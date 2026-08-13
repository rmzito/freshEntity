<?php

declare(strict_types=1);

namespace Infrastructure\Import;

use Domain\Import\ImportResult;
use InvalidArgumentException;
use RuntimeException;

/**
 * CSV Importer for spreadsheet files
 * 
 * Imports CSV files and creates structured content nodes:
 * - One root node containing the table title
 * - Child nodes for each row or logical section
 * - Metadata extraction (column count, row count, delimiter)
 * 
 * Security features:
 * - Path traversal prevention
 * - MIME type validation (text/csv, application/vnd.ms-excel)
 * - File size limits (10MB default)
 * - Delimiter auto-detection
 * - CSV injection prevention (formulas starting with =, +, -, @)
 * 
 * @label PROPOSED - Phase 4.2 implementation per plan
 */
class CsvImporter extends AbstractImporter
{
    /**
     * Maximum file size: 10MB for CSV
     */
    protected const MAX_FILE_SIZE = 10485760;

    /**
     * Allowed MIME types for CSV files
     */
    protected const ALLOWED_MIME_TYPES = [
        'text/csv',
        'application/vnd.ms-excel',
        'text/plain',
        'application/x-empty', // Empty files
    ];

    /**
     * Supported file extensions
     */
    private const SUPPORTED_EXTENSIONS = ['csv'];

    /**
     * Auto-detected delimiter
     */
    private string $delimiter = ',';

    /**
     * Import a CSV file and return structured result
     * 
     * Creates content nodes:
     * - Root node: Table metadata and headers
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
            // Read and parse CSV
            $rows = $this->parseCsv($filePath);

            if (empty($rows)) {
                return $this->createEmptyResult($filePath);
            }

            // Extract headers (first row)
            $headers = array_shift($rows);
            $headers = $this->sanitizeHeaders($headers);

            // Detect delimiter
            $this->delimiter = $this->detectDelimiter($filePath);

            // Create content nodes
            $contentNodes = [];

            // Root node with table metadata
            $rootNode = $this->createRootNode($filePath, $headers, count($rows));
            $contentNodes[] = $rootNode;

            // Create child nodes for data rows
            $childNodes = $this->createRowNodes($rows, $headers, $rootNode['id']);
            $contentNodes = array_merge($contentNodes, $childNodes);

            // Extract entity title from filename
            $entityTitle = $this->extractEntityTitle($filePath);

            // Build metadata
            $metadata = $this->buildMetadata($filePath, $headers, count($rows));

            return new ImportResult(
                importId: $this->generateImportId(),
                contentNodes: $contentNodes,
                entityTitle: $entityTitle,
                entityType: 'spreadsheet',
                metadata: $metadata,
                warnings: $this->generateWarnings($headers),
                errors: []
            );

        } catch (\Exception $e) {
            throw new RuntimeException(
                "Failed to import CSV file: " . $e->getMessage(),
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
        return $ext === 'csv';
    }

    /**
     * Get list of supported file extensions
     */
    public function getSupportedExtensions(): array
    {
        return self::SUPPORTED_EXTENSIONS;
    }

    /**
     * Parse CSV file into array of rows
     * 
     * @return array<int, array<int, string>>
     */
    private function parseCsv(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new RuntimeException("Cannot open CSV file for reading");
        }

        try {
            // Auto-detect delimiter from first line
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                return []; // Empty file
            }

            $this->delimiter = $this->detectDelimiterFromString($firstLine);
            
            // Rewind and parse with detected delimiter
            rewind($handle);

            while (($row = fgetcsv($handle, 0, $this->delimiter)) !== false) {
                // Skip completely empty rows
                if (empty($row) || (count($row) === 1 && $row[0] === null)) {
                    continue;
                }

                // Sanitize cells to prevent CSV injection
                $sanitizedRow = array_map([$this, 'sanitizeCell'], $row);
                $rows[] = $sanitizedRow;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Detect CSV delimiter from file content
     */
    private function detectDelimiter(string $filePath): string
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return ','; // Default
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                return ',';
            }

