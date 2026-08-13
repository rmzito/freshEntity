<?php

declare(strict_types=1);

namespace App\Application\UseCase\Entity;

use App\Application\DTO\EntityImportDTO;
use App\Application\Importer\ImportService;
use App\Domain\Entity\Audio;
use App\Domain\Entity\Book;
use App\Domain\Entity\Manuscript;
use App\Domain\Entity\Video;
use App\Domain\Repository\EntityRepositoryInterface;
use App\Domain\ValueObject\EntityId;

/**
 * Use case for importing an entity from a file.
 * 
 * @label PROPOSED - Part of Import System (Phase 4)
 */
final class ImportEntity
{
    public function __construct(
        private EntityRepositoryInterface $entityRepository,
        private ImportService $importService
    ) {
    }

    /**
     * Import an entity from a file path.
     * 
     * @param string $filePath Path to the file to import
     * @return EntityId The ID of the created entity
     * @throws \RuntimeException If import fails
     */
    public function execute(string $filePath): EntityId
    {
        // Validate file exists
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        // Check if file is supported
        if (!$this->importService->supports($filePath)) {
            throw new \RuntimeException("Unsupported file format: " . pathinfo($filePath, PATHINFO_EXTENSION));
        }

        // Import file to get validated DTO
        $dto = $this->importService->import($filePath);

        // Generate slug if not provided
        $slug = $dto->slug() ?? $this->generateSlug($dto->title());

        // Create appropriate entity type based on DTO type
        $entity = $this->createEntity($dto, $slug);

        // Save entity
        $this->entityRepository->save($entity);

        return $entity->id();
    }

    /**
     * Create entity instance based on type.
     */
    private function createEntity(EntityImportDTO $dto, string $slug): Book|Audio|Video|Manuscript
    {
        $id = EntityId::generate();

        return match ($dto->type()) {
            'book' => new Book(
                id: $id,
                title: $dto->title(),
                slug: $slug,
                author: $dto->metadata()['author'] ?? null,
                series: $dto->metadata()['series'] ?? null,
                seriesOrder: $dto->metadata()['series_order'] ?? null
            ),
            'audio' => new Audio(
                id: $id,
                title: $dto->title(),
                slug: $slug,
                author: $dto->metadata()['author'] ?? null,
                durationSeconds: isset($dto->metadata()['duration']) ? (int)$dto->metadata()['duration'] : null,
                format: $dto->metadata()['format'] ?? null
            ),
            'video' => new Video(
                id: $id,
                title: $dto->title(),
                slug: $slug,
                author: $dto->metadata()['author'] ?? null,
                durationSeconds: isset($dto->metadata()['duration']) ? (int)$dto->metadata()['duration'] : null,
                format: $dto->metadata()['format'] ?? null,
                thumbnailPath: $dto->metadata()['thumbnail'] ?? null
            ),
            'manuscript' => new Manuscript(
                id: $id,
                title: $dto->title(),
                slug: $slug,
                author: $dto->metadata()['author'] ?? null,
                language: $dto->metadata()['language'] ?? null
            ),
            default => throw new \RuntimeException("Unknown entity type: {$dto->type()}"),
        };
    }

    /**
     * Generate a URL-safe slug from a title.
     */
    private function generateSlug(string $title): string
    {
        // Handle empty or null title
        if (empty($title)) {
            return 'entity-' . bin2hex(random_bytes(4));
        }
        
        // Convert to lowercase
        $slug = mb_strtolower($title);
        
        // Replace non-alphanumeric characters with hyphens (supporting Latin and Arabic)
        $slug = preg_replace('/[^a-z0-9\x{0600}-\x{06FF}]+/u', '-', $slug);
        
        // Remove multiple consecutive hyphens
        $slug = preg_replace('/-+/', '-', $slug ?? '');
        
        // Trim hyphens from start and end
        $slug = trim($slug, '-');
        
        // Ensure slug is not empty
        if (empty($slug)) {
            $slug = 'entity-' . bin2hex(random_bytes(4));
        }
        
        return $slug;
    }
}
