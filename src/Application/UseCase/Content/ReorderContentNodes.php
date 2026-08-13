<?php

declare(strict_types=1);

namespace App\Application\UseCase\Content;

use App\Domain\Repository\ContentNodeRepositoryInterface;
use App\Domain\ValueObject\ContentNodeId;

/**
 * Use case for reordering content nodes within a parent.
 * 
 * @label PROPOSED - Part of Application Layer (Phase 3)
 */
final class ReorderContentNodes
{
    public function __construct(
        private ContentNodeRepositoryInterface $repository
    ) {
    }

    /**
     * Reorder content nodes within a parent.
     * 
     * @param ContentNodeId|null $parentId The ID of the parent (null for root level)
     * @param ContentNodeId[] $orderedIds The ordered list of node IDs
     */
    public function execute(?ContentNodeId $parentId, array $orderedIds): void
    {
        $this->repository->reorder($parentId, $orderedIds);
    }
}
