# COMPATIBILITY MATRIX

## Entity Types Compatibility

| Legacy Type | New Type | Status | Notes |
|-------------|----------|--------|-------|
| Book | Book | ✅ Preserve | All properties and relations must be maintained |
| Audio | Audio | ✅ Preserve | Duration, format, bitrate, sample_rate required |
| Video | Video | ✅ Preserve | Duration, format required |
| Manuscript | Manuscript | ✅ Preserve | All manuscript-specific fields required |

**Invariant:** No entity type may be removed or merged without explicit migration plan.

---

## Content Node Types Compatibility

### Book Content Types

| Legacy Type | New Type | HTML Tag | Behavior | Status |
|-------------|----------|----------|----------|--------|
| sub-book | SUB_BOOK | h1 | container | ✅ Preserve |
| part | PART | h2 | container | ✅ Preserve |
| bab | BAB | h3 | container | ✅ Preserve |
| chapter | CHAPTER | h4 | container | ✅ Preserve (default) |
| masalah | MASALAH | h5 | container | ✅ Preserve |
| section | SECTION | h6 | container | ✅ Preserve |
| page | PAGE | h4 | marker | ✅ Preserve |

### Manuscript Content Types

| Legacy Type | New Type | HTML Tag | Behavior | Status |
|-------------|----------|----------|----------|--------|
| (all book types) | - | - | - | ✅ Inherited |
| folio | FOLIO | h4 | marker | ✅ Preserve |

### Audio Content Types

| Legacy Type | New Type | HTML Tag | Behavior | Status |
|-------------|----------|----------|----------|--------|
| segment | SEGMENT | h4 | marker | ✅ Preserve (default) |
| track | TRACK | h4 | marker | ✅ Preserve |
| marker | MARKER | h5 | marker | ✅ Preserve |

### Video Content Types

| Legacy Type | New Type | HTML Tag | Behavior | Status |
|-------------|----------|----------|----------|--------|
| scene | SCENE | h4 | marker | ✅ Preserve (default) |
| shot | SHOT | h5 | marker | ✅ Preserve |
| segment | SEGMENT | h4 | marker | ✅ Preserve |

**Invariant:** Content node type validation MUST match legacy `ContentNodeType::allowedFor()` behavior.

---

## Storage Compatibility

### MySQL → SQL Mapping

| Legacy Table | New Table | Status | Migration Required |
|--------------|-----------|--------|-------------------|
| books | books | ✅ Direct | Schema compatibility check |
| audios | audios | ✅ Direct | Schema compatibility check |
| videos | videos | ✅ Direct | Schema compatibility check |
| manuscripts | manuscripts | ✅ Direct | Schema compatibility check |
| versions | versions | ✅ Direct | Schema compatibility check |
| tags | tags | ✅ Direct | Schema compatibility check |
| categories | categories | ✅ Direct | Schema compatibility check |
| authors | authors | ✅ Direct | Schema compatibility check |
| bookers | bookers | ✅ Direct | Schema compatibility check |
| publishers | publishers | ✅ Direct | Schema compatibility check |
| topics | topics | ✅ Direct | Schema compatibility check |
| collections | collections | ✅ Direct | Schema compatibility check |
| series | series | ✅ Direct | Schema compatibility check |
| languages | languages | ✅ Direct | Schema compatibility check |
| shelves | shelves | ✅ Direct | Schema compatibility check |
| comments | comments | ✅ Direct | Schema compatibility check |
| notes | notes | ✅ Direct | Schema compatibility check |
| activities | activities | ✅ Direct | Schema compatibility check |
| deletions | deletions | ✅ Direct | Schema compatibility check |
| reading_positions | reading_positions | ✅ Direct | Schema compatibility check |
| users | users | ⚠️ Review | Auth system dependency |

### MongoDB → Document Store Mapping

| Legacy Collection | New Collection | Status | Migration Required |
|-------------------|----------------|--------|-------------------|
| book_children | content_nodes | ⚠️ Transform | Add entity_type discriminator |
| manuscript_pages | content_nodes | ⚠️ Transform | Add entity_type discriminator |
| audio_segments | content_nodes | ⚠️ Transform | Add entity_type discriminator |
| video_segments | content_nodes | ⚠️ Transform | Add entity_type discriminator |
| entity_contents | content_nodes | ⚠️ Transform | Consolidate into unified schema |

**Proposal:** Consider unifying MongoDB collections into single `content_nodes` collection with `entity_type` and `entity_id` fields for simplified querying.

---

## Routing Compatibility

### Slug-Based Routing (VERIFIED)

