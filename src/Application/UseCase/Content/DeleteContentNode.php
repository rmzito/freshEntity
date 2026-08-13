<?php

declare(strict_types=1);

namespace App\Application\UseCase\Content;

use App\Domain\Repository\ContentNodeRepositoryInterface;
use App\Domain\ValueObject\ContentNodeId;

/**
 * Use case for deleting a content node.
 * 
 * @label PROPOSED - Part of Application Layer (Phase 3)
 */
final class DeleteContentNode
{
    public function __construct(
        private ContentNodeRepositoryInterface $repository
    ) {
    }

    public function execute(ContentNodeId $id): void
    {
        $this->repository->delete($id);
    }
}
