<?php

declare(strict_types=1);

namespace App\Application\UseCase\Content;

use App\Domain\Repository\ContentNodeRepositoryInterface;
use App\Domain\ValueObject\ContentNodeId;

/**
 * Use case for moving a content node to a new parent.
 * 
 * @label PROPOSED - Part of Application Layer (Phase 3)
 */
final class MoveContentNode
{
    public function __construct(
        private ContentNodeRepositoryInterface $repository
    ) {
    }

    /**
     * Move a content node to a new parent.
     * 
     * @param ContentNodeId $nodeId The ID of the node to move
     * @param ContentNodeId|null $newParentId The ID of the new parent (null for root)
     * @param int|null $position The position in the new parent (null for append)
     */
    public function execute(ContentNodeId $nodeId, ?ContentNodeId $newParentId = null, ?int $position = null): void
    {
        $this->repository->move($nodeId, $newParentId, $position);
    }
}
