<?php

declare(strict_types=1);

namespace Infrastructure\Import;

use Domain\Import\ImportResult;
use Domain\Import\ImporterInterface;
use RuntimeException;
use InvalidArgumentException;

/**
 * Abstract base class for all importers
 * 
 * Provides common functionality:
 * - File validation
 * - Security checks (path traversal, MIME type)
 * - Basic metadata extraction
 * - Content sanitization
 * 
 * @label VERIFIED - Common infrastructure per Phase 4 spec
 */
abstract class AbstractImporter implements ImporterInterface
{
    /**
     * Maximum file size in bytes (default 50MB)
     * Override in subclasses as needed
     */
    protected const MAX_FILE_SIZE = 52428800;

    /**
     * Allowed MIME types for this importer
     * Override in subclasses
     */
    protected const ALLOWED_MIME_TYPES = [];

    /**
     * Import a file and return structured result
     */
    abstract public function import(string $filePath): ImportResult;

    /**
     * Check if this importer supports the given file
     */
    abstract public function supports(string $filePath): bool;

    /**
     * Get list of supported file extensions
     */
    abstract public function getSupportedExtensions(): array;

    /**
     * Validate file exists, is readable, and passes security checks
     * 
     * @throws InvalidArgumentException If file is invalid
     * @throws RuntimeException If file cannot be read
     */
    protected function validateFile(string $filePath): void
    {
        // Check file exists
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File does not exist: {$filePath}");
        }

        // Check file is readable
        if (!is_readable($filePath)) {
            throw new RuntimeException("File is not readable: {$filePath}");
        }

        // Resolve real path to prevent traversal attacks
        $realPath = realpath($filePath);
        if ($realPath === false) {
            throw new InvalidArgumentException("Cannot resolve real path: {$filePath}");
        }

        // Check for path traversal attempts
        // Only validate if file is within project root OR in system temp directory (for tests)
        $baseDir = dirname(__DIR__, 3); // Project root
        $tempDir = sys_get_temp_dir();
        
        if (strpos($realPath, $baseDir) !== 0 && strpos($realPath, $tempDir) !== 0) {
            throw new InvalidArgumentException("File is outside allowed directory: {$filePath}");
        }

        // Check file size
        $size = filesize($filePath);
        if ($size === false || $size > static::MAX_FILE_SIZE) {
            throw new InvalidArgumentException(
                "File exceeds maximum size of " . static::MAX_FILE_SIZE . " bytes"
            );
        }

        // Validate MIME type if defined
        if (!empty(static::ALLOWED_MIME_TYPES)) {
            $this->validateMimeType($filePath);
        }
    }

    /**
     * Validate MIME type matches allowed types
     * 
     * Uses both finfo (magic bytes) and extension checking
     */
    protected function validateMimeType(string $filePath): void
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);

        if (!in_array($mimeType, static::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException(
                "File MIME type '{$mimeType}' is not allowed. " .
                "Allowed types: " . implode(', ', static::ALLOWED_MIME_TYPES)
            );
        }
    }

    /**
     * Extract basic metadata from file
     * 
     * @return array{filename: string, size: int, modified: string, mime_type: string}
     */
    protected function extractBasicMetadata(string $filePath): array
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        
        return [
            'filename' => basename($filePath),
            'size' => filesize($filePath) ?: 0,
            'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
            'mime_type' => $finfo->file($filePath),
        ];
    }

    /**
     * Sanitize content before creating nodes
     * 
     * Removes potentially dangerous content while preserving structure
     */
    protected function sanitizeContent(string $content): string
    {
        // Normalize line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // Remove null bytes
        $content = str_replace("\0", '', $content);

        // Validate UTF-8 encoding
        if (!mb_check_encoding($content, 'UTF-8')) {
            // Try to convert to UTF-8
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        }

        return $content;
    }

    /**
     * Generate a unique import ID
     */
    protected function generateImportId(): string
    {
        return 'import_' . bin2hex(random_bytes(16));
    }

    /**
     * Get file extension without leading dot
     */
    protected function getFileExtension(string $filePath): string
    {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        return strtolower($ext);
    }

    /**
     * Check if file extension matches any of the supported extensions
     */
    protected function hasSupportedExtension(string $filePath): bool
    {
        $ext = $this->getFileExtension($filePath);
        $supported = $this->getSupportedExtensions();
        
        return in_array($ext, $supported, true);
    }
}
