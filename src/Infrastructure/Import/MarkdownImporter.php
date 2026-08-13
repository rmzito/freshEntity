<?php

declare(strict_types=1);

namespace Infrastructure\Import;

use Domain\Import\ImportResult;

/**
 * Markdown File Importer
 * 
 * Imports Markdown files (.md, .markdown) and extracts:
 * - Front matter metadata (YAML between --- markers)
 * - Content as structured nodes based on headings
 * - Code blocks with language information
 * 
 * @label PROPOSED - First document importer in Phase 4.1
 */
final class MarkdownImporter extends AbstractImporter
{
    protected const MAX_FILE_SIZE = 10485760; // 10MB for markdown
    
    protected const ALLOWED_MIME_TYPES = [
        'text/markdown',
        'text/plain',
        'text/x-markdown'
    ];

    /**
     * Import a Markdown file
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
            
            // Parse front matter and content
            ['frontMatter' => $frontMatter, 'content' => $markdownContent] = $this->parseFrontMatter($content);
            
            // Parse content into nodes based on headings
            $nodes = $this->parseMarkdownNodes($markdownContent);
            
            // Extract title from front matter or first heading
            $title = $frontMatter['title'] ?? $this->extractTitle($markdownContent) ?? basename($filePath, '.md');
            
            return new ImportResult(
                importId: $this->generateImportId(),
                contentNodes: $nodes,
                entityTitle: $title,
                entityType: 'manuscript',
                metadata: [
                    ...$this->extractBasicMetadata($filePath),
                    ...$frontMatter,
                    'format' => 'markdown',
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

        // Verify it's actually a text/markdown file or plain text with markdown extension
        try {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($filePath);
            
            // Accept markdown-specific MIME types
            if (in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
                return true;
            }
            
            // Fallback: accept plain text files with markdown extension
            // This handles cases where the system doesn't recognize text/markdown
            if ($mimeType === 'text/plain') {
                return true;
            }
            
            return false;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get supported extensions
     */
    public function getSupportedExtensions(): array
    {
        return ['md', 'markdown'];
    }

    /**
     * Parse YAML front matter from markdown content
     * 
     * @return array{frontMatter: array<string, mixed>, content: string}
     */
    private function parseFrontMatter(string $content): array
    {
        $frontMatter = [];
        
        // Check for YAML front matter (--- at start)
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches)) {
            $yamlContent = $matches[1];
            $content = $matches[2];
            
            // Simple YAML parsing (handles basic key: value pairs)
            if (preg_match_all('/^([\w\-]+):\s*(.*)$/m', $yamlContent, $yamlMatches, PREG_SET_ORDER)) {
                foreach ($yamlMatches as $match) {
                    $key = $match[1];
                    $value = trim($match[2], '"\'');
                    
                    // Parse arrays [item1, item2]
                    if (preg_match('/^\[(.*)\]$/', $value, $arrayMatch)) {
                        $value = array_map('trim', explode(',', $arrayMatch[1]));
                    }
                    
                    $frontMatter[$key] = $value;
                }
            }
        }
        
        return [
            'frontMatter' => $frontMatter,
            'content' => $content
        ];
    }

    /**
     * Parse markdown content into content nodes based on headings
     * 
     * Each top-level heading (#) becomes a node
     * Sub-headings become part of the node content
     * 
     * @return array<array{type: string, title: string, content: string, position: int}>
     */
    private function parseMarkdownNodes(string $content): array
    {
        $nodes = [];
        $position = 0;
        
        // Split by top-level headings (# )
        $sections = preg_split('/^(#\s+.+)$/m', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        
        $currentTitle = null;
        $currentContent = '';
        
        foreach ($sections as $section) {
            $section = trim($section);
            
            if (empty($section)) {
                continue;
            }
            
            // Check if this is a heading
            if (preg_match('/^#\s+(.+)$/', $section, $headingMatch)) {
                // Save previous section if exists
                if ($currentTitle !== null && !empty(trim($currentContent))) {
                    $nodes[] = [
                        'type' => 'chapter',
                        'title' => $currentTitle,
                        'content' => trim($currentContent),
                        'position' => $position++
                    ];
                }
                
                $currentTitle = $headingMatch[1];
                $currentContent = '';
            } else {
                // Append to current content
                $currentContent .= $section . "\n\n";
            }
        }
        
        // Don't forget the last section
        if ($currentTitle !== null && !empty(trim($currentContent))) {
            $nodes[] = [
                'type' => 'chapter',
                'title' => $currentTitle,
                'content' => trim($currentContent),
                'position' => $position
            ];
        }
        
        // If no headings found, treat entire content as single node
        if (empty($nodes) && !empty(trim($content))) {
            $nodes[] = [
                'type' => 'section',
                'title' => 'Content',
                'content' => trim($content),
                'position' => 0
            ];
        }
        
        return $nodes;
    }

    /**
     * Extract title from first heading in content
     */
    private function extractTitle(string $content): ?string
    {
        if (preg_match('/^#\s+(.+)$/m', $content, $match)) {
            return trim($match[1]);
        }
        
        return null;
    }
}
