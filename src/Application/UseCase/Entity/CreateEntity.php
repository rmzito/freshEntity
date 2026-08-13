<?php

declare(strict_types=1);

namespace App\Application\UseCase\Entity;

use App\Domain\Entity\Entity;
use App\Domain\Repository\EntityRepositoryInterface;
use App\Domain\ValueObject\EntityId;

/**
 * Use case for creating an entity.
 * 
 * @label PROPOSED - Part of Application Layer (Phase 3)
 */
final class CreateEntity
{
    public function __construct(
        private EntityRepositoryInterface $repository
    ) {
    }

    public function execute(Entity $entity): void
    {
        $this->repository->save($entity);
    }
}
