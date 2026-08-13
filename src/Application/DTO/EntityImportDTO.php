<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\ValueObject\EntityId;

/**
 * Data Transfer Object for importing entities.
 * 
 * @label PROPOSED - DTO pattern for validated import data before persistence
 */
final class EntityImportDTO
{
    /**
     * @param array<string, mixed> $metadata Additional entity-specific metadata
     * @param array<string, mixed> $taxonomy Tags, categories, authors, etc.
     * @param array<ContentNodeImportDTO> $contentNodes Content nodes to import with this entity
     */
    public function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly ?string $slug = null,
        private readonly ?string $description = null,
        private readonly ?string $filePath = null,
        private readonly ?string $coverPath = null,
        private readonly array $metadata = [],
        private readonly array $taxonomy = [],
        private readonly array $contentNodes = []
    ) {
    }

    public function type(): string
    {
        return $this->type;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function slug(): ?string
    {
        return $this->slug;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function filePath(): ?string
    {
        return $this->filePath;
    }

    public function coverPath(): ?string
    {
        return $this->coverPath;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function taxonomy(): array
    {
        return $this->taxonomy;
    }

    public function contentNodes(): array
    {
        return $this->contentNodes;
    }

    /**
     * Convert to array for validation/debugging.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'file_path' => $this->filePath,
            'cover_path' => $this->coverPath,
            'metadata' => $this->metadata,
            'taxonomy' => $this->taxonomy,
            'content_nodes' => array_map(
                fn($node) => $node->toArray(),
                $this->contentNodes
            ),
        ];
    }
}
