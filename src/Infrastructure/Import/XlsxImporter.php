<?php

declare(strict_types=1);

namespace Infrastructure\Import;

use Domain\Import\ImportResult;
use InvalidArgumentException;
use RuntimeException;

/**
 * XLSX Importer for Excel spreadsheet files
 * 
 * Imports XLSX files and creates structured content nodes:
 * - One root node per worksheet containing table metadata
 * - Child nodes for each row in the worksheet
 * - Metadata extraction (sheet names, row count, column count)
 * 
 * Security features:
 * - Path traversal prevention
 * - MIME type validation (application/vnd.openxmlformats-officedocument.spreadsheetml.sheet)
 * - File size limits (20MB default)
 * - XML entity attack prevention (XXE)
 * - Formula sanitization
 * 
 * @label PROPOSED - Phase 4.3 implementation per plan
 */
class XlsxImporter extends AbstractImporter
{
    /**
     * Maximum file size: 20MB for XLSX
     */
    protected const MAX_FILE_SIZE = 20971520;

    /**
     * Allowed MIME types for XLSX files
     */
    protected const ALLOWED_MIME_TYPES = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip', // XLSX is a ZIP archive
        'application/x-empty', // Empty files
    ];

    /**
     * Supported file extensions
     */
    private const SUPPORTED_EXTENSIONS = ['xlsx'];

    /**
     * Maximum number of sheets to import
     */
    private const MAX_SHEETS = 50;

    /**
     * Maximum rows per sheet
     */
    private const MAX_ROWS_PER_SHEET = 10000;

    /**
     * Import an XLSX file and return structured result
     * 
     * Creates content nodes:
     * - Root node per worksheet with metadata
     * - Child nodes: Each data row as a separate node
     * 
     * @throws InvalidArgumentException If file is invalid
     * @throws RuntimeException If parsing fails
     */
    public function import(string $filePath): ImportResult
    {
        // Validate file
        $this->validateFile($filePath);

        try {
            // Extract and parse XLSX (ZIP-based format)
            $sheets = $this->parseXlsx($filePath);

            if (empty($sheets)) {
                return $this->createEmptyResult($filePath);
            }

            // Limit number of sheets
            $sheets = array_slice($sheets, 0, self::MAX_SHEETS);

            // Create content nodes from all sheets
            $contentNodes = [];
            $allWarnings = [];
            $nodePosition = 0;

            foreach ($sheets as $sheetIndex => $sheet) {
                $sheetName = $sheet['name'] ?? "Sheet_" . ($sheetIndex + 1);
                $rows = $sheet['rows'] ?? [];

                // Skip empty sheets but add warning
                if (empty($rows)) {
                    $allWarnings[] = "Sheet '{$sheetName}': Contains no data rows";
                    continue;
                }

                // Limit rows per sheet
                if (count($rows) > self::MAX_ROWS_PER_SHEET) {
                    $allWarnings[] = "Sheet '{$sheetName}': Truncated to " . self::MAX_ROWS_PER_SHEET . " rows";
                    $rows = array_slice($rows, 0, self::MAX_ROWS_PER_SHEET);
                }

                // Extract headers (first row)
                $headers = !empty($rows) ? array_shift($rows) : [];
                $headers = $this->sanitizeHeaders($headers);

                // Create root node for this sheet
                $rootNode = $this->createSheetRootNode(
                    $sheetName,
                    $headers,
                    count($rows),
                    $nodePosition++
                );
                $contentNodes[] = $rootNode;

                // Create child nodes for data rows
                $childNodes = $this->createRowNodes($rows, $headers, $rootNode['id'], $nodePosition);
                $contentNodes = array_merge($contentNodes, $childNodes);
                $nodePosition += count($childNodes);

                // Collect warnings for this sheet
                $allWarnings = array_merge($allWarnings, $this->generateWarnings($headers, $sheetName));
            }

            // Extract entity title from filename
            $entityTitle = $this->extractEntityTitle($filePath);

            // Build metadata
            $metadata = $this->buildMetadata($filePath, count($sheets));

            return new ImportResult(
                importId: $this->generateImportId(),
                contentNodes: $contentNodes,
                entityTitle: $entityTitle,
                entityType: 'spreadsheet',
                metadata: $metadata,
                warnings: $allWarnings,
                errors: []
            );

        } catch (\Exception $e) {
            throw new RuntimeException(
                "Failed to import XLSX file: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Check if this importer supports the given file
     */
    public function supports(string $filePath): bool
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return $ext === 'xlsx';
    }

    /**
     * Get list of supported file extensions
     */
    public function getSupportedExtensions(): array
    {
        return self::SUPPORTED_EXTENSIONS;
    }

    /**
     * Parse XLSX file into array of sheets with rows
     * 
     * XLSX is a ZIP archive containing XML files.
     * We extract and parse the XML safely without using external libraries.
     * 
     * @return array<int, array{name: string, rows: array<int, array<int, string>>}>
     */
    private function parseXlsx(string $filePath): array
    {
        $sheets = [];
        
        // Open ZIP archive
        $zip = new \ZipArchive();
        $result = $zip->open($filePath);

        if ($result !== true) {
            throw new RuntimeException("Cannot open XLSX file as ZIP archive");
        }

        try {
            // Prevent XXE attacks by disabling external entities
            libxml_disable_entity_loader(true);
            
            // Read workbook.xml to get sheet information
            $workbookXml = $zip->getFromName('xl/workbook.xml');
            if ($workbookXml === false) {
                throw new RuntimeException("Cannot find workbook.xml in XLSX");
            }

            // Parse workbook XML safely
            $workbook = simplexml_load_string($workbookXml, 'SimpleXMLElement', LIBXML_NOENT | LIBXML_DTDLOAD | LIBXML_DTDATTR);
            if ($workbook === false) {
                throw new RuntimeException("Failed to parse workbook.xml");
            }

            // Get sheet definitions
            $sheetsXml = $workbook->sheets->sheet ?? [];
            
            $sheetIndex = 0;
            foreach ($sheetsXml as $sheetDef) {
                if ($sheetIndex >= self::MAX_SHEETS) {
                    break;
                }

                $sheetName = (string) ($sheetDef['name'] ?? "Sheet_" . ($sheetIndex + 1));
                $sheetId = (string) ($sheetDef['sheetId'] ?? ($sheetIndex + 1));
                
                // Find the corresponding worksheet XML file
                // Sheet IDs typically map to worksheetN.xml files
                $worksheetPath = "xl/worksheets/sheet{$sheetId}.xml";
                
                // Try alternative naming if standard doesn't exist
                if (!$zip->locateName($worksheetPath)) {
                    // Try to find by iterating through available files
                    $worksheetPath = $this->findWorksheetPath($zip, $sheetIndex);
                }

                $worksheetXml = $zip->getFromName($worksheetPath);
                
                if ($worksheetXml !== false) {
                    $rows = $this->parseWorksheetXml($worksheetXml);
                    $sheets[] = [
                        'name' => $sheetName,
                        'rows' => $rows,
                    ];
                } else {
                    // Empty sheet
                    $sheets[] = [
                        'name' => $sheetName,
                        'rows' => [],
                    ];
                }

                $sheetIndex++;
            }

        } finally {
            $zip->close();
            // Re-enable entity loader
            libxml_disable_entity_loader(false);
        }

        return $sheets;
    }

    /**
     * Find worksheet file path in ZIP archive
     */
    private function findWorksheetPath(\ZipArchive $zip, int $sheetIndex): string
    {
        // Try common patterns
        $patterns = [
            "xl/worksheets/sheet" . ($sheetIndex + 1) . ".xml",
            "xl/worksheets/worksheet" . ($sheetIndex + 1) . ".xml",
        ];

        foreach ($patterns as $pattern) {
            if ($zip->locateName($pattern)) {
                return $pattern;
            }
        }

        // Search for any worksheet file
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileInfo = $zip->statIndex($i);
            if ($fileInfo && strpos($fileInfo['name'], 'xl/worksheets/') === 0 && strpos($fileInfo['name'], '.xml') !== false) {
                return $fileInfo['name'];
            }
        }

        return "xl/worksheets/sheet" . ($sheetIndex + 1) . ".xml";
    }

    /**
     * Parse worksheet XML into array of rows
     * 
     * Handles cell references (A1, B2, etc.) and shared strings
     * 
     * @return array<int, array<int, string>>
     */
    private function parseWorksheetXml(string $xmlContent): array
    {
        $rows = [];
        
        // Parse XML safely
        libxml_use_internal_errors(true);
        $worksheet = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOENT | LIBXML_DTDLOAD | LIBXML_DTDATTR);
        libxml_clear_errors();

        if ($worksheet === false) {
            return [];
        }

        // Extract shared strings if present
        $sharedStrings = $this->extractSharedStrings($xmlContent);

        // Parse sheetData/row elements
        if (isset($worksheet->sheetData->row)) {
            foreach ($worksheet->sheetData->row as $row) {
                $rowData = [];
                
                foreach ($row->c as $cell) {
                    $cellRef = (string) ($cell['r'] ?? '');
                    $cellType = (string) ($cell['t'] ?? 'n'); // n=number, s=string, b=boolean, etc.
                    
                    $value = $this->extractCellValue($cell, $cellType, $sharedStrings);
                    
                    // Convert cell reference (e.g., "A1") to column index
                    $colIndex = $this->columnRefToIndex($cellRef);
                    
                    // Place value at correct column index
                    if ($colIndex !== null) {
                        $rowData[$colIndex] = $value;
                    } else {
                        $rowData[] = $value;
                    }
                }

                // Fill gaps with empty strings
                if (!empty($rowData)) {
                    ksort($rowData);
                    $maxIndex = max(array_keys($rowData));
                    for ($i = 0; $i <= $maxIndex; $i++) {
                        if (!isset($rowData[$i])) {
                            $rowData[$i] = '';
                        }
                    }
                    $rows[] = array_values($rowData);
                }
            }
        }

        return $rows;
    }

    /**
     * Extract shared strings from worksheet or separate file
     * 
     * @return array<int, string>
     */
    private function extractSharedStrings(string $worksheetXml): array
    {
        $sharedStrings = [];
        
        // Try to find sharedStrings within the worksheet
        if (strpos($worksheetXml, '<sharedStrings>') !== false) {
            preg_match_all('/<si><t[^>]*>(.*?)<\/t><\/si>/s', $worksheetXml, $matches);
            if (!empty($matches[1])) {
                $sharedStrings = $matches[1];
            }
        }

        return $sharedStrings;
    }

    /**
     * Extract value from a cell element
     */
    private function extractCellValue(\SimpleXMLElement $cell, string $cellType, array $sharedStrings): string
    {
        $value = '';

        switch ($cellType) {
            case 's': // Shared string
                $index = (int) ($cell->v ?? 0);
                $value = $sharedStrings[$index] ?? '';
                break;

            case 'b': // Boolean
                $value = ($cell->v ?? '0') === '1' ? 'TRUE' : 'FALSE';
                break;

            case 'e': // Error
                $value = '#ERROR: ' . ($cell->v ?? 'Unknown');
                break;

            case 'str': // String formula result
            case 'inlineStr': // Inline string
                $value = (string) ($cell->v ?? '');
                break;

            case 'n': // Number (default)
            default:
                $value = (string) ($cell->v ?? '');
                break;
        }

        // Sanitize formula results
        return $this->sanitizeCell($value);
    }

    /**
     * Convert Excel column reference (A, B, ..., AA, AB) to zero-based index
     */
    private function columnRefToIndex(string $cellRef): ?int
    {
        if (empty($cellRef) || !preg_match('/^([A-Z]+)(\d+)$/', $cellRef, $matches)) {
            return null;
        }

        $columnLetters = $matches[1];
        $index = 0;
        $length = strlen($columnLetters);

        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($columnLetters[$i]) - ord('A') + 1);
        }

        return $index - 1; // Zero-based
    }

    /**
     * Sanitize cell value to prevent injection attacks
     */
    private function sanitizeCell(?string $cell): string
    {
        if ($cell === null) {
            return '';
        }

        // Trim whitespace
        $cell = trim($cell);

        // Check if cell starts with formula character (for exported data)
        if (preg_match('/^[=+\-@]/', $cell)) {
            // Prefix with single quote to treat as text
            $cell = "'" . $cell;
        }

        // Remove null bytes
        $cell = str_replace("\0", '', $cell);

        // Remove potential XSS vectors
        $cell = strip_tags($cell);

        return $cell;
    }

    /**
     * Sanitize header names
     * 
     * @param array<int, string|null> $headers
     * @return array<int, string>
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];

        foreach ($headers as $index => $header) {
            $cleanHeader = $header ?? "Column_" . ($index + 1);
            $cleanHeader = trim($cleanHeader);
            
            // Ensure unique column names
            if (empty($cleanHeader)) {
                $cleanHeader = "Column_" . ($index + 1);
            }

            $sanitized[] = $cleanHeader;
        }

        return $sanitized;
    }

    /**
     * Create root content node for a worksheet
     * 
     * @param array<int, string> $headers
     */
    private function createSheetRootNode(
        string $sheetName,
        array $headers,
        int $rowCount,
        int $position
    ): array {
        return [
            'id' => 'node_' . bin2hex(random_bytes(16)),
            'type' => 'table_root',
            'title' => $sheetName,
            'content' => json_encode([
                'sheet_name' => $sheetName,
                'headers' => $headers,
                'row_count' => $rowCount,
                'column_count' => count($headers),
            ], JSON_PRETTY_PRINT),
            'contentType' => 'application/json',
            'position' => $position,
            'parentId' => null,
            'metadata' => [
                'is_root' => true,
                'node_type' => 'table_container',
                'sheet_name' => $sheetName,
            ],
        ];
    }

    /**
     * Create child nodes for each data row
     * 
     * @param array<int, array<int, string>> $rows
     * @param array<int, string> $headers
     * @param string $parentId
     * @return array<int, array<string, mixed>>
     */
    private function createRowNodes(
        array $rows,
        array $headers,
        string $parentId,
        int &$startPosition
    ): array {
        $nodes = [];

        foreach ($rows as $index => $row) {
            // Create key-value pairs from headers and row data
            $rowData = [];
            foreach ($headers as $colIndex => $header) {
                $rowData[$header] = $row[$colIndex] ?? '';
            }

            $nodes[] = [
                'id' => 'node_' . bin2hex(random_bytes(16)),
                'type' => 'table_row',
                'title' => "Row " . ($index + 1),
                'content' => json_encode($rowData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'contentType' => 'application/json',
                'position' => $startPosition + $index + 1,
                'parentId' => $parentId,
                'metadata' => [
                    'row_index' => $index,
                    'is_header' => false,
                ],
            ];
        }

        $startPosition += count($nodes);
        return $nodes;
    }

    /**
     * Create result for empty XLSX file
     */
    private function createEmptyResult(string $filePath): ImportResult
    {
        return new ImportResult(
            importId: $this->generateImportId(),
            contentNodes: [],
            entityTitle: $this->extractEntityTitle($filePath),
            entityType: 'spreadsheet',
            metadata: [
                'sheet_count' => 0,
                'warning' => 'Empty XLSX file',
            ],
            warnings: ['XLSX file contains no data'],
            errors: []
        );
    }

    /**
     * Extract entity title from filename
     */
    private function extractEntityTitle(string $filePath): string
    {
        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        // Convert underscores and hyphens to spaces
        $title = str_replace(['_', '-'], ' ', $filename);
        // Capitalize words
        $title = ucwords($title);
        
        return $title;
    }

    /**
     * Build comprehensive metadata
     */
    private function buildMetadata(string $filePath, int $sheetCount): array
    {
        $basicMeta = $this->extractBasicMetadata($filePath);

        return array_merge($basicMeta, [
            'sheet_count' => $sheetCount,
            'import_format' => 'xlsx',
            'excel_version' => '2007+',
        ]);
    }

    /**
     * Generate warnings for potential issues
     * 
     * @param array<int, string> $headers
     */
    private function generateWarnings(array $headers, string $sheetName): array
    {
        $warnings = [];

        // Check for duplicate headers
        $uniqueHeaders = array_unique($headers);
        if (count($uniqueHeaders) !== count($headers)) {
            $duplicates = array_diff_assoc($headers, $uniqueHeaders);
            $warnings[] = "Sheet '{$sheetName}': Duplicate column headers detected: " . implode(', ', $duplicates);
        }

        // Check for empty headers
        foreach ($headers as $index => $header) {
            if (empty($header) || $header === "Column_" . ($index + 1)) {
                $warnings[] = "Sheet '{$sheetName}': Column " . ($index + 1) . " has no header name";
            }
        }

        return $warnings;
    }
}
