<?php

declare(strict_types=1);

namespace App\Application\UseCase\Entity;

use App\Domain\Entity\Entity;
use App\Domain\Repository\EntityRepositoryInterface;
use App\Domain\ValueObject\EntityId;

/**
 * Use case for listing entities.
 * 
 * @label PROPOSED - Part of Application Layer (Phase 3)
 */
final class ListEntities
{
    public function __construct(
        private EntityRepositoryInterface $repository
    ) {
    }

    /**
     * List all entities with optional pagination.
     * 
     * @param int $offset
     * @param int $limit
     * @return array<Entity>
     */
    public function execute(int $offset = 0, int $limit = 100): array
    {
        return $this->repository->findAll($offset, $limit);
    }

    /**
     * List entities by type.
     * 
     * @param string $type
     * @return array<Entity>
     */
    public function byType(string $type): array
    {
        return $this->repository->findByType($type);
    }
}
