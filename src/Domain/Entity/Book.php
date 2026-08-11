<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\EntityId;

/**
 * Book entity type.
 * 
 * @label VERIFIED - Book is a core entity type in legacy system with chapters as content nodes
 */
final class Book extends Entity
{
    public function __construct(
        EntityId $id,
        string $title,
        string $slug,
        private ?string $author = null,
        private ?string $series = null,
        private ?int $seriesOrder = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
        ?\DateTimeImmutable $deletedAt = null
    ) {
        parent::__construct($id, self::typeName(), $title, $slug, $createdAt, $updatedAt, $deletedAt);
    }

    public static function typeName(): string
    {
        return 'book';
    }

    public function author(): ?string
    {
        return $this->author;
    }

    public function series(): ?string
    {
        return $this->series;
    }

    public function seriesOrder(): ?int
    {
        return $this->seriesOrder;
    }

    public function updateAuthor(?string $author): void
    {
        $this->author = $author;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateSeries(?string $series, ?int $seriesOrder): void
    {
        $this->series = $series;
        $this->seriesOrder = $seriesOrder;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
