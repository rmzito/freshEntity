<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Content\ContentNode;
use App\Domain\ValueObject\ContentNodeId;
use App\Domain\ValueObject\EntityId;

/**
 * Repository interface for ContentNode persistence.
 * 
 * This interface abstracts the persistence layer from the Domain.
 * Implementations can use SQL, MongoDB, or any other storage mechanism.
 */
interface ContentNodeRepositoryInterface
{
    /**
     * Find a content node by its ID.
     * 
     * @param ContentNodeId $id
     * @return ContentNode|null
     */
    public function findById(ContentNodeId $id): ?ContentNode;

    /**
     * Find all content nodes for a specific entity.
     * 
     * @param EntityId $entityId
     * @return array<ContentNode>
     */
    public function findByEntityId(EntityId $entityId): array;

    /**
     * Find a content node by its path within an entity.
     * 
     * @param EntityId $entityId
     * @param string $path
     * @return ContentNode|null
     */
    public function findByPath(EntityId $entityId, string $path): ?ContentNode;

    /**
     * Find all child nodes of a parent node.
     * 
     * @param ContentNodeId $parentId
     * @return array<ContentNode>
     */
    public function findChildren(ContentNodeId $parentId): array;

    /**
     * Save a content node (create or update).
     * 
     * @param ContentNode $node
     * @return void
     */
    public function save(ContentNode $node): void;

    /**
     * Delete a content node (soft delete).
     * 
     * @param ContentNodeId $id
     * @return void
     */
    public function delete(ContentNodeId $id): void;

    /**
     * Permanently delete a content node (hard delete).
     * 
     * @param ContentNodeId $id
     * @return void
     */
    public function forceDelete(ContentNodeId $id): void;

    /**
     * Restore a soft-deleted content node.
     * 
     * @param ContentNodeId $id
     * @return ContentNode|null
     */
    public function restore(ContentNodeId $id): ?ContentNode;

    /**
     * Reorder content nodes within a parent.
     * 
     * @param ContentNodeId $parentId
     * @param array<ContentNodeId> $orderedIds
     * @return void
     */
    public function reorder(ContentNodeId $parentId, array $orderedIds): void;

    /**
     * Move a content node to a new parent.
     * 
     * @param ContentNodeId $nodeId
     * @param ContentNodeId|null $newParentId
     * @param int $position
     * @return void
     */
    public function move(ContentNodeId $nodeId, ?ContentNodeId $newParentId, int $position): void;
}
