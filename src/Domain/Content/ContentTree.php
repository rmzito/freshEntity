<?php

declare(strict_types=1);

namespace App\Domain\Content;

use App\Domain\ValueObject\ContentNodeId;

/**
 * ContentTree represents a hierarchical structure of content nodes.
 * 
 * @label VERIFIED - Legacy system organizes content nodes in a tree structure with parent-child relationships
 */
final class ContentTree
{
    /**
     * @param array<ContentNode> $nodes
     */
    public function __construct(
        private readonly array $nodes
    ) {
    }

    /**
     * @return array<ContentNode>
     */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /**
     * Get root nodes (nodes without parent).
     * 
     * @return array<ContentNode>
     */
    public function roots(): array
    {
        return array_values(array_filter(
            $this->nodes,
            fn(ContentNode $node) => $node->getParentId() === null && !$node->isDeleted()
        ));
    }

    /**
     * Get children of a specific node.
     * 
     * @return array<ContentNode>
     */
    public function children(ContentNodeId $parentId): array
    {
        return array_values(array_filter(
            $this->nodes,
            fn(ContentNode $node) => 
                $node->getParentId() !== null && 
                $node->getParentId()->equals($parentId) && 
                !$node->isDeleted()
        ));
    }

    /**
     * Get a node by its ID.
     */
    public function getNode(ContentNodeId $id): ?ContentNode
    {
        foreach ($this->nodes as $node) {
            if ($node->getId()->equals($id)) {
                return $node;
            }
        }
        return null;
    }

    /**
     * Build a hierarchical array representation.
     * 
     * @return array<string, mixed>
     */
    public function toHierarchy(): array
    {
        $roots = $this->roots();
        return array_map(fn(ContentNode $node) => $this->buildNodeTree($node), $roots);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNodeTree(ContentNode $node): array
    {
        $children = $this->children($node->getId());
        
        return [
            'id' => $node->getId()->value(),
            'type' => $node->getType()->value,
            'title' => $node->getTitle(),
            'content' => $node->getContent(),
            'position' => $node->getPosition(),
            'path' => $node->getPath(),
            'metadata' => $node->getMetadata(),
            'children' => array_map(fn(ContentNode $child) => $this->buildNodeTree($child), $children),
        ];
    }
}
