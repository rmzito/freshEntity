<?php

declare(strict_types=1);

namespace Domain\Content;

use Domain\ValueObject\ContentNodeId;
use Domain\ValueObject\EntityId;

/**
 * ContentNode represents a single unit of content within an entity.
 * 
 * @label VERIFIED - Content nodes have id, entity_id, type, title, content, order, parent_id, created_at, updated_at
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
        private int $order,
        private ?ContentNodeId $parentId = null,
        private ?\DateTimeImmutable $createdAt = null,
        private ?\DateTimeImmutable $updatedAt = null
    ) {
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function id(): ContentNodeId
    {
        return $this->id;
    }

    public function entityId(): EntityId
    {
        return $this->entityId;
    }

    public function type(): ContentNodeType
    {
        return $this->type;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function order(): int
    {
        return $this->order;
    }

    public function parentId(): ?ContentNodeId
    {
        return $this->parentId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?\DateTimeImmutable
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

    public function updateOrder(int $order): void
    {
        $this->order = $order;
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