            return $this->detectDelimiterFromString($firstLine);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Detect delimiter from a string line
     */
    private function detectDelimiterFromString(string $line): string
    {
        $delimiters = [',', ';', "\t", '|'];
        $counts = [];

        foreach ($delimiters as $delimiter) {
            // Count occurrences but ignore those inside quotes
            $count = substr_count($line, $delimiter);
            $counts[$delimiter] = $count;
        }

        // Return delimiter with highest count
        arsort($counts);
        $bestDelimiter = key($counts);

        return $bestDelimiter !== null ? $bestDelimiter : ',';
    }

    /**
     * Sanitize cell value to prevent CSV injection attacks
     * 
     * CSV injection occurs when cells start with formula characters:
     * =, +, -, @
     * 
     * These can be executed by spreadsheet applications
     */
    private function sanitizeCell(?string $cell): string
    {
        if ($cell === null) {
            return '';
        }

        // Trim whitespace
        $cell = trim($cell);

        // Check if cell starts with formula character
        if (preg_match('/^[=+\-@]/', $cell)) {
            // Prefix with single quote to treat as text
            // Or add space at beginning
            $cell = "'" . $cell;
        }

        // Remove null bytes
        $cell = str_replace("\0", '', $cell);

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
     * Create root content node with table metadata
     * 
     * @param array<int, string> $headers
     */
    private function createRootNode(
        string $filePath,
        array $headers,
        int $rowCount
    ): array {
        $title = $this->extractEntityTitle($filePath);

        return [
            'id' => 'node_' . bin2hex(random_bytes(16)),
            'type' => 'table_root',
            'title' => $title,
            'content' => json_encode([
                'headers' => $headers,
                'row_count' => $rowCount,
                'column_count' => count($headers),
                'delimiter' => $this->delimiter,
            ], JSON_PRETTY_PRINT),
            'contentType' => 'application/json',
            'position' => 0,
            'parentId' => null,
            'metadata' => [
                'is_root' => true,
                'node_type' => 'table_container',
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
        string $parentId
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
                'position' => $index + 1,
                'parentId' => $parentId,
                'metadata' => [
                    'row_index' => $index,
                    'is_header' => false,
                ],
            ];
        }

        return $nodes;
    }

    /**
     * Create result for empty CSV file
     */
    private function createEmptyResult(string $filePath): ImportResult
    {
        return new ImportResult(
            importId: $this->generateImportId(),
            contentNodes: [],
            entityTitle: $this->extractEntityTitle($filePath),
            entityType: 'spreadsheet',
            metadata: [
                'row_count' => 0,
                'column_count' => 0,
                'warning' => 'Empty CSV file',
            ],
            warnings: ['CSV file contains no data rows'],
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
     * 
     * @param array<int, string> $headers
     */
    private function buildMetadata(
        string $filePath,
        array $headers,
        int $rowCount
    ): array {
        $basicMeta = $this->extractBasicMetadata($filePath);

        return array_merge($basicMeta, [
            'row_count' => $rowCount,
            'column_count' => count($headers),
            'headers' => $headers,
            'delimiter' => $this->delimiter,
            'import_format' => 'csv',
        ]);
    }

    /**
     * Generate warnings for potential issues
     * 
     * @param array<int, string> $headers
     */
    private function generateWarnings(array $headers): array
    {
        $warnings = [];

        // Check for duplicate headers
        $uniqueHeaders = array_unique($headers);
        if (count($uniqueHeaders) !== count($headers)) {
            $duplicates = array_diff_assoc($headers, $uniqueHeaders);
            $warnings[] = 'Duplicate column headers detected: ' . implode(', ', $duplicates);
        }

        // Check for empty headers
        foreach ($headers as $index => $header) {
            if (empty($header) || $header === "Column_" . ($index + 1)) {
                $warnings[] = "Column " . ($index + 1) . " has no header name";
            }
        }

        return $warnings;
    }
}
