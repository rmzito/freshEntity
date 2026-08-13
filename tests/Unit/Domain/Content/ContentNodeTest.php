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
        $node = new ContentNode(
            id: $id,
            entityId: $entityId,
            type: ContentNodeType::CHAPTER,
            title: 'Chapter 1',
            content: 'Content here',
            metadata: [],
            path: '/chapter-1',
            position: 1
        );
        
        $this->assertEquals('node-123', $node->getId()->value());
        $this->assertEquals('book-123', $node->getEntityId()->value());
        $this->assertSame(ContentNodeType::CHAPTER, $node->getType());
        $this->assertSame('Chapter 1', $node->getTitle());
        $this->assertSame('Content here', $node->getContent());
        $this->assertSame('/chapter-1', $node->getPath());
        $this->assertSame(1, $node->getPosition());
        $this->assertNull($node->getParentId());
    }

    public function test_it_can_be_created_with_parent(): void
    {
        $id = new ContentNodeId('node-123');
        $entityId = new EntityId('book-123');
        $parentId = new ContentNodeId('parent-456');
        $node = new ContentNode(
            id: $id,
            entityId: $entityId,
            type: ContentNodeType::SECTION,
            title: 'Section 1',
            content: 'Content',
            metadata: [],
            path: '/section-1',
            position: 1,
            parentId: $parentId
        );
        
        $this->assertTrue($node->getParentId()->equals($parentId));
    }

    public function test_it_can_update_title(): void
    {
        $id = new ContentNodeId('node-123');
        $entityId = new EntityId('book-123');
        $node = new ContentNode(
            id: $id,
            entityId: $entityId,
            type: ContentNodeType::CHAPTER,
            title: 'Chapter 1',
            content: 'Content',
            metadata: [],
            path: '/chapter-1',
            position: 1
        );
        
        $node->updateTitle('Updated Chapter');
        
        $this->assertSame('Updated Chapter', $node->getTitle());
    }

    public function test_it_can_update_content(): void
    {
        $id = new ContentNodeId('node-123');
        $entityId = new EntityId('book-123');
        $node = new ContentNode(
            id: $id,
            entityId: $entityId,
            type: ContentNodeType::CHAPTER,
            title: 'Chapter 1',
            content: 'Content',
            metadata: [],
            path: '/chapter-1',
            position: 1
        );
        
        $node->updateContent('Updated content');
        
        $this->assertSame('Updated content', $node->getContent());
    }

    public function test_it_can_update_metadata(): void
    {
        $id = new ContentNodeId('node-123');
        $entityId = new EntityId('book-123');
        $node = new ContentNode(
            id: $id,
            entityId: $entityId,
            type: ContentNodeType::CHAPTER,
            title: 'Chapter 1',
            content: 'Content',
            metadata: [],
            path: '/chapter-1',
            position: 1
        );
        
        $metadata = ['key' => 'value'];
        $node->updateMetadata($metadata);
        
        $this->assertSame($metadata, $node->getMetadata());
    }

    public function test_it_can_update_path(): void
    {
        $id = new ContentNodeId('node-123');
        $entityId = new EntityId('book-123');
        $node = new ContentNode(
            id: $id,
            entityId: $entityId,
            type: ContentNodeType::CHAPTER,
            title: 'Chapter 1',
            content: 'Content',
            metadata: [],
            path: '/chapter-1',
            position: 1
        );
        
        $node->updatePath('/updated/chapter-1');
        
        $this->assertSame('/updated/chapter-1', $node->getPath());
    }

    public function test_it_can_update_position(): void
    {
        $id = new ContentNodeId('node-123');
        $entityId = new EntityId('book-123');
        $node = new ContentNode(
            id: $id,
            entityId: $entityId,
            type: ContentNodeType::CHAPTER,
            title: 'Chapter 1',
            content: 'Content',
            metadata: [],
            path: '/chapter-1',
            position: 1
        );
        
        $node->updatePosition(5);
        
        $this->assertSame(5, $node->getPosition());
    }

    public function test_it_can_mark_as_deleted(): void
    {
        $id = new ContentNodeId('node-123');
        $entityId = new EntityId('book-123');
        $node = new ContentNode(
            id: $id,
            entityId: $entityId,
            type: ContentNodeType::CHAPTER,
            title: 'Chapter 1',
            content: 'Content',
            metadata: [],
            path: '/chapter-1',
            position: 1
        );
        
        $this->assertFalse($node->isDeleted());
        
        $node->markAsDeleted();
        
        $this->assertTrue($node->isDeleted());
        $this->assertNotNull($node->getDeletedAt());
    }

    public function test_it_can_be_restored(): void
    {
        $id = new ContentNodeId('node-123');
        $entityId = new EntityId('book-123');
        $node = new ContentNode(
            id: $id,
            entityId: $entityId,
            type: ContentNodeType::CHAPTER,
            title: 'Chapter 1',
            content: 'Content',
            metadata: [],
            path: '/chapter-1',
            position: 1
        );
        $node->markAsDeleted();
        
        $node->restore();
        
        $this->assertFalse($node->isDeleted());
        $this->assertNull($node->getDeletedAt());
    }
}
