# Phase 5 - Reader Implementation Plan

## Executive Summary

Phase 5 implements the Reader application on top of Application APIs. The Reader is the primary interface for consuming content (books, audio, video, manuscripts).

**Status**: 📋 PLANNED

---

## Required Features (Per Contract)

### 1. Entity Resolution
- Resolve entity by ID, slug, or deep link
- Handle redirects for moved/renamed entities
- Support entity type detection

### 2. Node Navigation
- Navigate content tree (next/previous)
- Jump to specific node by ID or slug
- Breadcrumb generation
- Table of contents generation

### 3. Content Rendering
- Render text content (HTML/Markdown)
- Render audio with player controls
- Render video with player controls
- Render manuscript images
- Handle mixed media content

### 4. Reading Position
- Save reading position per entity per user
- Restore last position on open
- Sync position across devices (future)
- Calculate progress percentage

### 5. Search
- Full-text search within entity
- Search results with context snippets
- Highlight matches
- Filter by node type

### 6. Deep Links
- Generate links to specific nodes
- Support timestamp links for audio/video
- Handle anchor links within content

---

## Architecture

```
┌─────────────────────────────────────────────┐
│              Reader Frontend                │
│  (React/Vue components - Phase 10)          │
└─────────────────┬───────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────┐
│           Reader Use Cases                  │
│  - ResolveEntity                            │
│  - NavigateToNode                           │
│  - RenderContent                            │
│  - SaveReadingPosition                      │
│  - GetReadingPosition                       │
│  - SearchInEntity                           │
│  - GenerateDeepLink                         │
└─────────────────┬───────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────┐
│         Application Services                │
│  - EntityReaderService                      │
│  - ContentRenderer                          │
│  - ReadingPositionService                   │
│  - EntitySearchService                      │
└─────────────────┬───────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────┐
│          Domain Layer                       │
│  - Entity (Book, Audio, Video, Manuscript)  │
│  - ContentTree                              │
│  - ContentNode                              │
│  - ReadingPosition (Value Object)           │
│  - SearchResult (DTO)                       │
└─────────────────┬───────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────┐
│        Repository Interfaces                │
│  - EntityRepositoryInterface                │
│  - ContentNodeRepositoryInterface           │
│  - ReadingPositionRepositoryInterface       │
└─────────────────────────────────────────────┘
```

---

## Implementation Order

### Step 1: Domain Value Objects & DTOs
1. `ReadingPosition` value object
2. `SearchResult` DTO
3. `RenderedContent` DTO
4. `NavigationContext` DTO

### Step 2: Repository Interfaces
1. `ReadingPositionRepositoryInterface`
2. Extend `ContentNodeRepositoryInterface` with navigation methods

### Step 3: Application Services
1. `EntityReaderService` - orchestrate reading operations
2. `ContentRenderer` - render content based on type
3. `ReadingPositionService` - save/restore positions
4. `EntitySearchService` - search within entity content

### Step 4: Use Cases
1. `ResolveEntity` - get entity by ID/slug/link
2. `NavigateToNode` - navigate content tree
3. `RenderContent` - render node content
4. `SaveReadingPosition` - persist position
5. `GetReadingPosition` - restore position
6. `SearchInEntity` - full-text search
7. `GenerateDeepLink` - create shareable links

### Step 5: Infrastructure
1. SQL implementation of `ReadingPositionRepository`
2. MongoDB implementation (optional)
3. Search adapter (Phase 8 will replace if needed)

### Step 6: Tests
1. Unit tests for all use cases
2. Integration tests for reading flow
3. Performance tests for large content trees

---

## File Structure

