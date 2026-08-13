<?php

declare(strict_types=1);

namespace App\Application\Importer;

use App\Application\DTO\ContentNodeImportDTO;
use App\Domain\Content\ContentNodeType;

/**
 * Importer for Markdown files.
 * 
 * @label PROPOSED - Markdown importer for Phase 4 (Import System)
 */
final class MarkdownImporter implements EntityImporterInterface
{
    /**
     * @var string Default entity type for markdown imports
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

        // Parse front matter (YAML metadata between --- markers)
        $metadata = [];
        $body = $content;
        
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches)) {
            $yamlContent = $matches[1];
            $body = $matches[2];
            
            // Simple YAML parsing (could be enhanced with symfony/yaml)
            $lines = explode("\n", $yamlContent);
            foreach ($lines as $line) {
                if (strpos($line, ':') !== false) {
                    [$key, $value] = explode(':', $line, 2);
                    $metadata[trim($key)] = trim($value);
                }
            }
        }

        // Extract title from front matter or first heading
        $title = $metadata['title'] ?? $this->extractFirstHeading($body);
        if (empty($title)) {
            $title = pathinfo($filePath, PATHINFO_FILENAME);
        }

        // Generate slug from title
        $slug = $this->generateSlug($title);

        // Parse content into nodes based on headings
        $contentNodes = $this->parseContentIntoNodes($body, $title);

        return new \App\Application\DTO\EntityImportDTO(
            type: $this->entityType,
            title: $title,
            slug: $slug,
            description: $metadata['description'] ?? null,
            filePath: $filePath,
            metadata: $metadata,
            contentNodes: $contentNodes
        );
    }

    public function supports(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($extension, ['md', 'markdown'], true);
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Extract the first heading from markdown content.
     */
    private function extractFirstHeading(string $content): ?string
    {
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
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
     * Parse markdown content into content nodes based on heading hierarchy.
     * 
     * @return array<ContentNodeImportDTO>
     */
    private function parseContentIntoNodes(string $content, string $rootTitle): array
    {
        $nodes = [];
        $order = 0;

        // Split by headings (h1-h6)
        $pattern = '/^(#{1,6})\s+(.+)$/m';
        $sections = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $currentLevel = 0;
        $currentTitle = '';
        $currentContent = '';
        $headingLevel = 0;

        // Create root node for the document
        $rootContent = $this->extractRootContent($content);
        $nodes[] = new ContentNodeImportDTO(
            title: $rootTitle,
            slug: $this->generateSlug($rootTitle),
            type: $this->determineNodeType(1),
            content: $rootContent,
            order: $order++
        );

        // Process sections
        for ($i = 0; $i < count($sections); $i += 3) {
            if (!isset($sections[$i]) || !isset($sections[$i + 1]) || !isset($sections[$i + 2])) {
                break;
            }

            $heading = $sections[$i];
            $title = trim($sections[$i + 1]);
            $contentSection = $sections[$i + 2] ?? '';

            $level = strlen($heading);
            $nodeType = $this->determineNodeType($level);

            $nodes[] = new ContentNodeImportDTO(
                title: $title,
                slug: $this->generateSlug($title),
                type: $nodeType,
                content: $this->markdownToHtml($contentSection),
                contentBlocks: $this->parseContentBlocks($contentSection),
                order: $order++,
                sourceFile: null,
                lineNumber: null
            );
        }

        return $nodes;
    }

    /**
     * Extract content before the first heading (root content).
     */
    private function extractRootContent(string $content): string
    {
        if (preg_match('/^---\s*\n.*?\n---\s*\n(.*)$/s', $content, $matches)) {
            $body = $matches[1];
            if (preg_match('/^#+\s+.+$/m', $body, $headingMatches, PREG_OFFSET_CAPTURE)) {
                return substr($body, 0, $headingMatches[0][1]);
            }
            return $body;
        }
        
        if (preg_match('/^#+\s+.+$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return substr($content, 0, $matches[0][1]);
        }
        
        return $content;
    }

    /**
     * Determine content node type based on heading level.
     */
    private function determineNodeType(int $level): ContentNodeType
    {
        // Map heading levels to content node types
        return match ($level) {
            1 => ContentNodeType::CHAPTER,
            2 => ContentNodeType::SECTION,
            3 => ContentNodeType::SUBSECTION,
            4 => ContentNodeType::PARAGRAPH,
            5 => ContentNodeType::NOTE,
            6 => ContentNodeType::REFERENCE,
            default => ContentNodeType::CHAPTER,
        };
    }

    /**
     * Convert markdown to HTML.
     */
    private function markdownToHtml(string $markdown): string
    {
        // Simple markdown to HTML conversion
        $html = $markdown;
        
        // Headers
        for ($i = 6; $i >= 1; $i--) {
            $hashes = str_repeat('#', $i);
            $html = preg_replace("/^{$hashes}\s+(.+)$/m", "<h{$i}>$1</h{$i}>", $html);
        }
        
        // Bold and italic
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
        
        // Links
        $html = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $html);
        
        // Paragraphs
        $html = preg_replace('/\n\n+/', '</p><p>', $html);
        $html = '<p>' . $html . '</p>';
        
        return trim($html);
    }

    /**
     * Parse content into structured blocks.
     * 
     * @return array<string, mixed>
     */
    private function parseContentBlocks(string $content): array
    {
        $blocks = [];
        
        // Extract code blocks
        if (preg_match_all('/```(\w+)?\n(.*?)```/s', $content, $codeMatches, PREG_SET_ORDER)) {
            foreach ($codeMatches as $match) {
                $blocks[] = [
                    'type' => 'code',
                    'language' => $match[1] ?? 'text',
                    'content' => trim($match[2]),
                ];
            }
        }
        
        // Extract blockquotes
        if (preg_match_all('/^>\s+(.+)$/m', $content, $quoteMatches, PREG_SET_ORDER)) {
            foreach ($quoteMatches as $match) {
                $blocks[] = [
                    'type' => 'quote',
                    'content' => trim($match[1]),
                ];
            }
        }
        
        return $blocks;
    }
}
