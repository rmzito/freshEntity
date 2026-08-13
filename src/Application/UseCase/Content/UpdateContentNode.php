<?php

declare(strict_types=1);

namespace App\Application\UseCase\Content;

use App\Domain\Content\ContentNode;
use App\Domain\Repository\ContentNodeRepositoryInterface;

/**
 * Use case for updating a content node.
 * 
 * @label PROPOSED - Part of Application Layer (Phase 3)
 */
final class UpdateContentNode
{
    public function __construct(
        private ContentNodeRepositoryInterface $repository
    ) {
    }

    public function execute(ContentNode $node): void
    {
        $this->repository->save($node);
    }
}
