<?php

declare(strict_types=1);

namespace App\Application\UseCase\Content;

use App\Domain\Repository\ContentNodeRepositoryInterface;
use App\Domain\ValueObject\ContentNodeId;

/**
 * Use case for getting a content node by ID.
 * 
 * @label PROPOSED - Part of Application Layer (Phase 3)
 */
final class GetContentNode
{
    public function __construct(
        private ContentNodeRepositoryInterface $repository
    ) {
    }

    public function execute(ContentNodeId $id): ?ContentNode
    {
        return $this->repository->findById($id);
    }
}
