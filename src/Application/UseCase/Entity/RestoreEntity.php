<?php

declare(strict_types=1);

namespace App\Application\UseCase\Entity;

use App\Domain\Repository\EntityRepositoryInterface;
use App\Domain\ValueObject\EntityId;

/**
 * Use case for restoring a deleted entity.
 * 
 * @label PROPOSED - Part of Application Layer (Phase 3)
 */
final class RestoreEntity
{
    public function __construct(
        private EntityRepositoryInterface $repository
    ) {
    }

    public function execute(EntityId $id): ?\App\Domain\Entity\Entity
    {
        return $this->repository->restore($id);
    }
}
