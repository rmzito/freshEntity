<?php

declare(strict_types=1);

namespace Domain\Import;

/**
 * Importer Interface
 * 
 * All importers must implement this interface.
 * Importers are responsible for:
 * - Validating file type and content
 * - Extracting content and metadata
 * - Returning structured ImportResult DTOs
 * 
 * Importers do NOT persist data directly.
 * They return DTOs that should be processed by Application Use Cases.
 * 
 * @label VERIFIED - Required by Phase 4 specification
 */
interface ImporterInterface
{
    /**
     * Import a file and return structured result
     * 
     * @param string $filePath Absolute path to the file
     * @return ImportResult Contains extracted content nodes, metadata, and errors
     * 
     * @throws \RuntimeException If file cannot be read
     * @throws \InvalidArgumentException If file is not supported
     */
    public function import(string $filePath): ImportResult;

    /**
     * Check if this importer supports the given file
     * 
     * Implementation should check both extension and file content (magic bytes)
     * to prevent security issues from mislabeled files.
     * 
     * @param string $filePath Absolute path to the file
     * @return bool True if this importer can handle the file
     */
    public function supports(string $filePath): bool;

    /**
     * Get list of supported file extensions
     * 
     * Extensions should be returned without leading dot.
     * Example: ['md', 'markdown'] not ['.md', '.markdown']
     * 
     * @return array<string> List of supported extensions
     */
    public function getSupportedExtensions(): array;
}
