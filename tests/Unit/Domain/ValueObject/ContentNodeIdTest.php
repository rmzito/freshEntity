<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\ContentNodeId;
use PHPUnit\Framework\TestCase;

/**
 * @label VERIFIED
 */
final class ContentNodeIdTest extends TestCase
{
    public function test_it_can_be_created_with_valid_value(): void
    {
        $id = new ContentNodeId('node-123');
        
        $this->assertSame('node-123', $id->value());
    }

    public function test_it_throws_exception_for_empty_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ContentNodeId cannot be empty');
        
        new ContentNodeId('');
    }

    public function test_it_can_check_equality(): void
    {
        $id1 = new ContentNodeId('node-123');
        $id2 = new ContentNodeId('node-123');
        $id3 = new ContentNodeId('node-456');
        
        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }

    public function test_it_can_be_cast_to_string(): void
    {
        $id = new ContentNodeId('node-123');
        
        $this->assertSame('node-123', (string) $id);
    }
}
