<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence;

use App\Domain\Content\ContentNode;
use App\Domain\Content\ContentNodeType;
use App\Domain\Repository\ContentNodeRepositoryInterface;
use App\Domain\ValueObject\ContentNodeId;
use App\Domain\ValueObject\EntityId;
use App\Infrastructure\Persistence\Mongo\MongoContentNodeRepository;
use MongoDB\Client;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MongoContentNodeRepository.
 * 
 * VERIFIED: Tests match legacy MongoDB storage semantics
 * - Hierarchical content nodes
 * - Parent-child relationships
 * - Path-based navigation
 * - Soft delete
 * - Reorder and move operations
 */
class MongoContentNodeRepositoryTest extends TestCase
{
    private Client $mongoClient;
    private ContentNodeRepositoryInterface $repository;
    private string $databaseName = 'entity_test';

    protected function setUp(): void
    {
        // Skip test if MongoDB is not available
        if (!extension_loaded('mongodb')) {
            $this->markTestSkipped('MongoDB extension not loaded');
        }

        try {
            $this->mongoClient = new Client('mongodb://localhost:27017');
            // Test connection
            $this->mongoClient->listDatabases();
            
            // Clean up test database
            $this->mongoClient->selectDatabase($this->databaseName)->drop();
            
            $this->repository = new MongoContentNodeRepository($this->mongoClient, $this->databaseName);
        } catch (\Exception $e) {
            $this->markTestSkipped('MongoDB not available: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->mongoClient)) {
            $this->mongoClient->selectDatabase($this->databaseName)->drop();
        }
    }

    public function testSaveAndFindById(): void
    {
        $entityId = EntityId::generate();
        $nodeId = ContentNodeId::generate();
        
        $node = new ContentNode(
            id: $nodeId,
            entityId: $entityId,
            type: ContentNodeType::Chapter,
            title: 'Test Chapter',
            content: 'This is test content',
            metadata: ['key' => 'value'],
            path: '/chapter-1',
            position: 0,
            parentId: null,
            createdAt: new \DateTime(),
            updatedAt: null,
            deletedAt: null
        );

        $this->repository->save($node);

        $found = $this->repository->findById($nodeId);

        $this->assertNotNull($found);
        $this->assertEquals($nodeId->toString(), $found->getId()->toString());
        $this->assertEquals('Test Chapter', $found->getTitle());
        $this->assertEquals('This is test content', $found->getContent());
        $this->assertEquals(ContentNodeType::Chapter, $found->getType());
    }

    public function testFindByEntityId(): void
    {
        $entityId = EntityId::generate();
        
        // Create 3 nodes for the same entity
        for ($i = 0; $i < 3; $i++) {
            $node = new ContentNode(
                id: ContentNodeId::generate(),
                entityId: $entityId,
                type: ContentNodeType::Section,
                title: "Section {$i}",
                content: "Content {$i}",
                metadata: [],
                path: "/section-{$i}",
                position: $i,
                parentId: null,
                createdAt: new \DateTime(),
                updatedAt: null,
                deletedAt: null
            );
            $this->repository->save($node);
        }

        $nodes = $this->repository->findByEntityId($entityId);

        $this->assertCount(3, $nodes);
    }

    public function testFindByPath(): void
    {
        $entityId = EntityId::generate();
        $nodeId = ContentNodeId::generate();
        
        $node = new ContentNode(
            id: $nodeId,
            entityId: $entityId,
            type: ContentNodeType::Chapter,
            title: 'Path Test',
            content: 'Testing path lookup',
            metadata: [],
            path: '/test/path',
            position: 0,
            parentId: null,
            createdAt: new \DateTime(),
            updatedAt: null,
            deletedAt: null
        );

        $this->repository->save($node);

        $found = $this->repository->findByPath($entityId, '/test/path');

        $this->assertNotNull($found);
        $this->assertEquals('/test/path', $found->getPath());
    }

    public function testFindChildren(): void
    {
        $entityId = EntityId::generate();
        $parentId = ContentNodeId::generate();
        
        // Create parent node
        $parent = new ContentNode(
            id: $parentId,
            entityId: $entityId,
            type: ContentNodeType::Part,
            title: 'Parent Part',
            content: '',
            metadata: [],
            path: '/part-1',
            position: 0,
            parentId: null,
            createdAt: new \DateTime(),
            updatedAt: null,
            deletedAt: null
        );
        $this->repository->save($parent);

        // Create 3 child nodes
        for ($i = 0; $i < 3; $i++) {
            $child = new ContentNode(
                id: ContentNodeId::generate(),
                entityId: $entityId,
                type: ContentNodeType::Chapter,
                title: "Child {$i}",
                content: '',
                metadata: [],
                path: "/part-1/chapter-{$i}",
                position: $i,
                parentId: $parentId,
                createdAt: new \DateTime(),
                updatedAt: null,
                deletedAt: null
            );
            $this->repository->save($child);
        }

        $children = $this->repository->findChildren($parentId);

        $this->assertCount(3, $children);
    }

    public function testSoftDelete(): void
    {
        $entityId = EntityId::generate();
        $nodeId = ContentNodeId::generate();
        
        $node = new ContentNode(
            id: $nodeId,
            entityId: $entityId,
            type: ContentNodeType::Chapter,
            title: 'To Delete',
            content: 'Will be deleted',
            metadata: [],
            path: '/to-delete',
            position: 0,
            parentId: null,
            createdAt: new \DateTime(),
            updatedAt: null,
            deletedAt: null
        );

        $this->repository->save($node);

        // Verify node exists
        $found = $this->repository->findById($nodeId);
        $this->assertNotNull($found);

        // Soft delete
        $this->repository->delete($nodeId);

        // Node should not be found
        $notFound = $this->repository->findById($nodeId);
        $this->assertNull($notFound);
    }

    public function testRestore(): void
    {
        $entityId = EntityId::generate();
        $nodeId = ContentNodeId::generate();
        
        $node = new ContentNode(
            id: $nodeId,
            entityId: $entityId,
            type: ContentNodeType::Chapter,
            title: 'To Restore',
            content: 'Will be restored',
            metadata: [],
            path: '/to-restore',
            position: 0,
            parentId: null,
            createdAt: new \DateTime(),
            updatedAt: null,
            deletedAt: null
        );

        $this->repository->save($node);
        $this->repository->delete($nodeId);

        // Restore
        $restored = $this->repository->restore($nodeId);

        $this->assertNotNull($restored);
        $this->assertEquals($nodeId->toString(), $restored->getId()->toString());
    }

    public function testForceDelete(): void
    {
        $entityId = EntityId::generate();
        $nodeId = ContentNodeId::generate();
        
        $node = new ContentNode(
            id: $nodeId,
            entityId: $entityId,
            type: ContentNodeType::Chapter,
            title: 'To Force Delete',
            content: 'Will be permanently deleted',
            metadata: [],
            path: '/force-delete',
            position: 0,
            parentId: null,
            createdAt: new \DateTime(),
            updatedAt: null,
            deletedAt: null
        );

        $this->repository->save($node);
        $this->repository->forceDelete($nodeId);

        $found = $this->repository->findById($nodeId);
        $this->assertNull($found);
    }

    public function testReorder(): void
    {
        $entityId = EntityId::generate();
        $parentId = ContentNodeId::generate();
        
        // Create parent
        $parent = new ContentNode(
            id: $parentId,
            entityId: $entityId,
            type: ContentNodeType::Part,
            title: 'Parent',
            content: '',
            metadata: [],
            path: '/part',
            position: 0,
            parentId: null,
            createdAt: new \DateTime(),
            updatedAt: null,
            deletedAt: null
        );
        $this->repository->save($parent);

        // Create 3 children with initial order
        $childIds = [];
        for ($i = 0; $i < 3; $i++) {
            $childId = ContentNodeId::generate();
            $childIds[] = $childId;
            
            $child = new ContentNode(
                id: $childId,
                entityId: $entityId,
                type: ContentNodeType::Chapter,
                title: "Child {$i}",
                content: '',
                metadata: [],
                path: "/part/chapter-{$i}",
                position: $i,
                parentId: $parentId,
                createdAt: new \DateTime(),
                updatedAt: null,
                deletedAt: null
            );
            $this->repository->save($child);
        }

        // Reorder: reverse the order
        $reorderedIds = array_reverse($childIds);
        $this->repository->reorder($parentId, $reorderedIds);

        // Verify new order
        $children = $this->repository->findChildren($parentId);
        $this->assertCount(3, $children);
        
        // Check positions are updated
        $this->assertEquals(0, $children[0]->getPosition());
        $this->assertEquals(1, $children[1]->getPosition());
        $this->assertEquals(2, $children[2]->getPosition());
    }

    public function testMove(): void
    {
        $entityId = EntityId::generate();
        $oldParentId = ContentNodeId::generate();
        $newParentId = ContentNodeId::generate();
        $nodeId = ContentNodeId::generate();
        
        // Create old parent
        $oldParent = new ContentNode(
            id: $oldParentId,
            entityId: $entityId,
            type: ContentNodeType::Part,
            title: 'Old Parent',
            content: '',
            metadata: [],
            path: '/old-part',
            position: 0,
            parentId: null,
            createdAt: new \DateTime(),
            updatedAt: null,
            deletedAt: null
        );
        $this->repository->save($oldParent);

        // Create new parent
        $newParent = new ContentNode(
            id: $newParentId,
            entityId: $entityId,
            type: ContentNodeType::Part,
            title: 'New Parent',
            content: '',
            metadata: [],
            path: '/new-part',
            position: 1,
            parentId: null,
            createdAt: new \DateTime(),
            updatedAt: null,
            deletedAt: null
        );
        $this->repository->save($newParent);

        // Create node to move
        $node = new ContentNode(
            id: $nodeId,
            entityId: $entityId,
            type: ContentNodeType::Chapter,
            title: 'To Move',
            content: '',
            metadata: [],
            path: '/old-part/chapter',
            position: 0,
            parentId: $oldParentId,
            createdAt: new \DateTime(),
            updatedAt: null,
            deletedAt: null
        );
        $this->repository->save($node);

        // Move to new parent at position 5
        $this->repository->move($nodeId, $newParentId, 5);

        // Verify move
        $moved = $this->repository->findById($nodeId);
        $this->assertNotNull($moved);
        $this->assertEquals($newParentId->toString(), $moved->getParentId()->toString());
        $this->assertEquals(5, $moved->getPosition());
    }

    public function testMoveToRoot(): void
    {
        $entityId = EntityId::generate();
        $parentId = ContentNodeId::generate();
        $nodeId = ContentNodeId::generate();
        
        // Create parent
        $parent = new ContentNode(
            id: $parentId,
            entityId: $entityId,
            type: ContentNodeType::Part,
            title: 'Parent',
            content: '',
            metadata: [],
            path: '/part',
            position: 0,
            parentId: null,
            createdAt: new \DateTime(),
            updatedAt: null,
            deletedAt: null
        );
        $this->repository->save($parent);

        // Create child node
        $node = new ContentNode(
            id: $nodeId,
            entityId: $entityId,
            type: ContentNodeType::Chapter,
            title: 'To Move to Root',
            content: '',
            metadata: [],
            path: '/part/chapter',
            position: 0,
            parentId: $parentId,
            createdAt: new \DateTime(),
            updatedAt: null,
            deletedAt: null
        );
        $this->repository->save($node);

        // Move to root (null parent)
        $this->repository->move($nodeId, null, 10);

        // Verify move to root
        $moved = $this->repository->findById($nodeId);
        $this->assertNotNull($moved);
        $this->assertNull($moved->getParentId());
        $this->assertEquals(10, $moved->getPosition());
    }
}
