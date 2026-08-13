<?php

declare(strict_types=1);

namespace App\Application\Importer;

use App\Application\DTO\EntityImportDTO;
use RuntimeException;

/**
 * Factory service for routing import requests to appropriate importers.
 * 
 * @label PROPOSED - Central import orchestration for Phase 4 (Import System)
 */
class ImportService
{
    /**
     * @var array<EntityImporterInterface> List of available importers
     */
    private array $importers;

    /**
     * @param array<EntityImporterInterface> $importers List of available importers
     */
    public function __construct(array $importers = [])
    {
        $this->importers = $importers;
    }

    /**
     * Import a file using the appropriate importer.
     * 
     * @param string $filePath Path to the file to import
     * @return EntityImportDTO Validated DTO ready for persistence
     * @throws RuntimeException If no suitable importer is found or import fails
     */
    public function import(string $filePath): EntityImportDTO
    {
        $importer = $this->findImporter($filePath);
        
        if ($importer === null) {
            throw new RuntimeException(
                sprintf('No importer found for file: %s', $filePath)
            );
        }

        return $importer->import($filePath);
    }

    /**
     * Check if any importer supports the given file.
     * 
     * @param string $filePath Path to the file
     * @return bool True if supported
     */
    public function supports(string $filePath): bool
    {
        return $this->findImporter($filePath) !== null;
    }

    /**
     * Get all supported file extensions.
     * 
     * @return array<string> List of supported extensions (e.g., ['.txt', '.md', '.pdf'])
     */
    public function getSupportedExtensions(): array
    {
        $extensions = [];
        
        foreach ($this->importers as $importer) {
            // Try to get supported extensions from the importer
            if (method_exists($importer, 'getSupportedExtensions')) {
                $extensions = array_merge($extensions, $importer->getSupportedExtensions());
            }
        }
        
        return array_unique($extensions);
    }

    /**
     * Find the first importer that supports the given file.
     * 
     * @param string $filePath Path to the file
     * @return EntityImporterInterface|null The matching importer or null
     */
    private function findImporter(string $filePath): ?EntityImporterInterface
    {
        foreach ($this->importers as $importer) {
            if ($importer->supports($filePath)) {
                return $importer;
            }
        }
        
        return null;
    }

    /**
     * Register an additional importer at runtime.
     * 
     * @param EntityImporterInterface $importer The importer to add
     */
    public function addImporter(EntityImporterInterface $importer): void
    {
        $this->importers[] = $importer;
    }
}
