<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Entity;

use App\Application\UseCase\Entity\GetEntity;
use App\Domain\Entity\Book;
use App\Domain\Repository\EntityRepositoryInterface;
use App\Domain\ValueObject\EntityId;
use PHPUnit\Framework\TestCase;

/**
 * @label PROPOSED - Test for Application Layer (Phase 3)
 */
final class GetEntityTest extends TestCase
{
    private EntityRepositoryInterface $repository;
    private GetEntity $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepositoryInterface::class);
        $this->useCase = new GetEntity($this->repository);
    }

    public function testExecuteReturnsEntityWhenFound(): void
    {
        $id = EntityId::generate();
        $entity = new Book($id, 'Test Book', 'test-book', 'Test description');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($entity);

        $result = $this->useCase->execute($id);

        $this->assertSame($entity, $result);
    }

    public function testExecuteReturnsNullWhenNotFound(): void
    {
        $id = EntityId::generate();

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn(null);

        $result = $this->useCase->execute($id);

        $this->assertNull($result);
    }

    public function testGetBySlugReturnsEntityWhenFound(): void
    {
        $entity = new Book(EntityId::generate(), 'Test Book', 'test-book', 'Test description');

        $this->repository
            ->expects($this->once())
            ->method('findBySlug')
            ->with('test-book')
            ->willReturn($entity);

        $result = $this->useCase->getBySlug('test-book');

        $this->assertSame($entity, $result);
    }
}
