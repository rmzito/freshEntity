<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Entity;

use App\Application\UseCase\Entity\CreateEntity;
use App\Domain\Entity\Book;
use App\Domain\Repository\EntityRepositoryInterface;
use App\Domain\ValueObject\EntityId;
use PHPUnit\Framework\TestCase;

/**
 * @label PROPOSED - Test for Application Layer (Phase 3)
 */
final class CreateEntityTest extends TestCase
{
    private EntityRepositoryInterface $repository;
    private CreateEntity $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepositoryInterface::class);
        $this->useCase = new CreateEntity($this->repository);
    }

    public function testExecuteSavesEntity(): void
    {
        $entity = new Book(
            EntityId::generate(),
            'Test Book',
            'test-book',
            'Test description'
        );

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->equalTo($entity));

        $this->useCase->execute($entity);
    }
}
