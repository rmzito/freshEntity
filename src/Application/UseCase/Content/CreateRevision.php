<?php

declare(strict_types=1);

namespace App\Application\UseCase\Content;

use App\Domain\Repository\ContentNodeRepositoryInterface;
use App\Domain\ValueObject\ContentNodeId;

/**
 * Use case for creating a revision of a content node.
 * 
 * @label PROPOSED - Part of Application Layer (Phase 3)
 */
final class CreateRevision
{
    public function __construct(
        private ContentNodeRepositoryInterface $repository
    ) {
    }

    /**
     * Create a new revision for a content node.
     * 
     * @param ContentNodeId $nodeId The ID of the node to create a revision for
     * @param string|null $comment Optional comment for this revision
     * @return ContentNodeId The ID of the new revision
     */
    public function execute(ContentNodeId $nodeId, ?string $comment = null): ContentNodeId
    {
        return $this->repository->createRevision($nodeId, $comment);
    }
}
