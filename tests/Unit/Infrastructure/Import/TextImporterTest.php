<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Import;

use Domain\Import\ImportResult;
use Infrastructure\Import\TextImporter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Infrastructure\Import\TextImporter
 */
final class TextImporterTest extends TestCase
{
    private TextImporter $importer;
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->importer = new TextImporter();
        $this->fixturesDir = __DIR__ . '/../../Fixtures/Import';
    }

    public function testSupportsTextFiles(): void
    {
        $this->assertTrue($this->importer->supports($this->fixturesDir . '/sample.txt'));
        $this->assertFalse($this->importer->supports($this->fixturesDir . '/sample.md'));
    }

    public function testGetSupportedExtensions(): void
    {
        $extensions = $this->importer->getSupportedExtensions();
        
        $this->assertContains('txt', $extensions);
        $this->assertCount(1, $extensions);
    }

    public function testImportSuccessfulTextFile(): void
    {
        $result = $this->importer->import($this->fixturesDir . '/sample.txt');

        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->hasContent());
        $this->assertEmpty($result->errors);
        $this->assertEquals('manuscript', $result->entityType);
        
        // Check metadata
        $this->assertEquals('text', $result->metadata['format']);
        $this->assertArrayHasKey('line_count', $result->metadata);
        $this->assertArrayHasKey('word_count', $result->metadata);
    }

    public function testImportCreatesSingleNode(): void
    {
        $result = $this->importer->import($this->fixturesDir . '/sample.txt');

        $this->assertCount(1, $result->contentNodes);
        $this->assertEquals('section', $result->contentNodes[0]['type']);
        $this->assertEquals(0, $result->contentNodes[0]['position']);
        
        // Content should contain the text from the file
        $this->assertStringContainsString('sample plain text file', $result->contentNodes[0]['content']);
    }

    public function testImportExtractsTitleFromFirstLine(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'txt_title_');
        file_put_contents($tempFile, "My Custom Title\n\nRest of content...");
        
        try {
            $result = $this->importer->import($tempFile);

            $this->assertEquals('My Custom Title', $result->entityTitle);
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportUsesFilenameAsFallbackTitle(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'untitled_');
        file_put_contents($tempFile, "\n\n\nContent with empty first lines.");
        
        try {
            $result = $this->importer->import($tempFile);

            // Should use filename (without extension) as title
            $this->assertNotEmpty($result->entityTitle);
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportCountsLinesAndWords(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'txt_count_');
        file_put_contents($tempFile, "One two three\nFour five\nSix");
        
        try {
            $result = $this->importer->import($tempFile);

            $this->assertEquals(3, $result->metadata['line_count']);
            $this->assertEquals(6, $result->metadata['word_count']);
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportFailsForNonExistentFile(): void
    {
        $result = $this->importer->import('/non/existent/file.txt');

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->hasContent());
        $this->assertNotEmpty($result->errors);
        $this->assertStringContainsString('does not exist', $result->errors[0]);
    }

    public function testImportSanitizesContent(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'txt_sanitize_');
        // Content with Windows line endings (CRLF) that should be normalized
        file_put_contents($tempFile, "Title\r\n\r\nContent with\r\nmixed line endings.");
        
        try {
            $result = $this->importer->import($tempFile);

            $this->assertTrue($result->isSuccess());
            // Content should be normalized (CRLF -> LF)
            $this->assertStringNotContainsString("\r\n", $result->contentNodes[0]['content']);
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportHandlesEmptyFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'txt_empty_');
        file_put_contents($tempFile, '');
        
        try {
            $result = $this->importer->import($tempFile);

            $this->assertTrue($result->isSuccess());
            $this->assertCount(1, $result->contentNodes);
            $this->assertEquals('', $result->contentNodes[0]['content']);
        } finally {
            unlink($tempFile);
        }
    }
}
