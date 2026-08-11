# DOMAIN CONTRACT

## Core Domain Concepts

### Entity (VERIFIED)

**Definition:** The root abstraction for all content types in the system.

**Properties:**
- `id`: UUID (string)
- `title`: string
- `slug`: string (unique, auto-generated)
- `serial_number`: int (auto-incremented)
- `created_at`, `updated_at`, `deleted_at`: timestamps

**Behavior:**
- All entities use slug-based routing (`getRouteKeyName()`)
- Soft deletes enabled via `SoftDeletes` trait
- Caching via `getCached()`, `getCachedWithRelations()`, `getCachedStats()`
- Polymorphic relations for tags, categories, authors, etc.

**Invariant:** Every entity MUST have a unique slug within its type.

---

### EntityType (VERIFIED)

**Definition:** Enum defining the four supported entity types.

**Values:**
```php
EntityType::BOOK       = 'book'
EntityType::AUDIO      = 'audio'
EntityType::VIDEO      = 'video'
EntityType::MANUSCRIPT = 'manuscript'
```

**Methods:**
- `modelClass(): string` - Returns the concrete model class
- `defaultFormat(): string` - Default file format (pdf/mp3/mp4)
- `supportsDuration(): bool` - true for audio/video
- `supportsPages(): bool` - true for books/manuscripts
- `label(): string` - Arabic label

**Invariant:** EntityType is immutable and closed to extension without migration.

---

### ContentNode (VERIFIED)

**Definition:** Hierarchical content units belonging to an Entity.

**Properties:**
- `_id`: UUID string (MongoDB)
- `entity_id`: UUID (foreign key to MySQL entity)
- `parent_id`: UUID|null (self-referential for hierarchy)
- `type`: ContentNodeType
- `title`: string
- `slug`: string (unique within entity)
- `order`: int (sibling ordering)
- `content_blocks`: array (structured content)
- `content`: string (HTML rendering)
- `metadata`: array
- `versions`: array (snapshot history)

**Behavior:**
- Hierarchical via `parent_id`
- Ordered via `order` field
- Type-safe via `ContentNodeType` validation
- Version snapshots on manual edits

**Invariant:** 
- ContentNode type MUST be valid for parent entity type
- `order` MUST be unique among siblings

---

### ContentNodeType (VERIFIED)

**Definition:** Enum defining allowed content node types per entity type.

**Book Types:**
- `SUB_BOOK` → h1 (container)
- `PART` → h2 (container)
- `BAB` → h3 (container)
- `CHAPTER` → h4 (container, default)
- `MASALAH` → h5 (container)
- `SECTION` → h6 (container)
- `PAGE` → h4 (marker)

**Manuscript Types:**
- All book types PLUS:
- `FOLIO` → h4 (marker)

**Audio Types:**
- `SEGMENT` → h4 (marker, default)
- `TRACK` → h4 (marker)
- `MARKER` → h5 (marker)

**Video Types:**
- `SCENE` → h4 (marker, default)
- `SHOT` → h5 (marker)
- `SEGMENT` → h4 (marker)

**Methods:**
- `allowedFor(EntityType): self[]` - Valid types per entity
- `getVisualMap(EntityType): array` - HTML tag + behavior mapping
- `defaultFor(EntityType): self` - Primary/default type
- `isValidFor(EntityType): bool` - Validation
- `label(): string` - Arabic label

**Invariant:** ContentNodeType MUST match parent entity type's allowed types.

---

### ContentTree (INFERRED)

**Definition:** The hierarchical structure formed by ContentNodes within an Entity.

**Operations:**
- `getRootNodes(): ContentNode[]` - Top-level nodes (parent_id = null)
- `getDescendants(ContentNode): ContentNode[]` - All children recursively
- `getBranch(ContentNode): ContentNode[]` - Node + all descendants
- `reorder(items: [{id, order, parent_id}])` - Batch reorder with reparenting

**Invariant:** Tree structure MUST NOT have cycles.

---

### Book (VERIFIED)

