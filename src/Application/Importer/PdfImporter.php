<?php

declare(strict_types=1);

namespace App\Application\Importer;

use App\Application\DTO\ContentNodeImportDTO;
use App\Domain\Content\ContentNodeType;

/**
 * Importer for PDF files.
 *
 * @label PROPOSED - PDF importer for Phase 4 (Import System)
 * 
 * Note: This is a basic implementation using built-in PHP functions.
 * For production use, consider integrating a proper PDF parsing library
 * such as Smalot/PdfParser or pdftotext.
 */
final class PdfImporter implements EntityImporterInterface
{
    /**
     * @var string Default entity type for PDF imports
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

        // Check if file is readable
        if (!is_readable($filePath)) {
            throw new \RuntimeException("File not readable: {$filePath}");
        }

        // Validate PDF magic bytes
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new \RuntimeException("Failed to open file: {$filePath}");
        }

        $magicBytes = fread($handle, 5);
        fclose($handle);

        if ($magicBytes !== '%PDF-') {
            throw new \RuntimeException("Invalid PDF file: {$filePath}");
        }

        // Extract text from PDF
        // Note: This is a simplified extraction. Production should use a proper library.
        $text = $this->extractTextFromPdf($filePath);

        if (empty(trim($text))) {
            throw new \RuntimeException("Failed to extract text from PDF: {$filePath}");
        }

        // Extract title from first line or filename
        $lines = explode("\n", $text);
        $title = $this->extractTitle($lines, $filePath);

        // Generate slug from title
        $slug = $this->generateSlug($title);

        // Parse content into nodes based on structure
        $contentNodes = $this->parseContentIntoNodes($text, $title);

        // Get PDF metadata
        $metadata = $this->extractPdfMetadata($filePath);
        $metadata['original_format'] = 'pdf';
        $metadata['page_count'] = $this->getPageCount($filePath);

        return new \App\Application\DTO\EntityImportDTO(
            type: $this->entityType,
            title: $title,
            slug: $slug,
            description: null,
            filePath: $filePath,
            metadata: $metadata,
            taxonomy: [],
            contentNodes: $contentNodes
        );
    }

    public function supports(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return $extension === 'pdf';
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Extract text from PDF file.
     * 
     * Note: This is a basic implementation. For production, use a proper PDF parsing library.
     */
    private function extractTextFromPdf(string $filePath): string
    {
        // Try to use pdftotext if available
        if (function_exists('shell_exec')) {
            $output = shell_exec('pdftotext ' . escapeshellarg($filePath) . ' - 2>/dev/null');
            if ($output !== null && !empty(trim($output))) {
                return $output;
            }
        }

        // Fallback: Try to read raw text streams from PDF
        // This is very basic and won't work for all PDFs
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read PDF file: {$filePath}");
        }

        // Extract text from PDF text streams (very basic)
        $text = '';
        if (preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                // Remove PDF operators and extract text
                $streamText = preg_replace('/[0-9.]+\s*[TcDw]/', '', $stream);
                $streamText = preg_replace('/\/[A-Za-z0-9]+\s+[0-9.]+\s*Tf/', '', $streamText);
                
                // Extract text between parentheses (Tj operator)
                if (preg_match_all('/\((.*?)\)\s*Tj/s', $streamText, $textMatches)) {
                    foreach ($textMatches[1] as $t) {
                        // Decode PDF escape sequences
                        $decoded = str_replace(
                            ['\\\\', '\\(', '\\)', '\n', '\r', '\t'],
                            ['\\', '(', ')', "\n", "\r", "\t"],
                            $t
                        );
                        $text .= $decoded . ' ';
                    }
                }
            }
        }

        // Clean up extracted text
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        return $text;
    }

    /**
     * Extract title from first non-empty line or filename.
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

        // Fallback to filename without extension
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
     * Parse PDF content into content nodes.
     *
     * @return array<ContentNodeImportDTO>
     */
    private function parseContentIntoNodes(string $content, string $rootTitle): array
    {
        $nodes = [];
        $order = 0;

        // Create root node for the document
        $introContent = $this->extractIntroContent($content);
        $nodes[] = new ContentNodeImportDTO(
            title: $rootTitle,
            slug: $this->generateSlug($rootTitle),
            type: ContentNodeType::CHAPTER,
            content: $this->textToHtml($introContent),
            contentBlocks: [],
            order: $order++
        );

        // Split by potential section breaks (double newlines or page breaks)
        $sections = preg_split('/\n\s*\n|\f/', $content);
        $sectionOrder = 0;

        foreach ($sections as $index => $section) {
            $trimmed = trim($section);
            if (empty($trimmed)) {
                continue;
            }

            // Skip if this looks like the intro we already used
            if ($index === 0 && strpos($trimmed, substr($introContent, 0, 50)) !== false) {
                continue;
            }

            // Check if this looks like a section header
            if ($this->isSectionHeader($trimmed, $index)) {
                $nodes[] = $this->createSectionNode(
                    $trimmed,
                    $trimmed,
                    0,
                    $order++
                );
            } else {
                // Regular content section
                $nodes[] = $this->createSectionNode(
                    $trimmed,
                    "Section " . ($sectionOrder + 1),
                    $sectionOrder++,
                    $order++
                );
            }
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
     * Extract introductory content.
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
        // Escape HTML entities for security
        $html = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Convert double newlines to paragraph breaks
        $html = preg_replace('/\n\s*\n/', '</p><p>', $html);

        // Convert single newlines to line breaks
        $html = nl2br($html);

        // Wrap in paragraph tags
        $html = '<p>' . $html . '</p>';

        return trim($html);
    }

    /**
     * Extract basic metadata from PDF.
     *
     * @return array<string, mixed>
     */
    private function extractPdfMetadata(string $filePath): array
    {
        $metadata = [];
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            return $metadata;
        }

        // Try to extract basic info dictionary entries
        if (preg_match('/\/Author\s*\((.*?)\)/s', $content, $matches)) {
            $metadata['author'] = $this->decodePdfString($matches[1]);
        }

        if (preg_match('/\/Title\s*\((.*?)\)/s', $content, $matches)) {
            $metadata['pdf_title'] = $this->decodePdfString($matches[1]);
        }

        if (preg_match('/\/Creator\s*\((.*?)\)/s', $content, $matches)) {
            $metadata['creator'] = $this->decodePdfString($matches[1]);
        }

        if (preg_match('/\/Producer\s*\((.*?)\)/s', $content, $matches)) {
            $metadata['producer'] = $this->decodePdfString($matches[1]);
        }

        if (preg_match('/\/CreationDate\s*:\s*([0-9]+)/s', $content, $matches)) {
            $metadata['creation_date'] = $matches[1];
        }

        return $metadata;
    }

    /**
     * Decode PDF string escape sequences.
     */
    private function decodePdfString(string $str): string
    {
        return str_replace(
            ['\\\\', '\\(', '\\)', '\n', '\r', '\t'],
            ['\\', '(', ')', "\n", "\r", "\t"],
            $str
        );
    }

    /**
     * Get approximate page count from PDF.
     */
    private function getPageCount(string $filePath): int
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return 0;
        }

        // Count /Page objects (very approximate)
        if (preg_match_all('/\d+\s+\d+\s+obj[^\/]*\/Type\s*\/Page[^s]/s', $content, $matches)) {
            return count($matches[0]);
        }

        return 0;
    }
}
