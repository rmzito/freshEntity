<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Book;
use App\Domain\ValueObject\EntityId;
use PHPUnit\Framework\TestCase;

/**
 * @label VERIFIED
 */
final class BookTest extends TestCase
{
    public function test_it_can_be_created(): void
    {
        $id = new EntityId('book-123');
        $book = new Book($id, 'Test Book', 'test-book', 'Test Author', 'Test Series', 1);
        
        $this->assertEquals('book-123', $book->id()->value());
        $this->assertSame('book', $book->type());
        $this->assertSame('Test Book', $book->title());
        $this->assertSame('test-book', $book->slug());
        $this->assertSame('Test Author', $book->author());
        $this->assertSame('Test Series', $book->series());
        $this->assertSame(1, $book->seriesOrder());
    }

    public function test_it_can_update_title(): void
    {
        $id = new EntityId('book-123');
        $book = new Book($id, 'Test Book', 'test-book');
        
        $book->updateTitle('Updated Title');
        
        $this->assertSame('Updated Title', $book->title());
    }

    public function test_it_can_update_slug(): void
    {
        $id = new EntityId('book-123');
        $book = new Book($id, 'Test Book', 'test-book');
        
        $book->updateSlug('updated-slug');
        
        $this->assertSame('updated-slug', $book->slug());
    }

    public function test_it_can_mark_as_deleted(): void
    {
        $id = new EntityId('book-123');
        $book = new Book($id, 'Test Book', 'test-book');
        
        $this->assertFalse($book->isDeleted());
        
        $book->markAsDeleted();
        
        $this->assertTrue($book->isDeleted());
        $this->assertNotNull($book->deletedAt());
    }

    public function test_it_can_be_restored(): void
    {
        $id = new EntityId('book-123');
        $book = new Book($id, 'Test Book', 'test-book');
        $book->markAsDeleted();
        
        $book->restore();
        
        $this->assertFalse($book->isDeleted());
        $this->assertNull($book->deletedAt());
    }

    public function test_type_name_is_book(): void
    {
        $this->assertSame('book', Book::typeName());
    }
}
