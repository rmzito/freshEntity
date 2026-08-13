<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Import;

use Domain\Import\ImportResult;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Domain\Import\ImportResult
 */
final class ImportResultTest extends TestCase
{
    public function testSuccessfulImportResult(): void
    {
        $result = new ImportResult(
            importId: 'import_123',
            contentNodes: [
                ['type' => 'chapter', 'title' => 'Introduction', 'content' => 'Hello', 'position' => 0]
            ],
            entityTitle: 'My Book',
            entityType: 'book',
            metadata: ['author' => 'John Doe'],
            warnings: [],
            errors: []
        );

        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->hasContent());
        $this->assertEquals('import_123', $result->importId);
        $this->assertCount(1, $result->contentNodes);
        $this->assertEquals('My Book', $result->entityTitle);
    }

    public function testFailedImportResult(): void
    {
        $result = new ImportResult(
            importId: 'import_456',
            contentNodes: [],
            entityTitle: null,
            entityType: null,
            metadata: [],
            warnings: [],
            errors: ['File not found', 'Invalid format']
        );

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->hasContent());
        $this->assertCount(2, $result->errors);
    }

    public function testStaticFailureMethod(): void
    {
        $result = ImportResult::failure('import_789', ['Error 1', 'Error 2']);

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->hasContent());
        $this->assertEquals('import_789', $result->importId);
        $this->assertCount(2, $result->errors);
        $this->assertEmpty($result->contentNodes);
    }

    public function testMergeResults(): void
    {
        $result1 = new ImportResult(
            importId: 'import_111',
            contentNodes: [['type' => 'chapter', 'title' => 'Ch1', 'content' => 'C1', 'position' => 0]],
            entityTitle: 'Book 1',
            entityType: 'book',
            metadata: ['key1' => 'value1'],
            warnings: ['Warning 1'],
            errors: []
        );

        $result2 = new ImportResult(
            importId: 'import_222',
            contentNodes: [['type' => 'chapter', 'title' => 'Ch2', 'content' => 'C2', 'position' => 1]],
            entityTitle: 'Book 2',
            entityType: 'manuscript',
            metadata: ['key2' => 'value2'],
            warnings: ['Warning 2'],
            errors: []
        );

        $merged = $result1->merge($result2);

        $this->assertEquals('import_111', $merged->importId);
        $this->assertCount(2, $merged->contentNodes);
        $this->assertEquals('Book 1', $merged->entityTitle); // First takes precedence
        $this->assertEquals('book', $merged->entityType); // First takes precedence
        $this->assertArrayHasKey('key1', $merged->metadata);
        $this->assertArrayHasKey('key2', $merged->metadata);
        $this->assertCount(2, $merged->warnings);
        $this->assertTrue($merged->isSuccess());
    }

    public function testDefaultValues(): void
    {
        $result = new ImportResult(importId: 'import_333');

        $this->assertEmpty($result->contentNodes);
        $this->assertNull($result->entityTitle);
        $this->assertNull($result->entityType);
        $this->assertEmpty($result->metadata);
        $this->assertEmpty($result->warnings);
        $this->assertEmpty($result->errors);
        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->hasContent());
    }
}
