<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Importer;

use App\Application\Importer\MarkdownImporter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for MarkdownImporter.
 * 
 * @label PROPOSED - Tests for Phase 4 (Import System)
 */
final class MarkdownImporterTest extends TestCase
{
    private MarkdownImporter $importer;

    protected function setUp(): void
    {
        $this->importer = new MarkdownImporter('book');
    }

    public function testSupportsMarkdownFiles(): void
    {
        $this->assertTrue($this->importer->supports('/path/to/file.md'));
        $this->assertTrue($this->importer->supports('/path/to/file.markdown'));
        $this->assertFalse($this->importer->supports('/path/to/file.txt'));
        $this->assertFalse($this->importer->supports('/path/to/file.pdf'));
    }

    public function testGetEntityType(): void
    {
        $importer = new MarkdownImporter('book');
        $this->assertEquals('book', $importer->getEntityType());

        $importer = new MarkdownImporter('manuscript');
        $this->assertEquals('manuscript', $importer->getEntityType());
    }

    public function testImportThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        $this->importer->import('/non/existent/file.md');
    }

    public function testImportExtractsTitleFromFrontMatter(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'md_import_test_');
        
        $content = <<<MD
---
title: Test Book Title
description: A test description
author: Test Author
---

# Introduction

This is the content.
MD;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $this->assertEquals('Test Book Title', $dto->title());
            $this->assertEquals('book', $dto->type());
            $this->assertStringContainsString('A test description', $dto->description() ?? '');
            
            $metadata = $dto->metadata();
            $this->assertArrayHasKey('title', $metadata);
            $this->assertArrayHasKey('description', $metadata);
            $this->assertArrayHasKey('author', $metadata);
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportUsesFilenameAsFallbackTitle(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'md_import_test_');
        
        $content = <<<MD
Some content without title.
MD;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            // Should use filename as title when no front matter or heading
            $this->assertNotEmpty($dto->title());
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportCreatesContentNodes(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'md_import_test_');
        
        $content = <<<MD
---
title: Test Book
---

Introduction text before headings.

# Chapter One

Content for chapter one.

## Section One Point One

Content for section 1.1.

# Chapter Two

Content for chapter two.
MD;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $nodes = $dto->contentNodes();
            
            // Should have root node + chapters + sections
            $this->assertGreaterThan(0, count($nodes));
            
            // First node should be root
            $rootNode = $nodes[0];
            $this->assertEquals('Test Book', $rootNode->title());
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportGeneratesSlug(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'md_import_test_');
        
        $content = <<<MD
---
title: My Amazing Book Title!
---

Content here.
MD;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $slug = $dto->slug();
            $this->assertNotEmpty($slug);
            $this->assertEquals('my-amazing-book-title', $slug);
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportConvertsMarkdownToHtml(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'md_import_test_');
        
        $content = <<<MD
---
title: Test
---

# Heading

This is **bold** and *italic* text.

[Link](https://example.com)
MD;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $nodes = $dto->contentNodes();
            $this->assertNotEmpty($nodes);
            
            // Check that HTML conversion occurred
            foreach ($nodes as $node) {
                $content = $node->content();
                // Content should contain HTML tags if converted
                if (!empty($content)) {
                    $this->assertStringContainsString('<', $content);
                }
            }
        } finally {
            unlink($tempFile);
        }
    }

    public function testImportReturnsEntityImportDTO(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'md_import_test_');
        
        $content = <<<MD
---
title: Test Book
---

Content.
MD;

        file_put_contents($tempFile, $content);

        try {
            $dto = $this->importer->import($tempFile);

            $this->assertInstanceOf(\App\Application\DTO\EntityImportDTO::class, $dto);
            $this->assertIsArray($dto->toArray());
        } finally {
            unlink($tempFile);
        }
    }
}