```
src/
├── Domain/
│   ├── ValueObject/
│   │   └── ReadingPosition.php
│   └── DTO/
│       ├── SearchResult.php
│       ├── RenderedContent.php
│       └── NavigationContext.php
│
├── Application/
│   ├── UseCase/Reader/
│   │   ├── ResolveEntity.php
│   │   ├── NavigateToNode.php
│   │   ├── RenderContent.php
│   │   ├── SaveReadingPosition.php
│   │   ├── GetReadingPosition.php
│   │   ├── SearchInEntity.php
│   │   └── GenerateDeepLink.php
│   │
│   └── Service/
│       ├── EntityReaderService.php
│       ├── ContentRenderer.php
│       ├── ReadingPositionService.php
│       └── EntitySearchService.php
│
└── Infrastructure/
    └── Persistence/
        ├── SqlReadingPositionRepository.php
        └── MongoReadingPositionRepository.php (optional)
```

---

## Dependencies

### From Previous Phases
- ✅ Phase 1: Domain entities (Book, Audio, Video, Manuscript)
- ✅ Phase 2: Repository interfaces and implementations
- ✅ Phase 3: Application use cases for Entity and Content
- ✅ Phase 4: Import system (for content creation)

### External Dependencies
- **Search**: Will be replaced in Phase 8
- **Media rendering**: May need external libraries for audio/video
- **PDF rendering**: If manuscripts include PDFs

---

## Acceptance Criteria

### Entity Resolution
- [ ] Can resolve entity by ID
- [ ] Can resolve entity by slug
- [ ] Handles non-existent entities gracefully
- [ ] Returns entity type information

### Node Navigation
- [ ] Can get next/previous sibling
- [ ] Can get first/last child
- [ ] Can jump to node by ID
- [ ] Generates breadcrumb trail
- [ ] Generates table of contents

### Content Rendering
- [ ] Renders text as HTML
- [ ] Preserves formatting
- [ ] Handles audio player markup
- [ ] Handles video player markup
- [ ] Handles image galleries for manuscripts

### Reading Position
- [ ] Saves position with timestamp
- [ ] Restores last position
- [ ] Calculates progress percentage
- [ ] Handles edge cases (start, end)

### Search
- [ ] Searches all node content
- [ ] Returns matching nodes with snippets
- [ ] Highlights match positions
- [ ] Orders by relevance

### Deep Links
- [ ] Generates URL for entity
- [ ] Generates URL for specific node
- [ ] Supports timestamp for media
- [ ] Validates link format

---

## Security Considerations

1. **Authorization**: Ensure user has access to entity before rendering
2. **Path Traversal**: Validate all file paths for media
3. **XSS**: Sanitize rendered content
4. **IDOR**: Verify entity ownership/access rights
5. **Rate Limiting**: Protect search endpoint from abuse

---

## Performance Considerations

1. **Lazy Loading**: Load nodes on demand for large trees
2. **Caching**: Cache rendered content where appropriate
3. **Pagination**: Paginate search results
4. **Progress Calculation**: Efficient algorithm for large trees

---

## Open Questions

1. Should reading position be user-specific or session-based?
2. How to handle concurrent position updates from multiple devices?
3. Should search be implemented now or deferred to Phase 8?
4. What media player library to use for audio/video?
5. How to handle DRM-protected content (if applicable)?

---

## Estimated Effort

| Component | Estimated Time | Complexity |
|-----------|---------------|------------|
| Value Objects & DTOs | 2 hours | Low |
| Repository Interfaces | 1 hour | Low |
| Application Services | 6 hours | Medium |
| Use Cases | 8 hours | Medium |
| Infrastructure (SQL) | 4 hours | Medium |
| Tests | 6 hours | Medium |
| **Total** | **~27 hours** | |

---

## Next Steps

1. Review and approve this plan
2. Create domain value objects and DTOs
3. Implement repository interfaces
4. Build application services
5. Create use cases
6. Write comprehensive tests
7. Document API contracts
8. Prepare for Phase 6 (Studio)

---

## Sign-off

**Phase**: 5 - Reader
**Status**: 📋 PLANNED
**Date**: 2024
**Dependencies**: Phases 1-4 complete
**Estimated Duration**: 27 hours

**STOP** - Awaiting approval to proceed with implementation.
