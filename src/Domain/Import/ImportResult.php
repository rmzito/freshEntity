<?php

declare(strict_types=1);

namespace Domain\Import;

/**
 * ImportResult DTO
 * 
 * Represents the result of an import operation.
 * Contains all data needed to persist imported content through use cases.
 * 
 * @label VERIFIED - Required for separation between import and persistence
 */
final class ImportResult
{
    /**
     * @param string $importId Unique identifier for this import operation
     * @param array<array{type: string, title: string, content: string, position: int}> $contentNodes
     * @param ?string $entityTitle Title for entity if one should be created
     * @param string|null $entityType Type of entity to create (book, manuscript, etc.)
     * @param array<string, mixed> $metadata Extracted metadata
     * @param array<string> $warnings Non-fatal warnings during import
     * @param array<string> $errors Fatal errors that prevented import
     */
    public function __construct(
        public readonly string $importId,
        public readonly array $contentNodes = [],
        public readonly ?string $entityTitle = null,
        public readonly ?string $entityType = null,
        public readonly array $metadata = [],
        public readonly array $warnings = [],
        public readonly array $errors = []
    ) {
    }

    /**
     * Check if import was successful (no errors)
     */
    public function isSuccess(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if import has any content nodes
     */
    public function hasContent(): bool
    {
        return !empty($this->contentNodes);
    }

    /**
     * Create a failed import result
     */
    public static function failure(string $importId, array $errors): self
    {
        return new self(
            importId: $importId,
            errors: $errors
        );
    }

    /**
     * Merge another import result into this one
     */
    public function merge(self $other): self
    {
        return new self(
            importId: $this->importId,
            contentNodes: [...$this->contentNodes, ...$other->contentNodes],
            entityTitle: $this->entityTitle ?? $other->entityTitle,
            entityType: $this->entityType ?? $other->entityType,
            metadata: [...$this->metadata, ...$other->metadata],
            warnings: [...$this->warnings, ...$other->warnings],
            errors: [...$this->errors, ...$other->errors]
        );
    }
}
