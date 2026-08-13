# Phase 4 - Import System

## Mission

Implement separate import pipelines for all supported file types. All importers must produce validated DTOs before persistence through Application Use Cases.

---

## Required Importers

### Document Importers
1. **MarkdownImporter** - `.md` files
2. **TextImporter** - `.txt` files
3. **PdfImporter** - `.pdf` files
4. **DocxImporter** - `.docx` files

### Spreadsheet Importers
5. **CsvImporter** - `.csv` files
6. **XlsxImporter** - `.xlsx` files
7. **OdsImporter** - `.ods` files

### Media Importers
8. **ImageImporter** - Manuscript images (`.jpg`, `.png`, `.tiff`, etc.)
9. **AudioImporter** - Audio files (`.mp3`, `.wav`, `.flac`, etc.)
10. **VideoImporter** - Video files (`.mp4`, `.mkv`, `.avi`, etc.)

### Specialized Importers
11. **TranscriptImporter** - Transcript files (`.srt`, `.vtt`, `.json`)

---

## Architecture

### Import DTO Structure

```php
namespace Domain\Import;

class ImportResult {
    public function __construct(
        public readonly string $importId,
        public readonly array $contentNodes,
        public readonly ?Entity $entity,
        public readonly array $metadata,
        public readonly array $warnings,
        public readonly array $errors
    ) {}
}
```

### Importer Interface

```php
namespace Domain\Import;

interface ImporterInterface {
    /**
     * @VERIFIED - All importers must support this signature
     */
    public function import(string $filePath): ImportResult;
    
    /**
     * @VERIFIED - Check if importer supports file type
     */
    public function supports(string $filePath): bool;
    
    /**
     * @VERIFIED - Get supported extensions
     */
    public function getSupportedExtensions(): array;
}
```

### Base Importer

```php
namespace Infrastructure\Import;

abstract class AbstractImporter implements ImporterInterface {
    /**
     * @VERIFIED - Common validation logic
     */
    protected function validateFile(string $filePath): void;
    
    /**
     * @VERIFIED - Common metadata extraction
     */
    protected function extractBasicMetadata(string $filePath): array;
    
    /**
     * @VERIFIED - Sanitize content before creating nodes
     */
    protected function sanitizeContent(string $content): string;
}
```

---

## Implementation Status

### Phase 4.1 - Document Importers ✅ PROPOSED
- [ ] MarkdownImporter
- [ ] TextImporter
- [ ] PdfImporter (requires external library)
- [ ] DocxImporter (requires external library)

### Phase 4.2 - Spreadsheet Importers ⏸️ PROPOSED
- [ ] CsvImporter
- [ ] XlsxImporter (requires external library)
- [ ] OdsImporter (requires external library)

### Phase 4.3 - Media Importers ⏸️ PROPOSED
- [ ] ImageImporter
- [ ] AudioImporter
- [ ] VideoImporter

### Phase 4.4 - Specialized Importers ⏸️ PROPOSED
- [ ] TranscriptImporter

---

## Security Requirements

@VERIFIED - All importers must:

1. **Validate file type by content, not extension**
   - Use magic bytes / MIME type detection
   - Reject files with mismatched extension/content

2. **Prevent path traversal**
   - Resolve real paths
   - Ensure files are within allowed directories

3. **Limit file sizes**
   - Configurable max size per importer type
   - Fail fast on oversized files

4. **Sanitize all extracted content**
   - Remove potentially dangerous HTML
   - Escape special characters
   - Validate encoding

5. **Handle errors gracefully**
   - Never expose internal paths in error messages
   - Log security violations
   - Return structured error information

---

## Testing Requirements

@VERIFIED - Each importer must have:

1. **Unit tests** for parsing logic
2. **Integration tests** with real files
3. **Security tests** for:
   - Path traversal attempts
   - Malicious file content
   - Oversized files
   - Invalid encodings
4. **Compatibility tests** against legacy import behavior

---

## Dependency Order

Importers should be implemented in this order:

1. **MarkdownImporter** - Simplest, most common format
2. **TextImporter** - Trivial implementation
3. **CsvImporter** - Simple structure
4. **ImageImporter** - Metadata only, no content parsing
5. **AudioImporter** - Metadata extraction
6. **VideoImporter** - Metadata extraction
7. **TranscriptImporter** - Structured text
8. **DocxImporter** - Complex, requires library
9. **PdfImporter** - Complex, requires library
10. **XlsxImporter** - Complex, requires library
11. **OdsImporter** - Complex, requires library

---

## Open Questions

1. **Should importers create entities directly or return DTOs for use cases?**
   - Option A: Importers call use cases internally
   - Option B: Importers return DTOs, caller uses use cases
   - Recommendation: Option B for better separation of concerns

2. **How to handle multi-file imports (e.g., manuscript with images)?**
   - Need a composite importer or orchestrator?

3. **Should we support batch imports?**
   - Single call importing multiple files?

4. **What metadata should be extracted automatically?**
   - Title, author, date from file properties?
   - Custom metadata from content analysis?

---

## Next Steps

1. Implement MarkdownImporter (simplest case)
2. Implement TextImporter
3. Create test fixtures for all formats
4. Implement security test suite
5. Add remaining importers based on priority

---

**Status**: 🔄 IN PROGRESS  
**Current Focus**: Phase 4.1 - Document Importers (Markdown, Text)  
**Tests**: Pending implementation
