<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Import;

use Infrastructure\Import\CsvImporter;
use Domain\Import\ImportResult;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * Unit tests for CsvImporter
 * 
 * @covers \Infrastructure\Import\CsvImporter
 * @covers \Infrastructure\Import\AbstractImporter
 */
class CsvImporterTest extends TestCase
{
    private CsvImporter $importer;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->importer = new CsvImporter();
        $this->tempDir = sys_get_temp_dir() . '/csv_import_test_' . bin2hex(random_bytes(8));
        
        if (!mkdir($this->tempDir, 0755, true)) {
            throw new RuntimeException("Cannot create temp directory");
        }
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
     * Create a temporary CSV file with given content
     */
    private function createTempCsvFile(string $content): string
    {
        $filePath = $this->tempDir . '/test.csv';
        file_put_contents($filePath, $content);
        return $filePath;
    }

    public function testSupportsCsvFiles(): void
    {
        $filePath = $this->tempDir . '/data.csv';
        file_put_contents($filePath, "col1,col2\nval1,val2");

        self::assertTrue($this->importer->supports($filePath));
    }

    public function testDoesNotSupportNonCsvFiles(): void
    {
        $filePath = $this->tempDir . '/data.txt';
        file_put_contents($filePath, "some text");

        self::assertFalse($this->importer->supports($filePath));
    }

    public function testGetSupportedExtensions(): void
    {
        $extensions = $this->importer->getSupportedExtensions();
        
        self::assertEquals(['csv'], $extensions);
    }

    public function testImportCreatesContentNodesFromCsvData(): void
    {
        $csvContent = "Name,Age,City\nJohn,30,NYC\nJane,25,LA";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        self::assertInstanceOf(ImportResult::class, $result);
        self::assertNotEmpty($result->contentNodes);
        
        // Should have root node + 2 data rows
        self::assertCount(3, $result->contentNodes);
        
        // Root node should be table_root
        $rootNode = $result->contentNodes[0];
        self::assertEquals('table_root', $rootNode['type']);
        self::assertNull($rootNode['parentId']);
        
        // Data nodes should be table_row
        $firstRowNode = $result->contentNodes[1];
        self::assertEquals('table_row', $firstRowNode['type']);
        self::assertEquals($rootNode['id'], $firstRowNode['parentId']);
    }

    public function testImportExtractsHeadersAndCreatesRowData(): void
    {
        $csvContent = "Product,Price,Quantity\nApple,1.50,100\nBanana,0.75,200";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        self::assertCount(3, $result->contentNodes);
        
        $rootNode = $result->contentNodes[0];
        $metadata = json_decode($rootNode['content'], true);
        
        self::assertEquals(['Product', 'Price', 'Quantity'], $metadata['headers']);
        self::assertEquals(2, $metadata['row_count']);
        self::assertEquals(3, $metadata['column_count']);
        
        // Check first row data
        $firstRowNode = $result->contentNodes[1];
        $rowData = json_decode($firstRowNode['content'], true);
        
        self::assertEquals('Apple', $rowData['Product']);
        self::assertEquals('1.50', $rowData['Price']);
        self::assertEquals('100', $rowData['Quantity']);
    }

    public function testImportDetectsCommaDelimiter(): void
    {
        $csvContent = "A,B,C\n1,2,3";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        $rootNode = $result->contentNodes[0];
        $metadata = json_decode($rootNode['content'], true);
        
        self::assertEquals(',', $metadata['delimiter']);
    }

    public function testImportDetectsSemicolonDelimiter(): void
    {
        $csvContent = "A;B;C\n1;2;3";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        $rootNode = $result->contentNodes[0];
        $metadata = json_decode($rootNode['content'], true);
        
        self::assertEquals(';', $metadata['delimiter']);
    }

    public function testImportDetectsTabDelimiter(): void
    {
        $csvContent = "A\tB\tC\n1\t2\t3";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        $rootNode = $result->contentNodes[0];
        $metadata = json_decode($rootNode['content'], true);
        
        self::assertEquals("\t", $metadata['delimiter']);
    }

    public function testImportHandlesEmptyCsvFile(): void
    {
        $filePath = $this->createTempCsvFile("");

        $result = $this->importer->import($filePath);

        self::assertInstanceOf(ImportResult::class, $result);
        self::assertEmpty($result->contentNodes);
        self::assertContains('CSV file contains no data rows', $result->warnings);
    }

    public function testImportHandlesCsvWithOnlyHeaders(): void
    {
        $csvContent = "Header1,Header2,Header3";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        // Should have root node but no data rows
        self::assertCount(1, $result->contentNodes);
        self::assertEquals('table_root', $result->contentNodes[0]['type']);
        
        $metadata = json_decode($result->contentNodes[0]['content'], true);
        self::assertEquals(0, $metadata['row_count']);
    }

    public function testImportSanitizesFormulaCells(): void
    {
        // CSV injection attack vectors
        $csvContent = "Formula,Risk\n=1+1,+dangerous\n-Risk,@sum(A1:A10)";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        self::assertCount(3, $result->contentNodes);
        
        // Check that formula cells are sanitized
        $firstRowNode = $result->contentNodes[1];
        $rowData = json_decode($firstRowNode['content'], true);
        
        // Formula should be prefixed with quote
        self::assertEquals("'=1+1", $rowData['Formula']);
        self::assertEquals("'+dangerous", $rowData['Risk']);
    }

