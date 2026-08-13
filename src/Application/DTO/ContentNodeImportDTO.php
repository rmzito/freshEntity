<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\ValueObject\EntityId;
use App\Domain\Content\ContentNodeType;

/**
 * Data Transfer Object for importing content nodes.
 * 
 * @label PROPOSED - DTO pattern for validated import data before persistence
 */
final class ContentNodeImportDTO
{
    /**
     * @param array<string, mixed> $metadata Additional metadata for the node
     * @param array<string, mixed> $contentBlocks Structured content blocks
     */
    public function __construct(
        private readonly string $title,
        private readonly string $slug,
        private readonly ContentNodeType $type,
        private readonly string $content,
        private readonly array $contentBlocks = [],
        private readonly array $metadata = [],
        private readonly ?string $parentSlug = null,
        private readonly int $order = 0,
        private readonly ?string $sourceFile = null,
        private readonly ?int $lineNumber = null
    ) {
    }

    public function title(): string
    {
        return $this->title;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function type(): ContentNodeType
    {
        return $this->type;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function contentBlocks(): array
    {
        return $this->contentBlocks;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function parentSlug(): ?string
    {
        return $this->parentSlug;
    }

    public function order(): int
    {
        return $this->order;
    }

    public function sourceFile(): ?string
    {
        return $this->sourceFile;
    }

    public function lineNumber(): ?int
    {
        return $this->lineNumber;
    }

    /**
     * Convert to array for validation/debugging.
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'content' => $this->content,
            'content_blocks' => $this->contentBlocks,
            'metadata' => $this->metadata,
            'parent_slug' => $this->parentSlug,
            'order' => $this->order,
            'source_file' => $this->sourceFile,
            'line_number' => $this->lineNumber,
        ];
    }
}
