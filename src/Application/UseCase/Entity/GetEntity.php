<?php

declare(strict_types=1);

namespace App\Application\UseCase\Entity;

use App\Domain\Entity\Entity;
use App\Domain\Repository\EntityRepositoryInterface;
use App\Domain\ValueObject\EntityId;

/**
 * Use case for getting an entity by ID.
 * 
 * @label PROPOSED - Part of Application Layer (Phase 3)
 */
final class GetEntity
{
    public function __construct(
        private EntityRepositoryInterface $repository
    ) {
    }

    public function execute(EntityId $id): ?Entity
    {
        return $this->repository->findById($id);
    }

    public function getBySlug(string $slug): ?Entity
    {
        return $this->repository->findBySlug($slug);
    }
}