**Extends:** Entity

**Additional Properties:**
- `author`: string
- `isbn`: string|null
- `description`: string|null
- `cover_path`: string|null
- `file_path`: string|null

**Relations:**
- `children`: hasMany → BookChild (MongoDB)
- `topics`: belongsToMany → Topic
- `versions`: morphMany → Version
- `authors`, `bookers`, `tags`, `categories`: polymorphic many-to-many

**Display:** `display_name = "{title} - {author}"`

---

### Audio (VERIFIED)

**Extends:** Entity

**Additional Properties:**
- `duration`: int (seconds)
- `format`: string
- `bitrate`: int|null
- `sample_rate`: int|null
- `file_size`: int|null
- `description`: string|null
- `cover_path`: string|null
- `file_path`: string|null

**Computed:**
- `duration_in_minutes`: float
- `duration_formatted`: "HH:MM:SS" or "MM:SS"
- `bitrate_formatted`: "{kbps} kbps"
- `sample_rate_formatted`: "{Hz} Hz"

**Relations:**
- `children`: hasMany → AudioSegment (MongoDB)

---

### Video (VERIFIED)

**Extends:** Entity

**Additional Properties:**
- `duration`: int (seconds)
- `format`: string
- `description`: string|null
- `cover_path`: string|null
- `file_path`: string|null

**Relations:**
- `children`: hasMany → VideoSegment (MongoDB)

---

### Manuscript (VERIFIED)

**Extends:** Entity

**Additional Properties:**
- `original_title`: string|null
- `code`: string (work identifier, e.g., "ج-ش-م-م-م-ك-0074")
- `slug`: string
- `catalog_number`: string|null
- `scribe`: string|null
- `copy_date`: string|null
- `parts`: int|null
- `script_type`: string|null
- `dimensions`: string|null
- `lines_per_page`: int|null
- `inscriptions`: string|null
- `notes`: string|null
- `manuscript_century`: int|null
- `manuscript_century_label`: string|null
- `manuscript_start`: int|null
- `manuscript_end`: int|null
- `is_autograph`: bool|null
- `pages`: int|null
- `location`: string|null
- `description`: string|null
- `cover_path`: string|null
- `file_path`: string|null

**Computed:**
- `century_display`: Arabic century display (Hijri/Gregorian)
- `age`: years since creation
- `pages_formatted`: "{pages} صفحة"

**Methods:**
- `isAncient(): bool` - century < 15
- `isModern(): bool` - century >= 19

**Relations:**
- `children`: hasMany → ManuscriptPage (MongoDB)
- `versions`: hasMany → Version
- `siblings`: Manuscripts with same work code prefix

---

### Version (VERIFIED)

**Definition:** Publication version of an entity (book edition, manuscript copy, etc.)

**Properties:**
- `id`: UUID
- `versionable_id`: UUID (polymorphic)
- `versionable_type`: string (polymorphic)
- `publisher_id`: UUID|null
- `language_id`: int|null
- `shelf_id`: int|null
- `title`: string|null
- `file_path`: string|null
- `cover_path`: string|null
- `format`: string|null
- `file_size`: int
- `isbn`: string|null
- `pages`: int|null
- `published_year`: int|null
- `edition_number`: int

**Relations:**
- `versionable`: morphTo → Entity subclass
- `publisher`: belongsTo → Publisher
- `language`: belongsTo → Language
- `shelf`: belongsTo → Shelf

**Invariant:** Each version MUST belong to exactly one versionable entity.

---

### ReadingPosition (VERIFIED)

**Definition:** User's reading progress within an entity.

**Properties:**
- `user_id`: UUID
- `entity_id`: UUID (polymorphic)
- `entity_type`: string (polymorphic)
- `node_slug`: string
- `scroll_offset`: int
- `timestamp`: datetime

**Behavior:**
- Unique per user+entity combination
- Updated via `ReadingPositionService`

---

## Storage Semantics

### Hybrid Persistence (VERIFIED)

