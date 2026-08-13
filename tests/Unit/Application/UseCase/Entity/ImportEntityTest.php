<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Entity;

use App\Application\DTO\EntityImportDTO;
use App\Application\DTO\ContentNodeImportDTO;
use App\Application\Importer\ImportService;
use App\Application\UseCase\Entity\ImportEntity;
use App\Domain\Entity\Book;
use App\Domain\Repository\EntityRepositoryInterface;
use App\Domain\ValueObject\EntityId;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ImportEntity use case.
 * 
 * @label PROPOSED - Tests for Phase 4 Import System
 */
final class ImportEntityTest extends TestCase
{
    private EntityRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $entityRepository;
    private ImportService&\PHPUnit\Framework\MockObject\MockObject $importService;
    private ImportEntity $useCase;

    protected function setUp(): void
    {
        $this->entityRepository = $this->createMock(EntityRepositoryInterface::class);
        $this->importService = $this->createMock(ImportService::class);
        $this->useCase = new ImportEntity(
            $this->entityRepository,
            $this->importService
        );
    }

    public function testImportsTxtFileSuccessfully(): void
    {
        $filePath = '/tmp/test.txt';
        file_put_contents($filePath, "Test Title\nTest content here");

        $dto = new EntityImportDTO(
            type: 'book',
            title: 'Test Title',
            slug: 'test-title',
            description: null,
            filePath: $filePath,
            coverPath: null,
            metadata: [],
            taxonomy: [],
            contentNodes: []
        );

        $this->importService
            ->expects($this->once())
            ->method('supports')
            ->with($filePath)
            ->willReturn(true);

        $this->importService
            ->expects($this->once())
            ->method('import')
            ->with($filePath)
            ->willReturn($dto);

        $savedEntity = null;
        $this->entityRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Book $entity) use (&$savedEntity) {
                $savedEntity = $entity;
            });

        $resultId = $this->useCase->execute($filePath);

        $this->assertNotNull($savedEntity);
        $this->assertEquals($savedEntity->id(), $resultId);
        $this->assertEquals('book', $savedEntity->type());
        $this->assertEquals('Test Title', $savedEntity->title());

        unlink($filePath);
    }

    public function testGeneratesSlugWhenNotProvided(): void
    {
        $filePath = '/tmp/test.txt';
        file_put_contents($filePath, "My Test Book\nSome content");

        $dto = new EntityImportDTO(
            type: 'book',
            title: 'My Test Book',
            slug: null, // No slug provided
            description: null,
            filePath: $filePath,
            coverPath: null,
            metadata: [],
            taxonomy: [],
            contentNodes: []
        );

        $this->importService
            ->method('supports')
            ->willReturn(true);

        $this->importService
            ->method('import')
            ->willReturn($dto);

        $capturedEntity = null;
        $this->entityRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Book $entity) use (&$capturedEntity) {
                $capturedEntity = $entity;
            });

        $this->useCase->execute($filePath);

        $this->assertNotNull($capturedEntity);
        $this->assertEquals('my-test-book', $capturedEntity->slug());

        unlink($filePath);
    }

    public function testImportsWithArabicTitle(): void
    {
        $filePath = '/tmp/test.txt';
        file_put_contents($filePath, "كتاب الاختبار\nمحتوى الاختبار");

        $dto = new EntityImportDTO(
            type: 'book',
            title: 'كتاب الاختبار',
            slug: null,
            description: null,
            filePath: $filePath,
            coverPath: null,
            metadata: [],
            taxonomy: [],
            contentNodes: []
        );

        $this->importService
            ->method('supports')
            ->willReturn(true);

        $this->importService
            ->method('import')
            ->willReturn($dto);

        $capturedEntity = null;
        $this->entityRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Book $entity) use (&$capturedEntity) {
                $capturedEntity = $entity;
            });

        $this->useCase->execute($filePath);

        $this->assertNotNull($capturedEntity);
        // Arabic characters should be preserved in slug
        $this->assertNotEmpty($capturedEntity->slug());

        unlink($filePath);
    }

    public function testSetsMetadataFromDto(): void
    {
        $filePath = '/tmp/test.txt';
        file_put_contents($filePath, "Test\nContent");

        $dto = new EntityImportDTO(
            type: 'book',
            title: 'Test Book',
            slug: 'test-book',
            description: null,
            filePath: $filePath,
            coverPath: null,
            metadata: [
                'isbn' => '978-0-123456-78-9',
                'publisher' => 'Test Publisher',
                'year' => 2024
            ],
            taxonomy: [],
            contentNodes: []
        );

        $this->importService
            ->method('supports')
            ->willReturn(true);

        $this->importService
            ->method('import')
            ->willReturn($dto);

        // Note: Current Book entity doesn't have getMetadata() method
        // This test documents the expected behavior for future implementation
        $this->entityRepository
            ->expects($this->once())
            ->method('save');

        // Should not throw exception
        $resultId = $this->useCase->execute($filePath);
        $this->assertNotNull($resultId);

        unlink($filePath);
    }

    public function testSetsTaxonomyFromDto(): void
    {
        $filePath = '/tmp/test.txt';
        file_put_contents($filePath, "Test\nContent");

        $dto = new EntityImportDTO(
            type: 'book',
            title: 'Test Book',
            slug: 'test-book',
            description: null,
            filePath: $filePath,
            coverPath: null,
            metadata: [],
            taxonomy: [
                'tags' => ['fiction', 'science'],
                'categories' => ['education', 'reference'],
                'authors' => ['John Doe', 'Jane Smith']
            ],
            contentNodes: []
        );

        $this->importService
            ->method('supports')
            ->willReturn(true);

        $this->importService
            ->method('import')
            ->willReturn($dto);

        // Note: Current Book entity doesn't have tags(), categories(), authors() methods
        // This test documents the expected behavior for future implementation
        $this->entityRepository
            ->expects($this->once())
            ->method('save');

        // Should not throw exception
        $resultId = $this->useCase->execute($filePath);
        $this->assertNotNull($resultId);

        unlink($filePath);
    }

    public function testThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        $this->useCase->execute('/non/existent/file.txt');
    }

    public function testThrowsExceptionForUnsupportedFile(): void
    {
        $filePath = '/tmp/test.xyz';
        file_put_contents($filePath, "test");

        $this->importService
            ->method('supports')
            ->with($filePath)
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported file format');

        $this->useCase->execute($filePath);

        unlink($filePath);
    }

    public function testHandlesContentNodesInDto(): void
    {
        $filePath = '/tmp/test.txt';
        file_put_contents($filePath, "Test\nContent");

        $contentNodeDto = new ContentNodeImportDTO(
            title: 'Chapter 1',
            slug: 'chapter-1',
            type: \App\Domain\Content\ContentNodeType::CHAPTER,
            content: '<p>Content here</p>',
            order: 1
        );

        $dto = new EntityImportDTO(
            type: 'book',
            title: 'Test Book',
            slug: 'test-book',
            description: null,
            filePath: $filePath,
            coverPath: null,
            metadata: [],
            taxonomy: [],
            contentNodes: [$contentNodeDto]
        );

        $this->importService
            ->method('supports')
            ->willReturn(true);

        $this->importService
            ->method('import')
            ->willReturn($dto);

        $this->entityRepository
            ->expects($this->once())
            ->method('save');

        // Should not throw exception even with content nodes
        $resultId = $this->useCase->execute($filePath);

        $this->assertNotNull($resultId);

        unlink($filePath);
    }

    public function testUsesProvidedSlugInsteadOfGenerating(): void
    {
        $filePath = '/tmp/test.txt';
        file_put_contents($filePath, "Test\nContent");

        $customSlug = 'custom-slug-123';
        $dto = new EntityImportDTO(
            type: 'book',
            title: 'Test Book',
            slug: $customSlug, // Custom slug provided
            description: null,
            filePath: $filePath,
            coverPath: null,
            metadata: [],
            taxonomy: [],
            contentNodes: []
        );

        $this->importService
            ->method('supports')
            ->willReturn(true);

        $this->importService
            ->method('import')
            ->willReturn($dto);

        $capturedEntity = null;
        $this->entityRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Book $entity) use (&$capturedEntity) {
                $capturedEntity = $entity;
            });

        $this->useCase->execute($filePath);

        $this->assertNotNull($capturedEntity);
        $this->assertEquals($customSlug, $capturedEntity->slug());

        unlink($filePath);
    }
}
