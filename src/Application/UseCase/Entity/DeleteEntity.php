<?php

declare(strict_types=1);

namespace App\Application\UseCase\Entity;

use App\Domain\Repository\EntityRepositoryInterface;
use App\Domain\ValueObject\EntityId;

/**
 * Use case for deleting an entity (soft delete).
 * 
 * @label PROPOSED - Part of Application Layer (Phase 3)
 */
final class DeleteEntity
{
    public function __construct(
        private EntityRepositoryInterface $repository
    ) {
    }

    public function execute(EntityId $id): void
    {
        $this->repository->delete($id);
    }
}
