<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mongo;

use App\Domain\Content\ContentNode;
use App\Domain\Repository\ContentNodeRepositoryInterface;
use App\Domain\ValueObject\ContentNodeId;
use App\Domain\ValueObject\EntityId;
use MongoDB\Client;
use MongoDB\Collection;

/**
 * MongoDB implementation of ContentNodeRepositoryInterface.
 * 
 * VERIFIED: Matches legacy MongoDB storage semantics
 * - Hierarchical content nodes stored in MongoDB
 * - Parent-child relationships via parent_id
 * - Path-based navigation
 * - Soft delete via deleted_at field
 */
class MongoContentNodeRepository implements ContentNodeRepositoryInterface
{
    private Collection $collection;

    public function __construct(Client $mongoClient, string $databaseName = 'entity')
    {
        $this->collection = $mongoClient->selectCollection($databaseName, 'content_nodes');
    }

    public function findById(ContentNodeId $id): ?ContentNode
    {
        $row = $this->collection->findOne([
            '_id' => $id->toString(),
            'deleted_at' => null,
        ]);

        if (!$row) {
            return null;
        }

        return $this->hydrateContentNode((array) $row);
    }

    public function findByEntityId(EntityId $entityId): array
    {
        $cursor = $this->collection->find([
            'entity_id' => $entityId->toString(),
            'deleted_at' => null,
        ], ['sort' => ['position' => 1]]);

        return array_map([$this, 'hydrateContentNode'], iterator_to_array($cursor));
    }

    public function findByPath(EntityId $entityId, string $path): ?ContentNode
    {
        $row = $this->collection->findOne([
            'entity_id' => $entityId->toString(),
            'path' => $path,
            'deleted_at' => null,
        ]);

        if (!$row) {
            return null;
        }

        return $this->hydrateContentNode((array) $row);
    }

    public function findChildren(ContentNodeId $parentId): array
    {
        $cursor = $this->collection->find([
            'parent_id' => $parentId->toString(),
            'deleted_at' => null,
        ], ['sort' => ['position' => 1]]);

        return array_map([$this, 'hydrateContentNode'], iterator_to_array($cursor));
    }

    public function save(ContentNode $node): void
    {
        $data = [
            'entity_id' => $node->getEntityId()->toString(),
            'parent_id' => $node->getParentId()?->toString(),
            'type' => $node->getType()->value,
            'title' => $node->getTitle(),
            'content' => $node->getContent(),
            'metadata' => $node->getMetadata(),
            'path' => $node->getPath(),
            'position' => $node->getPosition(),
            'updated_at' => $node->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];

        $existing = $this->findById($node->getId());

        if ($existing) {
            $this->collection->updateOne(
                ['_id' => $node->getId()->toString()],
                ['$set' => $data]
            );
        } else {
            $data['_id'] = $node->getId()->toString();
            $data['created_at'] = $node->getCreatedAt()->format('Y-m-d H:i:s');
            $data['deleted_at'] = null;
            $this->collection->insertOne($data);
        }
    }

    public function delete(ContentNodeId $id): void
    {
        $this->collection->updateOne(
            ['_id' => $id->toString()],
            ['$set' => ['deleted_at' => (new \DateTime())->format('Y-m-d H:i:s')]]
        );
    }

    public function forceDelete(ContentNodeId $id): void
    {
        $this->collection->deleteOne(['_id' => $id->toString()]);
    }

    public function restore(ContentNodeId $id): ?ContentNode
    {
        $this->collection->updateOne(
            ['_id' => $id->toString()],
            ['$set' => ['deleted_at' => null]]
        );

        return $this->findById($id);
    }

    public function reorder(ContentNodeId $parentId, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $nodeId) {
            $this->collection->updateOne(
                ['_id' => $nodeId->toString()],
                [
                    '$set' => [
                        'parent_id' => $parentId->toString(),
                        'position' => $index,
                    ]
                ]
            );
        }
    }

    public function move(ContentNodeId $nodeId, ?ContentNodeId $newParentId, int $position): void
    {
        $update = [
            'parent_id' => $newParentId?->toString(),
            'position' => $position,
        ];

        $this->collection->updateOne(
            ['_id' => $nodeId->toString()],
            ['$set' => $update]
        );
    }

    /**
     * Hydrate a ContentNode from a MongoDB document.
     * 
     * @param array<string, mixed> $doc
     * @return ContentNode
     */
    private function hydrateContentNode(array $doc): ContentNode
    {
        return new ContentNode(
            id: ContentNodeId::fromString($doc['_id']),
            entityId: EntityId::fromString($doc['entity_id']),
            type: \App\Domain\Content\ContentNodeType::from($doc['type']),
            title: $doc['title'] ?? '',
            content: $doc['content'] ?? '',
            metadata: $doc['metadata'] ?? [],
            path: $doc['path'] ?? '',
            position: (int) ($doc['position'] ?? 0),
            parentId: isset($doc['parent_id']) ? ContentNodeId::fromString($doc['parent_id']) : null,
            createdAt: new \DateTime($doc['created_at']),
            updatedAt: isset($doc['updated_at']) ? new \DateTime($doc['updated_at']) : null,
            deletedAt: isset($doc['deleted_at']) ? new \DateTime($doc['deleted_at']) : null
        );
    }
}