| Legacy Pattern | New Pattern | Status |
|----------------|-------------|--------|
| `/books/{slug}` | `/books/{slug}` | ✅ Preserve |
| `/audios/{slug}` | `/audios/{slug}` | ✅ Preserve |
| `/videos/{slug}` | `/videos/{slug}` | ✅ Preserve |
| `/manuscripts/{slug}` | `/manuscripts/{slug}` | ✅ Preserve |
| `/studio/{type}/{slug}/{childId?}` | `/studio/{type}/{slug}/{childId?}` | ✅ Preserve |
| `/reader/{type}/{slug}/{childId?}` | `/reader/{type}/{slug}/{childId?}` | ✅ Preserve |

**Invariant:** All entities MUST remain accessible by slug, not just UUID.

---

## API Compatibility

### REST Endpoints

| Endpoint | Method | Status | Notes |
|----------|--------|--------|-------|
| `/api/books` | GET, POST | ✅ Preserve | Auth required |
| `/api/books/{book}` | GET, PUT, DELETE | ✅ Preserve | Auth required |
| `/api/audios` | GET, POST | ✅ Preserve | Auth required |
| `/api/videos` | GET, POST | ✅ Preserve | Auth required |
| `/api/manuscripts` | GET, POST | ✅ Preserve | Auth required |
| `/api/categories` | GET, POST | ✅ Preserve | Auth required |
| `/api/tags` | GET, POST | ✅ Preserve | Auth required |
| `/api/collections` | GET, POST | ✅ Preserve | Auth required |
| `/api/series` | GET, POST | ✅ Preserve | Auth required |
| `/api/books/{book}/contents/reorder` | POST | ✅ Preserve | Batch reorder |
| `/api/books/{book}/export/{format}` | GET | ✅ Preserve | Export formats |
| `/api/book-children/{id}/save` | POST | ✅ Preserve | Compatibility endpoint |
| `/api/book-children/{id}/restore/{version?}` | POST | ✅ Preserve | Version restore |
| `/api/segments` | POST, PUT, DELETE | ✅ Preserve | Audio/Video segments |

---

## Polymorphic Relations Compatibility

### Pivot Tables

| Relation | Pivot Table | Status |
|----------|-------------|--------|
| Entity ↔ Tag | taggables | ✅ Preserve |
| Entity ↔ Category | categorizables | ✅ Preserve |
| Entity ↔ Author | authorables | ✅ Preserve |
| Entity ↔ Booker | bookables | ✅ Preserve |
| Entity ↔ Collection | collectables | ✅ Preserve |
| Entity ↔ Series | seriables | ✅ Preserve |

**Invariant:** Pivot table structure must remain compatible for seamless migration.

---

## Versioning Compatibility

### SQL Versions

| Property | Legacy | New | Status |
|----------|--------|-----|--------|
| Polymorphic relation | ✅ | ✅ | Preserve |
| Publisher relation | ✅ | ✅ | Preserve |
| Language relation | ✅ | ✅ | Preserve |
| Shelf relation | ✅ | ✅ | Preserve |
| File storage | ✅ | ✅ | Preserve |
| Edition tracking | ✅ | ✅ | Preserve |

### MongoDB In-Document Versions

| Property | Legacy | New | Status |
|----------|--------|-----|--------|
| content_blocks snapshot | ✅ | ✅ | Preserve |
| created_at timestamp | ✅ | ✅ | Preserve |
| description | ✅ | ✅ | Preserve |

**Invariant:** Both versioning systems must coexist during migration.

---

## Caching Compatibility

| Cache Key Pattern | TTL | Status |
|-------------------|-----|--------|
| `entity.{class}.{id}` | 1 hour | ✅ Preserve |
| `entity.{class}.{id}.with_relations` | 1 hour | ✅ Preserve |
| `entity.{class}.{id}.stats` | 30 minutes | ✅ Preserve |

**Note:** Cache invalidation strategy must be preserved to prevent stale data.

---

## Media Streaming Compatibility

| Feature | Legacy | New | Status |
|---------|--------|-----|--------|
| HTTP Range Requests | ✅ | ✅ | Preserve |
| Video streaming | ✅ | ✅ | Preserve |
| Audio streaming | ✅ | ✅ | Preserve |
| Path-based routing | ✅ | ⚠️ Review | Security audit required |

**Security Requirements:**
- Path traversal protection: REQUIRED
- Symlink traversal protection: REQUIRED
- Authorization checks: REQUIRED
- MIME type validation: REQUIRED

---

## Reading Position Compatibility

| Property | Legacy | New | Status |
|----------|--------|-----|--------|
| Per-user tracking | ✅ | ✅ | Preserve |
| Per-entity tracking | ✅ | ✅ | Preserve |
| Node-level position | ✅ | ✅ | Preserve |
| Scroll offset | ✅ | ✅ | Preserve |
| Timestamp | ✅ | ✅ | Preserve |

---

## Soft Delete Compatibility

