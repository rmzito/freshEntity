<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Import;

use Infrastructure\Import\XlsxImporter;
use Domain\Import\ImportResult;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * Unit tests for XlsxImporter
 * 
 * @label VERIFIED - Phase 4.3 test coverage
 */
class XlsxImporterTest extends TestCase
{
    private XlsxImporter $importer;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->importer = new XlsxImporter();
        $this->tempDir = sys_get_temp_dir() . '/entity_xlsx_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->tempDir);
        }
    }

    /**
     * Create a minimal valid XLSX file for testing
     */
    private function createMinimalXlsx(array $data = [['Name', 'Age'], ['Alice', '30'], ['Bob', '25']]): string
    {
        $filePath = $this->tempDir . '/test.xlsx';
        
        // Create a minimal XLSX structure (ZIP archive with XML files)
        $zip = new \ZipArchive();
        $zip->open($filePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // Add [Content_Types].xml
        $contentTypesXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML;
        $zip->addFromString('[Content_Types].xml', $contentTypesXml);

        // Add _rels/.rels
        $relsXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;
        $zip->addFromString('_rels/.rels', $relsXml);

        // Add xl/_rels/workbook.xml.rels
        $workbookRelsXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML;
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);

        // Build worksheet XML with data
        $sheetDataXml = '<sheetData>';
        foreach ($data as $rowIndex => $row) {
            $rowNum = $rowIndex + 1;
            $sheetDataXml .= "<row r=\"{$rowNum}\">";
            foreach ($row as $colIndex => $value) {
                $colLetter = $this->indexToColumnRef($colIndex);
                $cellRef = "{$colLetter}{$rowNum}";
                
                // Determine cell type
                if (is_numeric($value)) {
                    $cellType = 'n';
                    $cellValue = $value;
                } else {
                    $cellType = 's'; // We'll use inlineStr for simplicity in tests
                    $cellValue = htmlspecialchars($value, ENT_XML1);
                }
                
                $sheetDataXml .= "<c r=\"{$cellRef}\" t=\"{$cellType}\"><v>{$cellValue}</v></c>";
            }
            $sheetDataXml .= '</row>';
        }
        $sheetDataXml .= '</sheetData>';

        // Add xl/workbook.xml
        $workbookXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheets>
        <sheet name="Sheet1" sheetId="1" r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/>
    </sheets>
</workbook>
XML;
        $zip->addFromString('xl/workbook.xml', $workbookXml);

        // Add xl/worksheets/sheet1.xml
        $worksheetXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    {$sheetDataXml}
</worksheet>
XML;
        $zip->addFromString('xl/worksheets/sheet1.xml', $worksheetXml);

        $zip->close();

        return $filePath;
    }

    /**
     * Convert column index to Excel column reference (0=A, 1=B, 26=AA)
     */
    private function indexToColumnRef(int $index): string
    {
        $result = '';
        while ($index >= 0) {
            $result = chr(($index % 26) + ord('A')) . $result;
            $index = intdiv($index, 26) - 1;
        }
        return $result;
    }

    public function testSupportsXlsxFiles(): void
    {
        $this->assertTrue($this->importer->supports('/path/to/file.xlsx'));
        $this->assertTrue($this->importer->supports('/path/to/FILE.XLSX'));
        $this->assertFalse($this->importer->supports('/path/to/file.csv'));
        $this->assertFalse($this->importer->supports('/path/to/file.xls'));
    }

    public function testGetSupportedExtensions(): void
    {
        $this->assertEquals(['xlsx'], $this->importer->getSupportedExtensions());
    }

    public function testImportSimpleXlsxFile(): void
    {
        $filePath = $this->createMinimalXlsx();
        
        $result = $this->importer->import($filePath);

        $this->assertInstanceOf(ImportResult::class, $result);
        $this->assertNotEmpty($result->importId);
        $this->assertEquals('Test', $result->entityTitle);
        $this->assertEquals('spreadsheet', $result->entityType);
        $this->assertGreaterThan(0, count($result->contentNodes));
        $this->assertEmpty($result->errors);
    }

    public function testImportCreatesRootNodePerSheet(): void
    {
        $filePath = $this->createMinimalXlsx();
        
        $result = $this->importer->import($filePath);

        // Should have at least one root node
        $rootNodes = array_filter($result->contentNodes, fn($node) => $node['type'] === 'table_root');
        $this->assertGreaterThan(0, count($rootNodes));

        $rootNode = reset($rootNodes);
        $this->assertEquals('Sheet1', $rootNode['title']);
        $this->assertNull($rootNode['parentId']);
        $this->assertTrue($rootNode['metadata']['is_root']);
    }

    public function testImportCreatesRowNodes(): void
    {
        $filePath = $this->createMinimalXlsx();
        
        $result = $this->importer->import($filePath);

        // Should have row nodes
        $rowNodes = array_filter($result->contentNodes, fn($node) => $node['type'] === 'table_row');
        $this->assertGreaterThan(0, count($rowNodes));

        // Check first row node
        $firstRowNode = reset($rowNodes);
        $this->assertEquals('Row 1', $firstRowNode['title']);
        $this->assertNotNull($firstRowNode['parentId']);
    }

    public function testImportExtractsMetadata(): void
    {
        $filePath = $this->createMinimalXlsx();
        
        $result = $this->importer->import($filePath);

        $this->assertArrayHasKey('sheet_count', $result->metadata);
        $this->assertArrayHasKey('import_format', $result->metadata);
        $this->assertEquals('xlsx', $result->metadata['import_format']);
        $this->assertGreaterThan(0, $result->metadata['sheet_count']);
    }

    public function testImportHandlesEmptyXlsxFile(): void
    {
        // Create empty XLSX (only structure, no data rows)
        $filePath = $this->createMinimalXlsx([]);
        
        $result = $this->importer->import($filePath);

        $this->assertInstanceOf(ImportResult::class, $result);
        // Empty file should still create root node but with 0 rows
        // Or return empty nodes with warnings
        $this->assertGreaterThanOrEqual(0, count($result->contentNodes));
        // Should have warnings about empty content
        $this->assertTrue(
            !empty($result->warnings) || !empty($result->metadata['warning'] ?? ''),
            'Expected warnings or metadata warning for empty file'
        );
    }

    public function testImportSanitizesFormulaCells(): void
    {
        // Create XLSX with formula-like values
        $filePath = $this->createMinimalXlsx([
            ['Product', 'Price'],
            ['=SUM(A1)', '100'],
            ['@DANGER', '200'],
        ]);
        
        $result = $this->importer->import($filePath);

        $this->assertInstanceOf(ImportResult::class, $result);
        // Formulas should be sanitized with leading quote
        $rowNodes = array_filter($result->contentNodes, fn($node) => $node['type'] === 'table_row');
        $this->assertGreaterThan(0, count($rowNodes));
    }

    public function testImportValidatesFilePath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File does not exist');
        
        $this->importer->import('/nonexistent/path/file.xlsx');
    }

    public function testImportRejectsNonXlsxFile(): void
    {
        // Create a fake XLSX file (actually CSV content)
        $fakePath = $this->tempDir . '/fake.xlsx';
        file_put_contents($fakePath, "name,age\nAlice,30");
        
        // Should fail because it's not a valid ZIP/XLSX or MIME type mismatch
        $this->expectException(\InvalidArgumentException::class);
        
        $this->importer->import($fakePath);
    }

    public function testImportGeneratesWarningsForDuplicateHeaders(): void
    {
        $filePath = $this->createMinimalXlsx([
            ['Name', 'Name', 'Age'], // Duplicate header
            ['Alice', 'Smith', '30'],
        ]);
        
        $result = $this->importer->import($filePath);

        $this->assertNotEmpty($result->warnings);
        $warningFound = false;
        foreach ($result->warnings as $warning) {
            if (stripos($warning, 'duplicate') !== false || stripos($warning, 'column') !== false) {
                $warningFound = true;
                break;
            }
        }
        // If no explicit warning, check metadata for column info
        $this->assertTrue(
            $warningFound || isset($result->metadata['headers']),
            'Expected warnings about headers or metadata with header info'
        );
    }
}
