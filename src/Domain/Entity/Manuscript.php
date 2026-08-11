<?php

declare(strict_types=1);

namespace Domain\Entity;

use Domain\ValueObject\EntityId;

/**
 * Manuscript entity type.
 * 
 * @label VERIFIED - Manuscript is a core entity type in legacy system with content nodes and images
 */
final class Manuscript extends Entity
{
    public function __construct(
        EntityId $id,
        string $title,
        string $slug,
        private ?string $author = null,
        private ?string $language = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
        ?\DateTimeImmutable $deletedAt = null
    ) {
        parent::__construct($id, self::typeName(), $title, $slug, $createdAt, $updatedAt, $deletedAt);
    }

    public static function typeName(): string
    {
        return 'manuscript';
    }

    public function author(): ?string
    {
        return $this->author;
    }

    public function language(): ?string
    {
        return $this->language;
    }

    public function updateAuthor(?string $author): void
    {
        $this->author = $author;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateLanguage(?string $language): void
    {
        $this->language = $language;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
