<?php

declare(strict_types=1);

namespace App\Application\Importer;

use App\Application\DTO\ContentNodeImportDTO;
use App\Domain\Content\ContentNodeType;

/**
 * Importer for CSV files.
 *
 * @label PROPOSED - CSV importer for Phase 4 (Import System)
 */
final class CsvImporter implements EntityImporterInterface
{
    /**
     * @var string Default entity type for CSV imports
     */
    private string $entityType = 'book';

    /**
     * @var string Column to use as title
     */
    private string $titleColumn = 'title';

    /**
     * @var string Column to use as content/body
     */
    private string $contentColumn = 'content';

    /**
     * @var string Column to use as slug (optional)
     */
    private string $slugColumn = 'slug';

    /**
     * @var array<string> Columns to treat as taxonomy tags
     */
    private array $taxonomyColumns = ['tags', 'categories'];

    public function __construct(
        ?string $entityType = null,
        ?string $titleColumn = null,
        ?string $contentColumn = null
    ) {
        if ($entityType !== null) {
            $this->entityType = $entityType;
        }
        if ($titleColumn !== null) {
            $this->titleColumn = $titleColumn;
        }
        if ($contentColumn !== null) {
            $this->contentColumn = $contentColumn;
        }
    }

    public function import(string $filePath): \App\Application\DTO\EntityImportDTO
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Failed to open file: {$filePath}");
        }

        try {
            // Read header row
            $headers = fgetcsv($handle);
            if ($headers === false || empty($headers)) {
                throw new \RuntimeException("CSV file is empty or has no headers");
            }

            // Normalize headers
            $headers = array_map(fn($h) => strtolower(trim($h ?? '')), $headers);

            // Validate required columns exist
            if (!in_array($this->titleColumn, $headers, true)) {
                throw new \RuntimeException(
                    "Required column '{$this->titleColumn}' not found in CSV. Available: " . implode(', ', $headers)
                );
            }

            // Get column indices
            $columnIndex = array_flip($headers);
            $titleIndex = $columnIndex[$this->titleColumn] ?? 0;
            $contentIndex = $columnIndex[$this->contentColumn] ?? null;
            $slugIndex = $columnIndex[$this->slugColumn] ?? null;

            // Extract taxonomy data
            $taxonomy = $this->extractTaxonomy($headers, $columnIndex);

            // Read all rows
            $rows = [];
            $lineNumber = 1; // Start after header
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;
                if (!empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
                    $rows[] = ['data' => $row, 'line' => $lineNumber];
                }
            }

            if (empty($rows)) {
                throw new \RuntimeException("CSV file has no data rows");
            }

            // Generate title from first row or filename
            $title = $this->extractTitle($rows, $titleIndex, $filePath);
            $slug = $this->generateSlug($title);

            // Create content nodes from rows
            $contentNodes = $this->createContentNodes($rows, $titleIndex, $contentIndex, $slugIndex);

            return new \App\Application\DTO\EntityImportDTO(
                type: $this->entityType,
                title: $title,
                slug: $slug,
                description: null,
                filePath: $filePath,
                metadata: [
                    'original_format' => 'csv',
                    'row_count' => count($rows),
                    'columns' => $headers,
                ],
                taxonomy: $taxonomy,
                contentNodes: $contentNodes
            );
        } finally {
            fclose($handle);
        }
    }

    public function supports(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return $extension === 'csv';
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Extract taxonomy from specified columns.
     *
     * @param array<string> $headers
     * @param array<string, int> $columnIndex
     * @return array<string, mixed>
     */
    private function extractTaxonomy(array $headers, array $columnIndex): array
    {
        $taxonomy = [];

        foreach ($this->taxonomyColumns as $taxCol) {
            if (isset($columnIndex[$taxCol])) {
                $taxonomy[$taxCol] = [];
            }
        }

        return $taxonomy;
    }

    /**
     * Extract title from first row or filename.
     *
     * @param array<array{data: array<string|null>, line: int}> $rows
     */
    private function extractTitle(array $rows, int $titleIndex, string $filePath): string
    {
        if (!empty($rows) && isset($rows[0]['data'][$titleIndex])) {
            $title = trim($rows[0]['data'][$titleIndex] ?? '');
            if (!empty($title)) {
                return $title;
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
     * Create content nodes from CSV rows.
     *
     * @param array<array{data: array<string|null>, line: int}> $rows
     * @return array<ContentNodeImportDTO>
     */
    private function createContentNodes(
        array $rows,
        int $titleIndex,
        ?int $contentIndex,
        ?int $slugIndex
    ): array {
        $nodes = [];
        $order = 0;

        foreach ($rows as $rowInfo) {
            $row = $rowInfo['data'];
            $lineNumber = $rowInfo['line'];

            // Extract title
            $nodeTitle = trim($row[$titleIndex] ?? "Row {$lineNumber}");
            if (empty($nodeTitle)) {
                $nodeTitle = "Row {$lineNumber}";
            }

            // Extract slug
            $nodeSlug = $slugIndex !== null && isset($row[$slugIndex])
                ? $this->generateSlug(trim($row[$slugIndex]))
                : $this->generateSlug($nodeTitle) . "-{$lineNumber}";

            // Extract content
            $content = '';
            if ($contentIndex !== null && isset($row[$contentIndex])) {
                $content = $this->textToHtml(trim($row[$contentIndex]));
            }

            $nodes[] = new ContentNodeImportDTO(
                title: $nodeTitle,
                slug: $nodeSlug,
                type: ContentNodeType::PARAGRAPH,
                content: $content,
                contentBlocks: [],
                order: $order++,
                sourceFile: null,
                lineNumber: $lineNumber
            );
        }

        return $nodes;
    }

    /**
     * Convert plain text to HTML.
     */
    private function textToHtml(string $text): string
    {
        // Escape HTML entities for security
        $html = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Convert newlines to line breaks
        $html = nl2br($html);

        // Wrap in paragraph tags
        $html = '<p>' . $html . '</p>';

        return trim($html);
    }
}
