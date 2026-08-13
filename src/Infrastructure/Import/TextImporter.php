<?php

declare(strict_types=1);

namespace Infrastructure\Import;

use Domain\Import\ImportResult;

/**
 * Plain Text File Importer
 * 
 * Imports plain text files (.txt) with minimal processing.
 * Creates a single content node with the entire file content.
 * 
 * @label PROPOSED - Simple document importer in Phase 4.1
 */
final class TextImporter extends AbstractImporter
{
    protected const MAX_FILE_SIZE = 5242880; // 5MB for text files
    
    protected const ALLOWED_MIME_TYPES = [
        'text/plain',
        'application/x-empty' // Empty files are valid for text
    ];

    /**
     * Import a plain text file
     */
    public function import(string $filePath): ImportResult
    {
        try {
            $this->validateFile($filePath);
            
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new \RuntimeException("Failed to read file: {$filePath}");
            }

            $content = $this->sanitizeContent($content);
            
            // Extract title from first non-empty line or filename
            $title = $this->extractTitle($content) ?? basename($filePath, '.txt');
            
            return new ImportResult(
                importId: $this->generateImportId(),
                contentNodes: [
                    [
                        'type' => 'section',
                        'title' => $title,
                        'content' => $content,
                        'position' => 0
                    ]
                ],
                entityTitle: $title,
                entityType: 'manuscript',
                metadata: [
                    ...$this->extractBasicMetadata($filePath),
                    'format' => 'text',
                    'line_count' => substr_count($content, "\n") + 1,
                    'word_count' => str_word_count($content),
                ],
                warnings: [],
                errors: []
            );
            
        } catch (\InvalidArgumentException $e) {
            return ImportResult::failure(
                $this->generateImportId(),
                [$e->getMessage()]
            );
        } catch (\RuntimeException $e) {
            return ImportResult::failure(
                $this->generateImportId(),
                [$e->getMessage()]
            );
        }
    }

    /**
     * Check if this importer supports the file
     */
    public function supports(string $filePath): bool
    {
        if (!$this->hasSupportedExtension($filePath)) {
            return false;
        }

        // Verify it's actually a text/plain file
        try {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($filePath);
            
            return $mimeType === 'text/plain';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get supported extensions
     */
    public function getSupportedExtensions(): array
    {
        return ['txt'];
    }

    /**
     * Extract title from first non-empty line
     */
    private function extractTitle(string $content): ?string
    {
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!empty($trimmed)) {
                // Limit title length
                return mb_substr($trimmed, 0, 200);
            }
        }
        
        return null;
    }
}