| Model | Uses Soft Deletes | Status |
|-------|------------------|--------|
| All Entity types | ✅ | Preserve |
| Tags | ✅ | Preserve |
| Categories | ✅ | Preserve |
| Authors | ✅ | Preserve |
| Bookers | ✅ | Preserve |
| Publishers | ✅ | Preserve |
| Topics | ✅ | Preserve |
| Collections | ✅ | Preserve |
| Series | ✅ | Preserve |
| Languages | ✅ | Preserve |
| Shelves | ✅ | Preserve |
| Comments | ✅ | Preserve |
| Notes | ✅ | Preserve |
| Activities | ❌ | Review |
| Deletions | ✅ | Preserve (audit trail) |
| Versions | ✅ | Preserve |
| ReadingPositions | ✅ | Preserve |

---

## Search Compatibility

| Searchable Field | Legacy | New | Status |
|-----------------|--------|-----|--------|
| Entity title | ✅ | ✅ | Preserve |
| Entity slug | ✅ | ✅ | Preserve |
| Author name | ✅ | ✅ | Preserve |
| Series name | ✅ | ✅ | Preserve |
| Taxonomy (tags/categories) | ✅ | ✅ | Preserve |
| Content node titles | ✅ | ✅ | Preserve |
| Content text | ✅ | ✅ | Preserve |

**Unknown:** Full-text search implementation details (database vs external engine).

---

## Import/Export Compatibility

### Export Formats (VERIFIED)

| Format | Books | Manuscripts | Audio | Video | Status |
|--------|-------|-------------|-------|-------|--------|
| PDF | ✅ | ✅ | ❌ | ❌ | Preserve |
| DOCX | ✅ | ✅ | ❌ | ❌ | Preserve |
| TXT | ✅ | ✅ | ❌ | ❌ | Preserve |
| Markdown | ✅ | ✅ | ❌ | ❌ | Preserve |

### Import Formats (UNKNOWN)

| Format | Status | Notes |
|--------|--------|-------|
| Markdown | ❓ | Requires verification |
| TXT | ❓ | Requires verification |
| PDF | ❓ | Text extraction? |
| DOCX | ❓ | Requires verification |
| CSV | ❓ | Metadata import? |
| XLSX | ❓ | Metadata import? |
| ODS | ❓ | Metadata import? |
| Images (manuscripts) | ❓ | Page upload? |
| Audio files | ❓ | Direct upload? |
| Video files | ❓ | Direct upload? |
| Transcripts | ❓ | Sync with media? |

---

## Security Test Matrix

| Test Case | Priority | Status |
|-----------|----------|--------|
| Path traversal (`../../../`) | 🔴 Critical | REQUIRED |
| Symlink traversal | 🔴 Critical | REQUIRED |
| Unauthorized media access | 🔴 Critical | REQUIRED |
| IDOR (Insecure Direct Object Reference) | 🔴 Critical | REQUIRED |
| Authorization bypass | 🔴 Critical | REQUIRED |
| XML entity attacks (XXE) | 🟠 High | REQUIRED |
| Command execution surfaces | 🔴 Critical | REQUIRED |
| XSS (Cross-Site Scripting) | 🔴 Critical | REQUIRED |
| Stored HTML injection | 🔴 Critical | REQUIRED |
| File upload abuse | 🔴 Critical | REQUIRED |
| Mass assignment | 🟠 High | REQUIRED |
| MongoDB injection | 🟠 High | REQUIRED |

---

## Migration Risk Assessment

### Low Risk (Direct Mapping)
- Entity metadata tables
- Taxonomy tables
- Actor tables (authors, bookers, publishers)
- Version records
- Reading positions

### Medium Risk (Transformation Required)
- MongoDB collection consolidation
- Content node unification
- Polymorphic relation optimization

### High Risk (Behavioral Changes)
- Search implementation
- Media streaming security
- Caching strategy
- Background job processing

### Unknown Risk (Requires Investigation)
- Import pipeline implementations
- Transcript synchronization
- External service integrations
- Backup/recovery procedures

---

## Open Questions for Approval

1. **MongoDB Consolidation:** Should we merge the four content collections into a single `content_nodes` collection with discriminators?

2. **Repository Pattern:** Should we introduce repository interfaces between domain and persistence, or maintain direct Eloquent/Mongo access?

3. **Use Case Layer:** Should we create explicit application use cases for all mutations, or allow some controller-level logic?

4. **Search Implementation:** Should search remain database-based or migrate to dedicated search engine (Elasticsearch, Meilisearch)?

5. **Import Pipeline:** What import formats are business-critical and must be preserved?

6. **API Versioning:** Should new API be `/api/v1/` from start, or maintain current unversioned paths?

7. **Frontend Strategy:** Should we maintain Vue/Inertia or consider alternative frameworks?

8. **Media Storage:** Should media files remain in local storage or migrate to cloud storage (S3, etc.)?
