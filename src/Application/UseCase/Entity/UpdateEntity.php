<?php

declare(strict_types=1);

namespace App\Application\UseCase\Entity;

use App\Domain\Repository\EntityRepositoryInterface;
use App\Domain\ValueObject\EntityId;

/**
 * Use case for updating an entity.
 * 
 * @label PROPOSED - Part of Application Layer (Phase 3)
 */
final class UpdateEntity
{
    public function __construct(
        private EntityRepositoryInterface $repository
    ) {
    }

    public function execute(\App\Domain\Entity\Entity $entity): void
    {
        $existing = $this->repository->findById($entity->id());
        
        if ($existing === null) {
            throw new \RuntimeException("Entity with ID {$entity->id()->value()} not found");
        }
        
        $this->repository->save($entity);
    }
}
