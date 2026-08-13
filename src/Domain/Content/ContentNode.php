<?php

declare(strict_types=1);

namespace App\Domain\Content;

use App\Domain\ValueObject\ContentNodeId;
use App\Domain\ValueObject\EntityId;

/**
 * ContentNode represents a single unit of content within an entity.
 * 
 * @label VERIFIED - Content nodes have id, entity_id, type, title, content, metadata, path, position, parent_id, created_at, updated_at
 */
final class ContentNode
{
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct(
        private readonly ContentNodeId $id,
        private readonly EntityId $entityId,
        private ContentNodeType $type,
        private string $title,
        private string $content,
        private array $metadata,
        private string $path,
        private int $position,
        private ?ContentNodeId $parentId = null,
        private ?\DateTimeImmutable $createdAt = null,
        private ?\DateTimeImmutable $updatedAt = null
    ) {
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): ContentNodeId
    {
        return $this->id;
    }

    public function getEntityId(): EntityId
    {
        return $this->entityId;
    }

    public function getType(): ContentNodeType
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getParentId(): ?ContentNodeId
    {
        return $this->parentId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function updateTitle(string $title): void
    {
        $this->title = $title;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateContent(string $content): void
    {
        $this->content = $content;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updatePath(string $path): void
    {
        $this->path = $path;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updatePosition(int $position): void
    {
        $this->position = $position;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markAsDeleted(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
        $this->updatedAt = $this->deletedAt;
    }

    public function restore(): void
    {
        $this->deletedAt = null;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
