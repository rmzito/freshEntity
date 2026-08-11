<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Entity;
use App\Domain\ValueObject\EntityId;

/**
 * Repository interface for Entity persistence.
 * 
 * This interface abstracts the persistence layer from the Domain.
 * Implementations can use SQL, MongoDB, or any other storage mechanism.
 */
interface EntityRepositoryInterface
{
    /**
     * Find an entity by its ID.
     * 
     * @param EntityId $id
     * @return Entity|null
     */
    public function findById(EntityId $id): ?Entity;

    /**
     * Find an entity by its slug.
     * 
     * @param string $slug
     * @return Entity|null
     */
    public function findBySlug(string $slug): ?Entity;

    /**
     * Find all entities of a specific type.
     * 
     * @param string $type
     * @return array<Entity>
     */
    public function findByType(string $type): array;

    /**
     * Find all entities (with optional pagination).
     * 
     * @param int $offset
     * @param int $limit
     * @return array<Entity>
     */
    public function findAll(int $offset = 0, int $limit = 100): array;

    /**
     * Save an entity (create or update).
     * 
     * @param Entity $entity
     * @return void
     */
    public function save(Entity $entity): void;

    /**
     * Delete an entity (soft delete).
     * 
     * @param EntityId $id
     * @return void
     */
    public function delete(EntityId $id): void;

    /**
     * Permanently delete an entity (hard delete).
     * 
     * @param EntityId $id
     * @return void
     */
    public function forceDelete(EntityId $id): void;

    /**
     * Restore a soft-deleted entity.
     * 
     * @param EntityId $id
     * @return Entity|null
     */
    public function restore(EntityId $id): ?Entity;
}
