<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Importer;

use App\Application\Importer\CsvImporter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CsvImporter.
 *
 * @label PROPOSED - Tests for Phase 4 (Import System)
 */
final class CsvImporterTest extends TestCase
{
    private CsvImporter $importer;

    protected function setUp(): void
    {
        $this->importer = new CsvImporter('book');
    }

    public function testSupportsCsvFiles(): void
    {
        $this->assertTrue($this->importer->supports('/path/to/file.csv'));
        $this->assertTrue($this->importer->supports('/path/to/file.CSV')); // Case insensitive
        $this->assertFalse($this->importer->supports('/path/to/file.txt'));
        $this->assertFalse($this->importer->supports('/path/to/file.md'));
    }

    public function testGetEntityType(): void
    {
        $importer = new CsvImporter('book');
        $this->assertEquals('book', $importer->getEntityType());

        $importer = new CsvImporter('manuscript');
        $this->assertEquals('manuscript', $importer->getEntityType());
    }

    public function testImportThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        $this->importer->import('/non/existent/file.csv');
    }

    public function testImportThrowsExceptionForEmptyFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_test_');
        file_put_contents($tempFile, '');

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('CSV file is empty or has no headers');

            $this->importer->import($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportThrowsExceptionForMissingTitleColumn(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_test_');
        
        $content = <<<CSV
name,description
"Item 1","Description 1"
CSV;

        file_put_contents($tempFile, $content);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage("Required column 'title' not found in CSV");

            $this->importer->import($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportExtractsDataFromCsv(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_test_');

        $content = <<<CSV
title,content,slug
"First Article","This is the first article content.","first-article"
"Second Article","This is the second article content.","second-article"
"Third Article","This is the third article content.","third-article"
CSV;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $this->assertEquals('First Article', $dto->title());
            $this->assertEquals('book', $dto->type());
            $this->assertEquals('first-article', $dto->slug());

            $nodes = $dto->contentNodes();
            $this->assertCount(3, $nodes);

            $firstNode = $nodes[0];
            $this->assertEquals('First Article', $firstNode->title());
            $this->assertEquals('first-article', $firstNode->slug());
            $this->assertStringContainsString('<p>', $firstNode->content());
            $this->assertStringContainsString('This is the first article content.', $firstNode->content());
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportUsesFilenameAsFallbackTitle(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_test_');

        $content = <<<CSV
title,content
"","Content without title"
CSV;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            // Should use filename as title when first row title is empty
            $this->assertNotEmpty($dto->title());
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportGeneratesSlugs(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_test_');

        $content = <<<CSV
title,content
"My Amazing Article!","Content here"
"Another Article @ Home","More content"
CSV;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $slug = $dto->slug();
            $this->assertNotEmpty($slug);
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);

            $nodes = $dto->contentNodes();
            foreach ($nodes as $node) {
                $this->assertMatchesRegularExpression('/^[a-z0-9-]+-\d+$/', $node->slug());
            }
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportConvertsTextToHtml(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_test_');

        $content = <<<CSV
title,content
"Test Document","Special chars: <>&\\" and\nnewlines"
CSV;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $nodes = $dto->contentNodes();
            $this->assertNotEmpty($nodes);

            $node = $nodes[0];
            $htmlContent = $node->content();
            
            // Should contain HTML paragraph tags
            $this->assertStringContainsString('<p>', $htmlContent);
            $this->assertStringContainsString('</p>', $htmlContent);
            
            // Should have escaped special characters
            $this->assertStringContainsString('&lt;', $htmlContent);
            $this->assertStringContainsString('&gt;', $htmlContent);
            $this->assertStringContainsString('&amp;', $htmlContent);
            
            // Should preserve newlines as <br>
            $this->assertStringContainsString('<br />', $htmlContent);
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportReturnsEntityImportDTO(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_test_');

        $content = <<<CSV
title,content
"Test","Some content"
CSV;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $this->assertInstanceOf(\App\Application\DTO\EntityImportDTO::class, $dto);
            $this->assertIsArray($dto->toArray());

            // Check metadata includes original format and row count
            $metadata = $dto->metadata();
            $this->assertArrayHasKey('original_format', $metadata);
            $this->assertEquals('csv', $metadata['original_format']);
            $this->assertArrayHasKey('row_count', $metadata);
            $this->assertEquals(1, $metadata['row_count']);
            $this->assertArrayHasKey('columns', $metadata);
            $this->assertEquals(['title', 'content'], $metadata['columns']);
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportHandlesMultipleRows(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_test_');

        $content = <<<CSV
title,content
"Row 1","Content 1"
"Row 2","Content 2"
"Row 3","Content 3"
"Row 4","Content 4"
"Row 5","Content 5"
CSV;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $nodes = $dto->contentNodes();
            $this->assertCount(5, $nodes);

            // Check order
            for ($i = 0; $i < 5; $i++) {
                $this->assertEquals($i, $nodes[$i]->order());
                $this->assertEquals("Row " . ($i + 1), $nodes[$i]->title());
            }
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportWithCustomColumns(): void
    {
        $importer = new CsvImporter('book', 'name', 'body');

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_test_');

        $content = <<<CSV
name,body,identifier
"Custom Title","Custom body content","custom-id"
CSV;

        file_put_contents($tempFile, $content);

        try {
            $dto = $importer->import($tempFile);

            $this->assertEquals('Custom Title', $dto->title());
            
            $nodes = $dto->contentNodes();
            $this->assertCount(1, $nodes);
            $this->assertStringContainsString('Custom body content', $nodes[0]->content());
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportSkipsEmptyRows(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_test_');

        $content = <<<CSV
title,content
"Row 1","Content 1"
,,
"Row 2","Content 2"
,,"
"Row 3","Content 3"
CSV;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $nodes = $dto->contentNodes();
            // Should only have 3 nodes (empty rows skipped)
            $this->assertCount(3, $nodes);
        } finally {
            unlink($tempFile);
        }
    }
}
