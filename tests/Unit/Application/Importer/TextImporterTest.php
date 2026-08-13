<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Importer;

use App\Application\Importer\TextImporter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TextImporter.
 * 
 * @label PROPOSED - Tests for Phase 4 (Import System)
 */
final class TextImporterTest extends TestCase
{
    private TextImporter $importer;

    protected function setUp(): void
    {
        $this->importer = new TextImporter('book');
    }

    public function testSupportsTextFiles(): void
    {
        $this->assertTrue($this->importer->supports('/path/to/file.txt'));
        $this->assertTrue($this->importer->supports('/path/to/file.TXT')); // Case insensitive
        $this->assertFalse($this->importer->supports('/path/to/file.md'));
        $this->assertFalse($this->importer->supports('/path/to/file.pdf'));
    }

    public function testGetEntityType(): void
    {
        $importer = new TextImporter('book');
        $this->assertEquals('book', $importer->getEntityType());

        $importer = new TextImporter('manuscript');
        $this->assertEquals('manuscript', $importer->getEntityType());
    }

    public function testImportThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        $this->importer->import('/non/existent/file.txt');
    }

    public function testImportExtractsTitleFromFirstLine(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'txt_import_test_');
        
        $content = <<<TXT
My Test Document Title

This is the content of the document.
It has multiple lines.
TXT;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $this->assertEquals('My Test Document Title', $dto->title());
            $this->assertEquals('book', $dto->type());
            $this->assertNotEmpty($dto->slug());
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportUsesFilenameAsFallbackTitle(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'txt_import_test_');
        
        $content = <<<TXT
This is a very long first line that should not be used as a title because it exceeds the maximum length allowed for titles in our system and therefore we should fall back to using the filename instead which is more appropriate in this case.

Some content here.
TXT;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            // Should use filename as title when first line is too long
            $this->assertNotEmpty($dto->title());
            $this->assertNotEquals('This is a very long first line', $dto->title());
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportCreatesContentNodes(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'txt_import_test_');
        
        $content = <<<TXT
Introduction

First Section

Content for first section.

Second Section

Content for second section.
TXT;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $nodes = $dto->contentNodes();
            
            // Should have root node + sections
            $this->assertGreaterThan(0, count($nodes));
            
            // First node should be root
            $rootNode = $nodes[0];
            $this->assertEquals('Introduction', $rootNode->title());
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportGeneratesSlug(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'txt_import_test_');
        
        $content = <<<TXT
My Amazing Document!

Content here.
TXT;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $slug = $dto->slug();
            $this->assertNotEmpty($slug);
            $this->assertEquals('my-amazing-document', $slug);
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportConvertsTextToHtml(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'txt_import_test_');
        
        $content = <<<TXT
Test Document

This is some text content.
It has multiple paragraphs.

And special characters: <>&"
TXT;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $nodes = $dto->contentNodes();
            $this->assertNotEmpty($nodes);
            
            // Check that HTML conversion occurred with proper escaping
            $foundEscapedContent = false;
            foreach ($nodes as $node) {
                $content = $node->content();
                if (!empty($content)) {
                    // Should contain HTML paragraph tags
                    $this->assertStringContainsString('<p>', $content);
                    // Check if any node contains escaped special characters
                    if (strpos($content, '&lt;') !== false || strpos($content, '&gt;') !== false) {
                        $foundEscapedContent = true;
                    }
                }
            }
            // At least one node should have escaped content
            $this->assertTrue($foundEscapedContent, 'Expected to find escaped HTML entities in content');
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportReturnsEntityImportDTO(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'txt_import_test_');
        
        $content = <<<TXT
Test Document

Some content.
TXT;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $this->assertInstanceOf(\App\Application\DTO\EntityImportDTO::class, $dto);
            $this->assertIsArray($dto->toArray());
            
            // Check metadata includes original format
            $metadata = $dto->metadata();
            $this->assertArrayHasKey('original_format', $metadata);
            $this->assertEquals('txt', $metadata['original_format']);
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportHandlesEmptyFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'txt_import_test_');
        
        file_put_contents($tempFile, '');

        try {
            $dto = $this->importer->import($tempFile);

            $this->assertNotEmpty($dto->title()); // Should use filename
            $nodes = $dto->contentNodes();
            $this->assertNotEmpty($nodes); // Should have at least root node
        } finally {
            unlink($tempFile);
        }
    }
}
