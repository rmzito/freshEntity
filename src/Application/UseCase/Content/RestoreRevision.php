<?php

declare(strict_types=1);

namespace App\Application\UseCase\Content;

use App\Domain\Repository\ContentNodeRepositoryInterface;
use App\Domain\ValueObject\ContentNodeId;

/**
 * Use case for restoring a specific revision of a content node.
 * 
 * @label PROPOSED - Part of Application Layer (Phase 3)
 */
final class RestoreRevision
{
    public function __construct(
        private ContentNodeRepositoryInterface $repository
    ) {
    }

    /**
     * Restore a specific revision of a content node.
     * 
     * @param ContentNodeId $revisionId The ID of the revision to restore
     * @return ContentNodeId The ID of the restored node
     */
    public function execute(ContentNodeId $revisionId): ContentNodeId
    {
        return $this->repository->restoreRevision($revisionId);
    }
}
