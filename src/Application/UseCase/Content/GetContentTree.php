<?php

declare(strict_types=1);

namespace App\Application\UseCase\Content;

use App\Domain\Repository\ContentNodeRepositoryInterface;
use App\Domain\ValueObject\ContentNodeId;
use App\Domain\Content\ContentTree;

/**
 * Use case for getting the content tree of an entity.
 * 
 * @label PROPOSED - Part of Application Layer (Phase 3)
 */
final class GetContentTree
{
    public function __construct(
        private ContentNodeRepositoryInterface $repository
    ) {
    }

    /**
     * Get the full content tree for an entity.
     * 
     * @param ContentNodeId|null $rootId The root node ID (null for all roots)
     * @return ContentTree
     */
    public function execute(?ContentNodeId $rootId = null): ContentTree
    {
        return $this->repository->getTree($rootId);
    }
}