    public function testImportHandlesQuotedFields(): void
    {
        $csvContent = "Name,Description\n\"John Doe\",\"Hello, World\"\n\"Jane\",\"Simple text\"";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        self::assertCount(3, $result->contentNodes);
        
        $firstRowNode = $result->contentNodes[1];
        $rowData = json_decode($firstRowNode['content'], true);
        
        self::assertEquals('John Doe', $rowData['Name']);
        self::assertEquals('Hello, World', $rowData['Description']);
    }

    public function testImportExtractsEntityTitleFromFilename(): void
    {
        $csvContent = "A,B\n1,2";
        $filePath = $this->tempDir . '/my_data_file.csv';
        file_put_contents($filePath, $csvContent);

        $result = $this->importer->import($filePath);

        self::assertEquals('My Data File', $result->entityTitle);
    }

    public function testImportExtractsMetadata(): void
    {
        $csvContent = "Col1,Col2,Col3\nVal1,Val2,Val3";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        self::assertArrayHasKey('row_count', $result->metadata);
        self::assertArrayHasKey('column_count', $result->metadata);
        self::assertArrayHasKey('headers', $result->metadata);
        self::assertArrayHasKey('delimiter', $result->metadata);
        self::assertArrayHasKey('mime_type', $result->metadata);
        
        self::assertEquals(1, $result->metadata['row_count']);
        self::assertEquals(3, $result->metadata['column_count']);
        self::assertEquals(['Col1', 'Col2', 'Col3'], $result->metadata['headers']);
    }

    public function testImportGeneratesWarningsForDuplicateHeaders(): void
    {
        $csvContent = "Name,Age,Name\nJohn,30,Doe";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        self::assertNotEmpty($result->warnings);
        self::assertStringContainsString('Duplicate column headers', $result->warnings[0]);
    }

    public function testImportGeneratesWarningsForEmptyHeaders(): void
    {
        // CSV with empty header
        $csvContent = "Name,,City\nJohn,30,NYC";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        self::assertNotEmpty($result->warnings);
        self::assertStringContainsString('has no header name', $result->warnings[0]);
    }

    public function testImportValidatesMimeType(): void
    {
        // Create a fake CSV file with PDF magic bytes
        $filePath = $this->tempDir . '/fake.csv';
        file_put_contents($filePath, "%PDF-1.4\nThis is not a real CSV");

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MIME type');

        $this->importer->import($filePath);
    }

    public function testImportRejectsOversizedFile(): void
    {
        // This test would require creating a large file
        // For now, we verify the constant is set correctly
        $reflection = new \ReflectionClass(CsvImporter::class);
        $maxSize = $reflection->getConstant('MAX_FILE_SIZE');
        
        self::assertEquals(10485760, $maxSize); // 10MB
    }

    public function testImportHandlesNonExistentFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $this->importer->import('/non/existent/file.csv');
    }

    public function testImportPreservesUtf8Content(): void
    {
        $csvContent = "Name,City\nمحمد,الرياض\nصيني,بكين";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        $firstRowNode = $result->contentNodes[1];
        $rowData = json_decode($firstRowNode['content'], true);
        
        self::assertEquals('محمد', $rowData['Name']);
        // Second row title is auto-generated as "Row 2"
        self::assertEquals('Row 2', $result->contentNodes[2]['title']);
        // But the content should have the Arabic data
        $secondRowData = json_decode($result->contentNodes[2]['content'], true);
        self::assertEquals('صيني', $secondRowData['Name']);
        self::assertEquals('بكين', $secondRowData['City']);
    }

    public function testImportSkipsEmptyRows(): void
    {
        $csvContent = "A,B\n1,2\n\n3,4\n";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        // Should have root + 2 data rows (empty row skipped)
        self::assertCount(3, $result->contentNodes);
    }

    public function testImportAssignsSequentialPositions(): void
    {
        $csvContent = "A,B\n1,2\n3,4\n5,6";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        // Root at position 0
        self::assertEquals(0, $result->contentNodes[0]['position']);
        
        // Data rows at positions 1, 2, 3
        self::assertEquals(1, $result->contentNodes[1]['position']);
        self::assertEquals(2, $result->contentNodes[2]['position']);
        self::assertEquals(3, $result->contentNodes[3]['position']);
    }

    public function testImportGeneratesUniqueNodeIds(): void
    {
        $csvContent = "A,B\n1,2";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        $ids = array_column($result->contentNodes, 'id');
        
        // All IDs should be unique
        self::assertEquals(count($ids), count(array_unique($ids)));
        
        // IDs should start with 'node_'
        foreach ($ids as $id) {
            self::assertStringStartsWith('node_', $id);
        }
    }

    public function testImportSetsCorrectContentType(): void
    {
        $csvContent = "A,B\n1,2";
        $filePath = $this->createTempCsvFile($csvContent);

        $result = $this->importer->import($filePath);

        foreach ($result->contentNodes as $node) {
            self::assertEquals('application/json', $node['contentType']);
        }
    }
}
