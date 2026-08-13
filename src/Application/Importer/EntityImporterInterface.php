<?php

declare(strict_types=1);

namespace App\Application\Importer;

use App\Application\DTO\EntityImportDTO;

/**
 * Interface for entity importers.
 * 
 * @label PROPOSED - Importer interface for Phase 4 (Import System)
 */
interface EntityImporterInterface
{
    /**
     * Import an entity from a file or stream.
     * 
     * @param string $filePath Path to the file to import
     * @return EntityImportDTO Validated DTO ready for persistence
     * @throws \RuntimeException If import fails
     */
    public function import(string $filePath): EntityImportDTO;

    /**
     * Check if this importer supports the given file.
     * 
     * @param string $filePath Path to the file
     * @return bool True if supported
     */
    public function supports(string $filePath): bool;

    /**
     * Get the entity type this importer produces.
     * 
     * @return string Entity type (book, audio, video, manuscript)
     */
    public function getEntityType(): string;
}
