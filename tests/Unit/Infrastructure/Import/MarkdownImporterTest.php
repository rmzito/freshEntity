<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Import;

use Domain\Import\ImportResult;
use Infrastructure\Import\MarkdownImporter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Infrastructure\Import\MarkdownImporter
 */
final class MarkdownImporterTest extends TestCase
{
    private MarkdownImporter $importer;
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->importer = new MarkdownImporter();
        $this->fixturesDir = __DIR__ . '/../../Fixtures/Import';
    }

    public function testSupportsMarkdownFiles(): void
    {
        $this->assertTrue($this->importer->supports($this->fixturesDir . '/sample.md'));
        $this->assertFalse($this->importer->supports($this->fixturesDir . '/sample.txt'));
    }

    public function testGetSupportedExtensions(): void
    {
        $extensions = $this->importer->getSupportedExtensions();
        
        $this->assertContains('md', $extensions);
        $this->assertContains('markdown', $extensions);
        $this->assertCount(2, $extensions);
    }

    public function testImportSuccessfulMarkdownFile(): void
    {
        $result = $this->importer->import($this->fixturesDir . '/sample.md');

        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->hasContent());
        $this->assertEmpty($result->errors);
        $this->assertEquals('Sample Markdown Document', $result->entityTitle);
        $this->assertEquals('manuscript', $result->entityType);
        
        // Check metadata from front matter
        $this->assertEquals('John Doe', $result->metadata['author']);
        $this->assertEquals('2024-01-15', $result->metadata['date']);
        $this->assertEquals(['test', 'sample', 'markdown'], $result->metadata['tags']);
        $this->assertEquals('markdown', $result->metadata['format']);
    }

    public function testImportExtractsContentNodes(): void
    {
        $result = $this->importer->import($this->fixturesDir . '/sample.md');

        $this->assertCount(4, $result->contentNodes);
        
        // First node should be Introduction
        $this->assertEquals('Introduction', $result->contentNodes[0]['title']);
        $this->assertEquals('chapter', $result->contentNodes[0]['type']);
        $this->assertEquals(0, $result->contentNodes[0]['position']);
        
        // Second node should be Chapter One
        $this->assertEquals('Chapter One', $result->contentNodes[1]['title']);
        $this->assertEquals(1, $result->contentNodes[1]['position']);
        
        // Third node should be Chapter Two
        $this->assertEquals('Chapter Two', $result->contentNodes[2]['title']);
        
        // Fourth node should be Conclusion
        $this->assertEquals('Conclusion', $result->contentNodes[3]['title']);
    }

    public function testImportGeneratesUniqueImportId(): void
    {
        $result1 = $this->importer->import($this->fixturesDir . '/sample.md');
        $result2 = $this->importer->import($this->fixturesDir . '/sample.md');

        $this->assertNotEquals($result1->importId, $result2->importId);
        $this->assertStringStartsWith('import_', $result1->importId);
        $this->assertStringStartsWith('import_', $result2->importId);
    }

    public function testImportFailsForNonExistentFile(): void
    {
        $result = $this->importer->import('/non/existent/file.md');

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->hasContent());
        $this->assertNotEmpty($result->errors);
        $this->assertStringContainsString('does not exist', $result->errors[0]);
    }

    public function testImportHandlesMarkdownWithoutFrontMatter(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'md_test_');
        file_put_contents($tempFile, "# Title\n\nContent without front matter.");
        
        try {
            $result = $this->importer->import($tempFile);

            $this->assertTrue($result->isSuccess());
            $this->assertEquals('Title', $result->entityTitle);
            $this->assertCount(1, $result->contentNodes);
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportHandlesEmptyMarkdownFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'md_empty_');
        file_put_contents($tempFile, '');
        
        try {
            $result = $this->importer->import($tempFile);

            $this->assertTrue($result->isSuccess());
            $this->assertFalse($result->hasContent());
            $this->assertEmpty($result->contentNodes);
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportSanitizesContent(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'md_sanitize_');
        // Content with Windows line endings and null bytes
        file_put_contents($tempFile, "# Title\r\n\r\nContent with\rnull byte\0here.");
        
        try {
            $result = $this->importer->import($tempFile);

            $this->assertTrue($result->isSuccess());
            // Content should be sanitized (no null bytes, normalized line endings)
            $this->assertStringNotContainsString("\0", $result->contentNodes[0]['content']);
        } finally {
            unlink($tempFile);
        }
    }
}
