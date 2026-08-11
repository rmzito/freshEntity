<?php

declare(strict_types=1);

namespace Domain\Entity;

/**
 * Video entity type.
 * 
 * @label VERIFIED - Video is a core entity type in legacy system with segments and transcripts
 */
final class Video extends Entity
{
    public function __construct(
        EntityId $id,
        string $title,
        string $slug,
        private readonly ?string $author = null,
        private readonly ?int $durationSeconds = null,
        private readonly ?string $format = null,
        private readonly ?string $thumbnailPath = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
        ?\DateTimeImmutable $deletedAt = null
    ) {
        parent::__construct($id, self::typeName(), $title, $slug, $createdAt, $updatedAt, $deletedAt);
    }

    public static function typeName(): string
    {
        return 'video';
    }

    public function author(): ?string
    {
        return $this->author;
    }

    public function durationSeconds(): ?int
    {
        return $this->durationSeconds;
    }

    public function format(): ?string
    {
        return $this->format;
    }

    public function thumbnailPath(): ?string
    {
        return $this->thumbnailPath;
    }

    public function updateAuthor(?string $author): void
    {
        $this->author = $author;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateDuration(?int $durationSeconds): void
    {
        $this->durationSeconds = $durationSeconds;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateFormat(?string $format): void
    {
        $this->format = $format;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateThumbnailPath(?string $thumbnailPath): void
    {
        $this->thumbnailPath = $thumbnailPath;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
