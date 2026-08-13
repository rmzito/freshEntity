<?php

declare(strict_types=1);

namespace App\Application\Importer;

use App\Application\DTO\ContentNodeImportDTO;
use App\Domain\Content\ContentNodeType;

/**
 * Importer for plain text (.txt) files.
 * 
 * @label PROPOSED - Text importer for Phase 4 (Import System)
 */
final class TextImporter implements EntityImporterInterface
{
    /**
     * @var string Default entity type for text imports
     */
    private string $entityType = 'book';

    public function __construct(?string $entityType = null)
    {
        if ($entityType !== null) {
            $this->entityType = $entityType;
        }
    }

    public function import(string $filePath): \App\Application\DTO\EntityImportDTO
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        // Extract title from first line or filename
        $lines = explode("\n", $content);
        $title = $this->extractTitle($lines, $filePath);
        
        // Generate slug from title
        $slug = $this->generateSlug($title);

        // Parse content into nodes based on structure
        $contentNodes = $this->parseContentIntoNodes($content, $title);

        return new \App\Application\DTO\EntityImportDTO(
            type: $this->entityType,
            title: $title,
            slug: $slug,
            description: null,
            filePath: $filePath,
            metadata: ['original_format' => 'txt'],
            taxonomy: [],
            contentNodes: $contentNodes
        );
    }

    public function supports(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return $extension === 'txt';
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Extract title from first non-empty line or use filename.
     */
    private function extractTitle(array $lines, string $filePath): string
    {
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!empty($trimmed)) {
                // Use first line if it's short enough to be a title
                if (strlen($trimmed) < 200) {
                    return $trimmed;
                }
                break;
            }
        }
        
        // Fallback to filename
        return pathinfo($filePath, PATHINFO_FILENAME);
    }

    /**
     * Generate a URL-friendly slug from a title.
     */
    private function generateSlug(string $title): string
    {
        $slug = mb_strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s-]/u', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Parse text content into content nodes based on blank lines and structure.
     * 
     * @return array<ContentNodeImportDTO>
     */
    private function parseContentIntoNodes(string $content, string $rootTitle): array
    {
        $nodes = [];
        $order = 0;

        // Create root node for the document
        $nodes[] = new ContentNodeImportDTO(
            title: $rootTitle,
            slug: $this->generateSlug($rootTitle),
            type: ContentNodeType::CHAPTER,
            content: $this->textToHtml($this->extractIntroContent($content)),
            contentBlocks: [],
            order: $order++
        );

        // Split by double newlines to identify paragraphs/sections
        $paragraphs = preg_split('/\n\s*\n/', $content);
        $currentSection = [];
        $sectionOrder = 0;

        foreach ($paragraphs as $index => $paragraph) {
            $trimmed = trim($paragraph);
            if (empty($trimmed)) {
                continue;
            }

            // Check if this looks like a section header (short, no punctuation at end)
            if ($this->isSectionHeader($trimmed, $index)) {
                // Save previous section if exists
                if (!empty($currentSection)) {
                    $nodes[] = $this->createSectionNode(
                        implode("\n\n", $currentSection),
                        "Section " . ($sectionOrder + 1),
                        $sectionOrder++,
                        $order++
                    );
                    $currentSection = [];
                }
                
                // Create node for this header
                $nodes[] = $this->createSectionNode(
                    $trimmed,
                    $trimmed,
                    0,
                    $order++
                );
            } else {
                $currentSection[] = $trimmed;
            }
        }

        // Add remaining content as final section
        if (!empty($currentSection)) {
            $nodes[] = $this->createSectionNode(
                implode("\n\n", $currentSection),
                "Conclusion",
                $sectionOrder,
                $order++
            );
        }

        return $nodes;
    }

    /**
     * Check if a line looks like a section header.
     */
    private function isSectionHeader(string $line, int $index): bool
    {
        $trimmed = trim($line);
        
        // Too long to be a header
        if (strlen($trimmed) > 150) {
            return false;
        }
        
        // First line might be title, not a section header
        if ($index === 0) {
            return false;
        }

        // Headers often don't end with periods
        if (substr($trimmed, -1) === '.') {
            return false;
        }

        // Could be all caps or have special formatting
        return true;
    }

    /**
     * Create a section content node.
     */
    private function createSectionNode(string $content, string $title, int $subOrder, int $order): ContentNodeImportDTO
    {
        return new ContentNodeImportDTO(
            title: $title,
            slug: $this->generateSlug($title) . '-' . $subOrder,
            type: ContentNodeType::SECTION,
            content: $this->textToHtml($content),
            contentBlocks: [],
            order: $order
        );
    }

    /**
     * Extract introductory content (before first major break).
     */
    private function extractIntroContent(string $content): string
    {
        $lines = explode("\n", $content);
        $intro = [];
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) {
                break;
            }
            $intro[] = $trimmed;
        }
        
        return implode(' ', $intro);
    }

    /**
     * Convert plain text to HTML.
     */
    private function textToHtml(string $text): string
    {
        // Escape HTML entities
        $html = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Convert double newlines to paragraph breaks
        $html = preg_replace('/\n\s*\n/', '</p><p>', $html);
        
        // Convert single newlines to line breaks
        $html = nl2br($html);
        
        // Wrap in paragraph tags
        $html = '<p>' . $html . '</p>';
        
        return trim($html);
    }
}
