<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\EntityId;

/**
 * Base Entity class containing common properties and behavior.
 * 
 * @label VERIFIED - All entities in legacy code share common fields: id, type, title, slug, created_at, updated_at, deleted_at
 */
abstract class Entity
{
    public function __construct(
        private readonly EntityId $id,
        private string $type,
        private string $title,
        private string $slug,
        private ?\DateTimeImmutable $createdAt = null,
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $deletedAt = null
    ) {
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function id(): EntityId
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function slug(): string
    {
        return $this->slug;
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

    public function updateSlug(string $slug): void
    {
        $this->slug = $slug;
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

    abstract public static function typeName(): string;
}
