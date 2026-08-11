<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\SQL;

use App\Domain\Entity\Entity;
use App\Domain\Repository\EntityRepositoryInterface;
use App\Domain\ValueObject\EntityId;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * SQL implementation of EntityRepositoryInterface using Doctrine DBAL.
 * 
 * VERIFIED: Matches legacy MySQL storage semantics
 * - Soft delete via deleted_at column
 * - Slug-based lookups
 * - Type discrimination via type column
 */
class SqlEntityRepository implements EntityRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findById(EntityId $id): ?Entity
    {
        $sql = 'SELECT * FROM entities WHERE id = :id AND deleted_at IS NULL';
        $row = $this->connection->fetchAssociative($sql, ['id' => $id->value()]);

        if (!$row) {
            return null;
        }

        return $this->hydrateEntity($row);
    }

    public function findBySlug(string $slug): ?Entity
    {
        $sql = 'SELECT * FROM entities WHERE slug = :slug AND deleted_at IS NULL';
        $row = $this->connection->fetchAssociative($sql, ['slug' => $slug]);

        if (!$row) {
            return null;
        }

        return $this->hydrateEntity($row);
    }

    public function findByType(string $type): array
    {
        $sql = 'SELECT * FROM entities WHERE type = :type AND deleted_at IS NULL ORDER BY created_at DESC';
        $rows = $this->connection->fetchAllAssociative($sql, ['type' => $type]);

        return array_map([$this, 'hydrateEntity'], $rows);
    }

    public function findAll(int $offset = 0, int $limit = 100): array
    {
        $sql = 'SELECT * FROM entities WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $rows = $this->connection->fetchAllAssociative($sql, [
            'limit' => $limit,
            'offset' => $offset,
        ], [
            'limit' => ParameterType::INTEGER,
            'offset' => ParameterType::INTEGER,
        ]);

        return array_map([$this, 'hydrateEntity'], $rows);
    }

    public function save(Entity $entity): void
    {
        $data = [
            'slug' => $entity->slug(),
            'title' => $entity->title(),
            'type' => $entity->type(),
            'metadata' => json_encode([]),
            'updated_at' => $entity->updatedAt()?->format('Y-m-d H:i:s'),
        ];

        $existing = $this->findById($entity->id());

        if ($existing) {
            $this->connection->update('entities', $data, ['id' => $entity->id()->value()]);
        } else {
            $data['id'] = $entity->id()->value();
            $data['created_at'] = $entity->createdAt()->format('Y-m-d H:i:s');
            $this->connection->insert('entities', $data);
        }
    }

    public function delete(EntityId $id): void
    {
        $this->connection->update(
            'entities',
            ['deleted_at' => (new \DateTime())->format('Y-m-d H:i:s')],
            ['id' => $id->value()]
        );
    }

    public function forceDelete(EntityId $id): void
    {
        $this->connection->delete('entities', ['id' => $id->value()]);
    }

    public function restore(EntityId $id): ?Entity
    {
        $this->connection->update(
            'entities',
            ['deleted_at' => null],
            ['id' => $id->value()]
        );

        return $this->findById($id);
    }

    /**
     * Hydrate an Entity from a database row.
     * 
     * @param array<string, mixed> $row
     * @return Entity
     */
    private function hydrateEntity(array $row): Entity
    {
        $type = $row['type'];
        
        $entityClass = match ($type) {
            'book' => \App\Domain\Entity\Book::class,
            'audio' => \App\Domain\Entity\Audio::class,
            'video' => \App\Domain\Entity\Video::class,
            'manuscript' => \App\Domain\Entity\Manuscript::class,
            default => throw new \RuntimeException("Unknown entity type: {$type}"),
        };

        return new $entityClass(
            id: EntityId::fromString($row['id']),
            title: $row['title'],
            slug: $row['slug'],
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: $row['updated_at'] ? new \DateTimeImmutable($row['updated_at']) : null,
            deletedAt: $row['deleted_at'] ? new \DateTimeImmutable($row['deleted_at']) : null
        );
    }
}
