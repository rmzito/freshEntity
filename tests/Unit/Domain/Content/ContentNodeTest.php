<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Content;

use App\Domain\Content\ContentNode;
use App\Domain\Content\ContentNodeType;
use App\Domain\ValueObject\ContentNodeId;
use App\Domain\ValueObject\EntityId;
use PHPUnit\Framework\TestCase;

/**
 * @label VERIFIED
 */
final class ContentNodeTest extends TestCase
{
    public function test_it_can_be_created(): void
    {
        $id = new ContentNodeId('node-123');
        $entityId = new EntityId('book-123');
        $node = new ContentNode($id, $entityId, ContentNodeType::CHAPTER, 'Chapter 1', 'Content here', 1);
        
        $this->assertEquals('node-123', $node->id()->value());
        $this->assertEquals('book-123', $node->entityId()->value());
        $this->assertSame(ContentNodeType::CHAPTER, $node->type());
        $this->assertSame('Chapter 1', $node->title());
        $this->assertSame('Content here', $node->content());
        $this->assertSame(1, $node->order());
        $this->assertNull($node->parentId());
    }

    public function test_it_can_be_created_with_parent(): void
    {
        $id = new ContentNodeId('node-123');
        $entityId = new EntityId('book-123');
        $parentId = new ContentNodeId('parent-456');
        $node = new ContentNode($id, $entityId, ContentNodeType::SECTION, 'Section 1', 'Content', 1, $parentId);
        
        $this->assertTrue($node->parentId()->equals($parentId));
    }

    public function test_it_can_update_title(): void
    {
        $id = new ContentNodeId('node-123');
        $entityId = new EntityId('book-123');
        $node = new ContentNode($id, $entityId, ContentNodeType::CHAPTER, 'Chapter 1', 'Content', 1);
        
        $node->updateTitle('Updated Chapter');
        
        $this->assertSame('Updated Chapter', $node->title());
    }

    public function test_it_can_update_content(): void
    {
        $id = new ContentNodeId('node-123');
        $entityId = new EntityId('book-123');
        $node = new ContentNode($id, $entityId, ContentNodeType::CHAPTER, 'Chapter 1', 'Content', 1);
        
        $node->updateContent('Updated content');
        
        $this->assertSame('Updated content', $node->content());
    }

    public function test_it_can_update_order(): void
    {
        $id = new ContentNodeId('node-123');
        $entityId = new EntityId('book-123');
        $node = new ContentNode($id, $entityId, ContentNodeType::CHAPTER, 'Chapter 1', 'Content', 1);
        
        $node->updateOrder(5);
        
        $this->assertSame(5, $node->order());
    }

    public function test_it_can_mark_as_deleted(): void
    {
        $id = new ContentNodeId('node-123');
        $entityId = new EntityId('book-123');
        $node = new ContentNode($id, $entityId, ContentNodeType::CHAPTER, 'Chapter 1', 'Content', 1);
        
        $this->assertFalse($node->isDeleted());
        
        $node->markAsDeleted();
        
        $this->assertTrue($node->isDeleted());
        $this->assertNotNull($node->deletedAt());
    }

    public function test_it_can_be_restored(): void
    {
        $id = new ContentNodeId('node-123');
        $entityId = new EntityId('book-123');
        $node = new ContentNode($id, $entityId, ContentNodeType::CHAPTER, 'Chapter 1', 'Content', 1);
        $node->markAsDeleted();
        
        $node->restore();
        
        $this->assertFalse($node->isDeleted());
        $this->assertNull($node->deletedAt());
    }
}
