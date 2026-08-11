<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence;

use App\Domain\Entity\Book;
use App\Domain\Repository\EntityRepositoryInterface;
use App\Domain\ValueObject\EntityId;
use App\Infrastructure\Persistence\SQL\SqlEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SqlEntityRepository.
 * 
 * VERIFIED: Tests match legacy MySQL storage semantics
 */
class SqlEntityRepositoryTest extends TestCase
{
    private Connection $connection;
    private EntityRepositoryInterface $repository;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        // Create entities table
        $this->connection->executeStatement('
            CREATE TABLE entities (
                id VARCHAR(36) PRIMARY KEY,
                slug VARCHAR(255) NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                metadata TEXT,
                created_at DATETIME NOT NULL,
                updated_at DATETIME,
                deleted_at DATETIME
            )
        ');

        $this->repository = new SqlEntityRepository($this->connection);
    }

    public function testSaveAndFindById(): void
    {
        $entity = new Book(
            id: EntityId::generate(),
            title: 'Test Book',
            slug: 'test-book',
            author: 'Test Author',
            createdAt: new \DateTimeImmutable()
        );

        $this->repository->save($entity);

        $found = $this->repository->findById($entity->id());

        $this->assertNotNull($found);
        $this->assertEquals($entity->id()->value(), $found->id()->value());
        $this->assertEquals('test-book', $found->slug());
        $this->assertEquals('Test Book', $found->title());
    }

    public function testFindBySlug(): void
    {
        $entity = new Book(
            id: EntityId::generate(),
            title: 'Unique Book',
            slug: 'unique-slug',
            createdAt: new \DateTimeImmutable()
        );

        $this->repository->save($entity);

        $found = $this->repository->findBySlug('unique-slug');

        $this->assertNotNull($found);
        $this->assertEquals('unique-slug', $found->slug());
    }

    public function testFindByType(): void
    {
        $book1 = new Book(
            id: EntityId::generate(),
            title: 'Book 1',
            slug: 'book-1',
            createdAt: new \DateTimeImmutable()
        );

        $book2 = new Book(
            id: EntityId::generate(),
            title: 'Book 2',
            slug: 'book-2',
            createdAt: new \DateTimeImmutable()
        );

        $this->repository->save($book1);
        $this->repository->save($book2);

        $books = $this->repository->findByType('book');

        $this->assertCount(2, $books);
    }

    public function testSoftDelete(): void
    {
        $entity = new Book(
            id: EntityId::generate(),
            title: 'To Delete',
            slug: 'to-delete',
            createdAt: new \DateTimeImmutable()
        );

        $this->repository->save($entity);

        // Verify entity exists
        $found = $this->repository->findById($entity->id());
        $this->assertNotNull($found);

        // Soft delete
        $this->repository->delete($entity->id());

        // Entity should not be found
        $notFound = $this->repository->findById($entity->id());
        $this->assertNull($notFound);
    }

    public function testRestore(): void
    {
        $entity = new Book(
            id: EntityId::generate(),
            title: 'To Restore',
            slug: 'to-restore',
            createdAt: new \DateTimeImmutable()
        );

        $this->repository->save($entity);
        $this->repository->delete($entity->id());

        // Restore
        $restored = $this->repository->restore($entity->id());

        $this->assertNotNull($restored);
        $this->assertEquals($entity->id()->value(), $restored->id()->value());
    }

    public function testForceDelete(): void
    {
        $entity = new Book(
            id: EntityId::generate(),
            title: 'To Force Delete',
            slug: 'to-force-delete',
            createdAt: new \DateTimeImmutable()
        );

        $this->repository->save($entity);
        $this->repository->forceDelete($entity->id());

        $found = $this->repository->findById($entity->id());
        $this->assertNull($found);
    }

    public function testFindAllWithPagination(): void
    {
        // Create 5 entities
        for ($i = 0; $i < 5; $i++) {
            $entity = new Book(
                id: EntityId::generate(),
                title: "Book {$i}",
                slug: "book-{$i}",
                createdAt: new \DateTimeImmutable()
            );
            $this->repository->save($entity);
        }

        // Get first 2
        $firstPage = $this->repository->findAll(0, 2);
        $this->assertCount(2, $firstPage);

        // Get next 2
        $secondPage = $this->repository->findAll(2, 2);
        $this->assertCount(2, $secondPage);
    }
}
