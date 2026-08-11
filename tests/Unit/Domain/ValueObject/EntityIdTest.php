<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\EntityId;
use PHPUnit\Framework\TestCase;

/**
 * @label VERIFIED
 */
final class EntityIdTest extends TestCase
{
    public function test_it_can_be_created_with_valid_value(): void
    {
        $id = new EntityId('test-123');
        
        $this->assertSame('test-123', $id->value());
    }

    public function test_it_throws_exception_for_empty_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EntityId cannot be empty');
        
        new EntityId('');
    }

    public function test_it_can_check_equality(): void
    {
        $id1 = new EntityId('test-123');
        $id2 = new EntityId('test-123');
        $id3 = new EntityId('test-456');
        
        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }

    public function test_it_can_be_cast_to_string(): void
    {
        $id = new EntityId('test-123');
        
        $this->assertSame('test-123', (string) $id);
    }
}
