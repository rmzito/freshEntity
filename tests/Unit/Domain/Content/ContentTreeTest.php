<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Content;

use App\Domain\Content\ContentNode;
use App\Domain\Content\ContentNodeType;
use App\Domain\Content\ContentTree;
use App\Domain\ValueObject\ContentNodeId;
use App\Domain\ValueObject\EntityId;
use PHPUnit\Framework\TestCase;

/**
 * @label VERIFIED
 */
final class ContentTreeTest extends TestCase
{
    public function test_it_can_be_created_with_nodes(): void
    {
        $node1 = $this->createNode('node-1', 'book-1', ContentNodeType::CHAPTER, 'Chapter 1', 1);
        $node2 = $this->createNode('node-2', 'book-1', ContentNodeType::SECTION, 'Section 1', 1, 'node-1');
        
        $tree = new ContentTree([$node1, $node2]);
        
        $this->assertCount(2, $tree->nodes());
    }

    public function test_it_returns_root_nodes(): void
    {
        $node1 = $this->createNode('node-1', 'book-1', ContentNodeType::CHAPTER, 'Chapter 1', 1);
        $node2 = $this->createNode('node-2', 'book-1', ContentNodeType::SECTION, 'Section 1', 1, 'node-1');
        
        $tree = new ContentTree([$node1, $node2]);
        $roots = $tree->roots();
        
        $this->assertCount(1, $roots);
        $this->assertEquals('node-1', $roots[0]->id()->value());
    }

    public function test_it_returns_children_of_node(): void
    {
        $node1 = $this->createNode('node-1', 'book-1', ContentNodeType::CHAPTER, 'Chapter 1', 1);
        $node2 = $this->createNode('node-2', 'book-1', ContentNodeType::SECTION, 'Section 1', 1, 'node-1');
        $node3 = $this->createNode('node-3', 'book-1', ContentNodeType::SECTION, 'Section 2', 2, 'node-1');
        
        $tree = new ContentTree([$node1, $node2, $node3]);
        $children = $tree->children(new ContentNodeId('node-1'));
        
        $this->assertCount(2, $children);
    }

    public function test_it_gets_node_by_id(): void
    {
        $node1 = $this->createNode('node-1', 'book-1', ContentNodeType::CHAPTER, 'Chapter 1', 1);
        $node2 = $this->createNode('node-2', 'book-1', ContentNodeType::SECTION, 'Section 1', 1, 'node-1');
        
        $tree = new ContentTree([$node1, $node2]);
        
        $found = $tree->getNode(new ContentNodeId('node-2'));
        
        $this->assertNotNull($found);
        $this->assertSame('Section 1', $found->title());
    }

    public function test_it_returns_null_for_non_existent_node(): void
    {
        $node1 = $this->createNode('node-1', 'book-1', ContentNodeType::CHAPTER, 'Chapter 1', 1);
        
        $tree = new ContentTree([$node1]);
        
        $found = $tree->getNode(new ContentNodeId('non-existent'));
        
        $this->assertNull($found);
    }

    public function test_it_builds_hierarchy(): void
    {
        $node1 = $this->createNode('node-1', 'book-1', ContentNodeType::CHAPTER, 'Chapter 1', 1);
        $node2 = $this->createNode('node-2', 'book-1', ContentNodeType::SECTION, 'Section 1', 1, 'node-1');
        
        $tree = new ContentTree([$node1, $node2]);
        $hierarchy = $tree->toHierarchy();
        
        $this->assertCount(1, $hierarchy);
        $this->assertSame('Chapter 1', $hierarchy[0]['title']);
        $this->assertCount(1, $hierarchy[0]['children']);
        $this->assertSame('Section 1', $hierarchy[0]['children'][0]['title']);
    }

    public function test_it_excludes_deleted_nodes_from_roots(): void
    {
        $node1 = $this->createNode('node-1', 'book-1', ContentNodeType::CHAPTER, 'Chapter 1', 1);
        $node1->markAsDeleted();
        
        $tree = new ContentTree([$node1]);
        $roots = $tree->roots();
        
        $this->assertCount(0, $roots);
    }

    private function createNode(
        string $id,
        string $entityId,
        ContentNodeType $type,
        string $title,
        int $order,
        ?string $parentId = null
    ): ContentNode {
        return new ContentNode(
            new ContentNodeId($id),
            new EntityId($entityId),
            $type,
            $title,
            'Content',
            $order,
            $parentId !== null ? new ContentNodeId($parentId) : null
        );
    }
}