**MySQL Tables:**
- Entity metadata (books, audios, videos, manuscripts)
- Taxonomy (tags, categories, topics, collections, series)
- Actors (authors, bookers, publishers, users)
- Versions
- Reading positions
- Activity logs, comments, notes, deletions

**MongoDB Collections:**
- `book_children` - Book content hierarchy
- `manuscript_pages` - Manuscript page images/transcriptions
- `audio_segments` - Time-based audio segments
- `video_segments` - Time-based video segments
- `entity_contents` - Generic unified content

**Connection Strategy:**
- Entities loaded from MySQL
- Children loaded manually from MongoDB based on entity type
- No ORM-level join across databases

---

## Relationship Contract

### Polymorphic Relations (VERIFIED)

**Entity → Tags/Categories:**
```php
Entity::tags(): MorphToMany<Tag>
Entity::categories(): MorphToMany<Category>
```
Pivot tables: `taggables`, `categorizables`

**Entity → Authors/Bookers:**
```php
Entity::authors(): MorphToMany<Author>
Entity::bookers(): MorphToMany<Booker>
```
Pivot tables: `authorables`, `bookables`

**Entity → Versions:**
```php
Entity::versions(): MorphMany<Version>
```

**Entity → Activities/Comments/Notes/Deletions:**
```php
Entity::activities(): MorphMany<Activity>
Entity::comments(): MorphMany<Comment>
Entity::notes(): MorphMany<Note>
Entity::deletions(): MorphMany<Deletion>
```

**Entity → Collections/Series:**
```php
Entity::collections(): MorphToMany<Collection> (pivot: collectables)
Entity::series(): MorphToMany<Series> (pivot: seriables)
```

---

## Content Aggregation Contract

### aggregateFullContent() (VERIFIED)

**Purpose:** Render complete entity content as HTML for reading/export.

**Algorithm:**
1. Determine if entity supports hierarchy (Book/Manuscript vs Audio/Video)
2. For hierarchical:
   - Fetch root nodes (parent_id = null) ordered
   - Recursively render each node + descendants
3. For flat (Audio/Video):
   - Fetch all segments ordered by start_time/order
   - Render sequentially

**Output Format:**
```html
<h2 data-id="..." data-type="...">Title</h2>
<p>Content...</p>
<br/>
<h3 data-id="..." data-type="...">Child Title</h3>
...
```

**Attributes:**
- `data-id`: Node ID
- `data-type`: ContentNodeType
- `data-start-time`: (Audio/Video only)
- `data-segment-link`: true for clickable markers

---

## Caching Contract

### Entity Caching (VERIFIED)

**Cache Keys:**
- `entity.{class}.{id}` - Single entity
- `entity.{class}.{id}.with_relations` - Entity + relations
- `entity.{class}.{id}.stats` - Statistics (comments, reviews, tags count)

**TTL:**
- Entity: 1 hour
- Stats: 30 minutes

**Invalidation:**
- On entity update/delete
- Via `EntityCacheObserver`

---

## Security Requirements

### Path Handling (UNKNOWN - Requires Testing)

**Media Streaming:**
- Routes: `/stream/videos/{path}`, `/stream/audio/{path}`
- Controller: `MediaStreamController`
- Supports HTTP Range Requests

**Risks:**
- Path traversal via `../`
- Symlink following
- Unauthorized access to private files

**Required Tests:**
- Path traversal attempts
- Symlink traversal
- Authorization bypass
- Direct file access

---

## Unresolved Questions

1. **Transcript Synchronization:** How are transcripts synced with audio/video segments?
2. **Import Formats:** What import formats are fully supported (Markdown, DOCX, PDF, etc.)?
3. **Export Formats:** Complete list of export formats and their implementations?
4. **Search Index:** What fields are indexed for search? Custom search engine or database queries?
5. **Background Jobs:** Are there queued jobs for long-running operations?
6. **External APIs:** Any third-party service integrations?
7. **Backup Strategy:** Database backup and recovery procedures?
8. **Audit Trail:** Complete audit logging requirements?
