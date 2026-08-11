# REWRITE BASELINE - Entity Application

## Repository Inventory

**Location:** `/workspace/entity-legacy`

**Technology Stack:**
- PHP 8.2+
- Laravel 12.x
- MongoDB (via mongodb/laravel-mongodb ^5.0)
- MySQL/MariaDB (hybrid persistence)
- Inertia.js + Vue.js (frontend)
- Pest/PHPUnit (testing)

**File Counts:**
- PHP Files: 287
- JavaScript Files: 32
- Vue Components: 158

---

## Route Inventory

### Web Routes (`routes/web.php`)

#### Authentication
- GET/POST `/login` - Login form
- GET/POST `/register` - Registration
- POST `/logout` - Logout

#### Media Streaming
- GET `/stream/videos/{path}` - Video streaming with Range requests
- GET `/stream/audio/{path}` - Audio streaming with Range requests

#### Studio (Unified Editor)
- GET `/studio/resume` - Resume last editing session
- GET `/studio/{type}/{slug}/{childId?}` - Main editor view
- POST `/studio/{type}/{slug}/{childId?}/save` - Save content
- POST `/studio/{type}/{slug}/nodes` - Create new content node

#### Resource Routes (Web)
- `books` - Book management
- `audios` - Audio management
- `videos` - Video management
- `manuscripts` - Manuscript management
- `categories` - Category taxonomy
- `tags` - Tag taxonomy
- `authors` - Author management
- `publishers` - Publisher management
- `bookers` - Booker management
- `topics` - Topic management
- `languages` - Language management
- `shelves` - Shelf management
- `collections` - Collection management
- `series` - Series management
- `activities` - Activity logs (index, show)
- `comments` - Comments
- `notes` - Notes
- `deletions` - Deletion records (index, show)

#### Reader & Editor
- GET `/books/{book}/reader/{child?}` - Book reader
- GET `/books/{book}/editor/{child}` - Book editor
- GET `/audios/{audio}/editor/{child}` - Audio editor
- GET `/videos/{video}/editor/{child}` - Video editor
- GET `/manuscripts/{manuscript}/editor/{child}` - Manuscript editor
- GET `/book-contents/{child}` - Show book content

#### Search & Dashboard
- GET `/dashboard` - Main dashboard
- GET `/search` - Global search
- GET `/system/commands` - System command dashboard

#### API Compatibility Endpoints
- POST `api/book-children/{id}/save` - Save book child content
- POST `api/book-children/{id}/restore/{version?}` - Restore version
- POST `api/segments/{id}` - Store segment
- PUT `api/segments/{id}` - Update segment
- DELETE `api/segments/{id}` - Delete segment
- POST `api/system/run-command` - Run system command
- POST `api/system/list-files` - List files

### API Routes (`routes/api.php`)

All under `auth:sanctum` middleware:
- `books` - API resource
- `audios` - API resource
- `videos` - API resource
- `manuscripts` - API resource
- `categories` - API resource
- `tags` - API resource
- `collections` - API resource
- `series` - API resource
- `activities` - API resource (index, show)
- `comments` - API resource
- `notes` - API resource
- `deletions` - API resource (index, show)
- POST `books/{book}/contents/reorder` - Reorder book contents
- GET `books/{book}/export/{format}` - Export book
- GET `book-children/{child}/export/{format}` - Export child

---

## Model Inventory

### Core Entity Models (MySQL)

| Model | Table | Description |
|-------|-------|-------------|
| `Entity` (abstract) | N/A | Base class with common traits |
| `Book` | `books` | Books with author, ISBN, cover |
| `Audio` | `audios` | Audio files with duration, format |
| `Video` | `videos` | Video files with duration, format |
| `Manuscript` | `manuscripts` | Historical manuscripts |
| `Version` | `versions` | Polymorphic publication versions |
| `ReadingPosition` | `reading_positions` | User reading progress |
| `Deletion` | `deletions` | Soft deletion records |

### Content Node Models (MongoDB)

| Model | Collection | Description |
|-------|-----------|-------------|
| `BookChild` | `book_children` | Book chapters/sections |
| `ManuscriptPage` | `manuscript_pages` | Manuscript pages/folios |
| `AudioSegment` | `audio_segments` | Audio time segments |
| `VideoSegment` | `video_segments` | Video time segments |
| `EntityContent` | `entity_contents` | Generic unified content |

### Taxonomy Models

| Model | Table | Description |
|-------|-------|-------------|
| `Tag` | `tags` | Tags with polymorphic relations |
| `Category` | `categories` | Categories with hierarchy |
| `Topic` | `topics` | Subject topics |
| `Collection` | `collections` | Entity collections |
| `Series` | `series` | Ordered series |

### Actor Models

| Model | Table | Description |
|-------|-------|-------------|
| `Author` | `authors` | Authors (polymorphic) |
| `Booker` | `bookers` | Contributors/editors |
| `Publisher` | `publishers` | Publishers |
| `User` | `users` | System users |

### Supporting Models

| Model | Table | Description |
|-------|-------|-------------|
| `Language` | `languages` | Languages |
| `Shelf` | `shelves` | Virtual shelves |
| `Comment` | `comments` | Comments |
| `Note` | `notes` | User notes |
| `Activity` | `activities` | Activity log |

---

## Enum Inventory

### `EntityType` (`app/Enums/EntityType.php`)
```php
case BOOK = 'book'
case AUDIO = 'audio'
case VIDEO = 'video'
case MANUSCRIPT = 'manuscript'
```

Methods:
- `modelClass()` - Returns model class for type
- `defaultFormat()` - PDF for books/manuscripts, MP3/MP4 for media
- `supportsDuration()` - true for audio/video
- `supportsPages()` - true for books/manuscripts
- `label()` - Arabic label

### `ContentNodeType` (`app/Enums/ContentNodeType.php`)

**Book Types:**
- `SUB_BOOK` (كتاب فرعي)
- `PART` (جزء)
- `BAB` (باب)
- `CHAPTER` (فصل)
- `MASALAH` (مسألة)
- `PAGE` (صفحة)
- `SECTION` (قسم)

**Manuscript Types:**
- All book types plus `FOLIO` (ورقة)

**Audio Types:**
- `SEGMENT` (مقطع)
- `TRACK` (مسار)
- `MARKER` (علامة)

**Video Types:**
- `SCENE` (مشهد)
- `SHOT` (لقطة)
- `SEGMENT` (مقطع)

Methods:
- `allowedFor(EntityType)` - Valid types per entity
- `getVisualMap(EntityType)` - HTML tag mapping
- `defaultFor(EntityType)` - Primary type
- `isValidFor(EntityType)` - Validation
- `label()` - Arabic label

---

## Migration Inventory

**Location:** `database/migrations/`

### Core Migrations
1. `0001_01_01_000000_create_users_table.php`
2. `0001_01_01_000001_create_cache_table.php`
3. `0001_01_01_000002_create_jobs_table.php`

### Entity Tables
4. `2025_12_22_215242_create_activities_table.php`
5. `2025_12_22_221006_create_tags_table.php`
6. `2025_12_22_222951_create_books_table.php`
7. `2025_12_22_225756_create_videos_table.php`
8. `2025_12_22_232028_create_audio_table.php`
9. `2025_12_22_232104_create_manuscripts_table.php`
10. `2025_12_24_154958_create_categories_table.php`
11. `2025_12_24_155328_create_comments_table.php`
12. `2025_12_24_155410_create_deletions_table.php`
13. `2025_12_24_155447_create_collections_table.php`
14. `2025_12_24_155541_create_series_table.php`

### Actor Tables
15. `2025_12_28_152000_create_authors_table.php`
16. `2025_12_28_152000_create_bookers_table.php`
17. `2025_12_28_152000_create_publishers_table.php`
18. `2025_12_28_152001_create_languages_table.php`
19. `2025_12_28_152001_create_shelves_table.php`
20. `2025_12_28_152001_create_topics_table.php`

### Versioning
21. `2025_12_28_152001_create_versions_table.php`
22. `2026_01_06_214110_add_title_to_versions_table.php`

### Collections Migration
23. `2026_01_07_000000_migrate_to_separated_collections.php`

### Reading Position
24. `2026_01_25_171127_create_reading_positions_table.php`

### Sanctum
25. `2025_12_24_225855_create_personal_access_tokens_table.php`

---

## MongoDB Collection Inventory

**Database:** `entity_content` (configurable via `DB_DATABASE_MONGO`)

### Collections
| Collection | Model | Description |
|------------|-------|-------------|
| `book_children` | `BookChild` | Hierarchical book content |
| `manuscript_pages` | `ManuscriptPage` | Manuscript page images/transcriptions |
| `audio_segments` | `AudioSegment` | Time-based audio segments |
| `video_segments` | `VideoSegment` | Time-based video segments |
| `entity_contents` | `EntityContent` | Generic unified content storage |

### Document Structure (BookChild example)
```json
{
  "_id": "uuid-string",
  "book_id": "uuid-string",
  "parent_id": "uuid-string|null",
  "slug": "unique-slug",
  "type": "chapter|part|bab|etc",
  "title": "string",
  "order": 0,
  "content_blocks": [],
  "metadata": {},
  "last_updated": "ISO8601",
  "is_manually_edited": false,
  "versions": []
}
```

---

## Storage Format Inventory

### File Storage
**Location:** `storage/app/`

**Structure:**
```
storage/
├── app/
│   ├── books/
│   ├── audio/
│   ├── video/
│   ├── manuscripts/
│   └── covers/
├── framework/
└── logs/
```

### Public Access
Files served via `MediaStreamController` with HTTP Range Request support for seeking.

### Database Configuration
**Dual Persistence:**
- **MySQL:** Entity metadata, taxonomy, users, versions
- **MongoDB:** Content nodes, hierarchical structures, time-based segments

**Connection Config:**
```php
'mysql' => [...] // Default for entities
'mongodb' => [
    'driver' => 'mongodb',
    'host' => env('DB_HOST_MONGO'),
    'port' => env('DB_PORT_MONGO', 27017),
    'database' => env('DB_DATABASE_MONGO', 'entity_content'),
    'options' => [
        'w' => 'majority',
        'readPreference' => 'primary',
    ],
]
```

---

## Frontend Module Inventory

**Location:** `resources/js/`

### Pages (`resources/js/Pages/`)
| Directory | Description |
|-----------|-------------|
| `Activities/` | Activity logs |
| `Audios/` | Audio management |
| `Auth/` | Login/Register |
| `Authors/` | Author management |
| `Bookers/` | Booker management |
| `Books/` | Book CRUD + Reader |
| `Categories/` | Category management |
| `Collections/` | Collections |
| `Comments/` | Comments |
| `Deletions/` | Deletion records |
| `Languages/` | Languages |
| `Manuscripts/` | Manuscript management |
| `Notes/` | Notes |
| `Publishers/` | Publishers |
| `Reader/` | Reader component |
| `Search/` | Search interface |
| `Series/` | Series management |
| `Shelves/` | Shelves |
| `System/` | System commands |
| `Tags/` | Tag management |
| `Topics/` | Topics |
| `Videos/` | Video management |

### Technologies (`resources/js/Technologies/`)
| Directory | Description |
|-----------|-------------|
| `Editor/` | Unified editor components |
| `Reader/` | Reader client components |
| `Studio/` | Studio layout and tools |

### Layouts
- `StudioLayout.vue` - Editor/Studio layout
- `AppLayout.vue` - Main application layout

### Components
- Various reusable UI components

---

## Test Inventory

### Feature Tests (`tests/Feature/`)

| Test File | Coverage |
|-----------|----------|
| `BookChildTest.php` | Book child CRUD |
| `BookControllerTest.php` | Book controller actions |
| `BookEditorControllerTest.php` | Editor functionality |
| `BookExportTest.php` | Export functionality |
| `BookWorkflowTest.php` | Book workflow |
| `BulkDeletionTest.php` | Bulk delete operations |
| `CategoryAndTagControllerTest.php` | Taxonomy controllers |
| `ConsoleCommandsTest.php` | Console commands |
| `ControllerAccessTest.php` | Controller access control |
| `EntityControllerTest.php` | Entity CRUD |
| `EntitySlugRoutingTest.php` | Slug-based routing |
| `EntityVersioningTest.php` | Version management |
| `EntityWorkflowTest.php` | Entity workflows |
| `GlobalSearchTest.php` | Search functionality |
| `InertiaResponseTest.php` | Inertia responses |
| `ManuscriptCreationIntegrationTest.php` | Manuscript creation |
| `MarkdownStructureParserTest.php` | Markdown parsing |
| `MediaControllersTest.php` | Media streaming |
| `MongoDBIntegrationTest.php` | MongoDB integration |
| `PageAccessibilityTest.php` | Page access |
| `PolymorphicRelationsIntegrationTest.php` | Polymorphic relations |
| `SecurityValidationTest.php` | Security validations |
| `StandardControllersTest.php` | Standard CRUD |
| `SyncProtectionTest.php` | Sync protection |
| `UnifiedContentTest.php` | Unified content |

### Unit Tests (`tests/Unit/`)
- `ArabicSlugTest.php` - Arabic slug generation
- Factories, Models, Observers, Repositories, Services, Traits subdirectories

### Browser Tests
- Laravel Dusk configured

---

## Command Inventory

**Location:** `app/Console/Commands/`

| Command | Description |
|---------|-------------|
| `AnalyzeArchitecture.php` | Architecture analysis |
| `SyncManuscriptPages.php` | Sync manuscript pages |
| `SyncStorage.php` | Sync storage |
| `SyncManuscriptsData.php` | Sync manuscripts data |
| `SeedRealisticData.php` | Seed realistic test data |
| `RegenerateContentSlugs.php` | Regenerate slugs |
| `ImportTranscripts.php` | Import transcripts |

---

## Dependency Inventory

### Production Dependencies (`composer.json`)
```json
{
  "php": "^8.2",
  "barryvdh/laravel-dompdf": "^3.1",
  "inertiajs/inertia-laravel": "^2.0",
  "laravel/framework": "^12.0",
  "laravel/sanctum": "^4.0",
  "mongodb/laravel-mongodb": "^5.0",
  "phpoffice/phpword": "^1.4",
  "spatie/simple-excel": "^3.8",
  "tightenco/ziggy": "^2.6"
}
```

### Dev Dependencies
```json
{
  "fakerphp/faker": "^1.23",
  "laravel/dusk": "^8.3",
  "laravel/pint": "^1.24",
  "pestphp/pest": "^3.8",
  "pestphp/pest-plugin-laravel": "^3.2",
  "phpstan/phpstan": "^2.1",
  "phpunit/phpunit": "^11.5"
}
```

---

## Service Layer Inventory

**Location:** `app/Services/`

| Service | Responsibility |
|---------|----------------|
| `EntityContentService` | Content node CRUD, aggregation, rendering |
| `EntityManagerService` | Entity lifecycle management |
| `EntityQueryService` | Entity querying |
| `EntityRelationService` | Entity relationships |
| `BookContentService` | Book-specific content operations |
| `MediaManagerService` | Media file management |
| `ReadingPositionService` | User reading position tracking |
| `Book/MarkdownStructureParser` | Markdown structure parsing |

---

## Controller Inventory

**Location:** `app/Http/Controllers/`

### Main Controllers
- `EntityController` - Master entity controller (19KB)
- `UnifiedEditorController` - Unified studio editor
- `ReaderController` - Reading interface
- `ContentNodeController` - Content node operations
- `BookContentController` - Book content operations
- `MediaStreamController` - Media streaming with Range requests
- `DashboardController` - Dashboard
- `GlobalSearchController` - Global search
- `EditorTestController` - Editor testing
- `SystemController` - System commands

### Resource Controllers
- `BookController`, `AudioController`, `VideoController`, `ManuscriptController`
- `AuthorController`, `BookerController`, `PublisherController`
- `CategoryController`, `TagController`, `TopicController`
- `CollectionController`, `SeriesController`, `ShelfController`
- `LanguageController`, `CommentController`, `NoteController`
- `ActivityController`, `DeletionController`

### API Controllers (`app/Http/Controllers/Api/`)
- `BookController`, `AudioController`, `VideoController`, `ManuscriptController`
- `BookContentOrderController` - Book content reordering
- `BookExportController` - Book export (PDF, DOCX, etc.)
- `SegmentController` - Audio/Video segment operations
- `CategoryController`, `TagController`, `CollectionController`, `SeriesController`
- `CommentController`, `NoteController`, `ActivityController`, `DeletionController`

---

## Trait Inventory

**Location:** `app/Traits/`

| Trait | Purpose |
|-------|---------|
| `HasPolymorphicRelations` | Polymorphic relations for entities |
| `HasCommonScopes` | Common query scopes |
| `TaxonomyRelationships` | Taxonomy relation helpers |

---

## Observer Inventory

**Location:** `app/Observers/`

| Observer | Model |
|----------|-------|
| `EntityLifecycleObserver` | Entity lifecycle events |
| `EntityAuditObserver` | Audit logging |
| `EntityContentObserver` | Content change tracking |
| `EntityCacheObserver` | Cache invalidation |

---

## Key Architectural Patterns

### VERIFIED Patterns

1. **Hybrid Persistence**
   - MySQL for entity metadata, taxonomy, users
   - MongoDB for hierarchical content nodes
   - Connected via `HybridRelations` trait (now manual loading)

2. **Polymorphic Relations**
   - `HasPolymorphicRelations` trait on Entity base
   - Supports: tags, categories, authors, bookers, versions, collections, series
   - Custom pivot tables: `taggables`, `categorizables`, `authorables`, etc.

3. **Content Node Hierarchy**
   - Parent-child relationships via `parent_id`
   - Ordered via `order` field
   - Type-safe via `ContentNodeType` enum

4. **Slug-Based Routing**
   - All entities use `slug` as route key
   - Auto-generated via `SlugHelper`
   - Supports Arabic slugs

5. **Soft Deletes**
   - All models use `SoftDeletes` trait
   - `Deletion` model tracks deletion records

6. **Versioning**
   - `Version` model for publication versions
   - Polymorphic `versionable` relation
   - In-document versioning in MongoDB (content_blocks snapshots)

7. **Caching**
   - Entity caching via `getCached()`, `getCachedWithRelations()`, `getCachedStats()`
   - Cache keys: `entity.{class}.{id}`, `entity.{class}.{id}.with_relations`, `entity.{class}.{id}.stats`

8. **Media Streaming**
   - HTTP Range Request support
   - Separate routes for video/audio
   - Path traversal protection needed

---

## Domain Contract Summary

### Entity Types (VERIFIED)
1. **Book** - Books with structured content (chapters, parts, etc.)
2. **Audio** - Audio files with time-based segments
3. **Video** - Video files with time-based segments
4. **Manuscript** - Historical manuscripts with pages/folios

### Content Node Types (VERIFIED)
Hierarchical structure with parent-child relationships
- Books: sub-book → part → bab → chapter → masalah → section → page
- Manuscripts: same as books + folio
- Audio: segment, track, marker
- Video: scene, shot, segment

### Relationships (VERIFIED)
- Entity → hasMany → ContentNodes (MongoDB)
- Entity → morphMany → Versions
- Entity → morphToMany → Authors, Bookers, Tags, Categories, Collections, Series
- Entity → morphMany → Activities, Comments, Notes, Deletions

### Storage Semantics (VERIFIED)
- Entities stored in MySQL with UUIDs
- Content nodes stored in MongoDB with string `_id`
- Files stored in `storage/app/` with public access via controller
- Reading positions tracked per user per entity

---

## Compatibility Notes

### Critical Behaviors to Preserve

1. **Slug-based routing** - All entities resolved by slug, not ID
2. **Hybrid SQL/Mongo** - Content separation must be maintained or explicitly migrated
3. **ContentNodeType validation** - Type safety enforced per entity type
4. **Hierarchical content** - Parent-child relationships with ordering
5. **Polymorphic taxonomy** - Tags, categories shared across all entity types
6. **Version snapshots** - Both SQL versions and MongoDB in-document versions
7. **Reading position** - Per-user, per-entity progress tracking
8. **Media streaming** - Range request support for seeking

### Known Limitations

1. **No repository pattern** - Direct Eloquent/Mongo access in controllers
2. **Business logic in controllers** - Some logic in `UnifiedEditorController`, `ReaderController`
3. **Tight coupling** - Services depend on concrete models
4. **No explicit use cases** - Operations scattered across controllers
5. **MongoDB connection hardcoded** - In model classes directly

---

## Unresolved Questions

1. What is the exact MongoDB schema for each collection?
2. What are all the import/export formats supported?
3. What are the exact security requirements for media access?
4. How are transcripts synchronized with audio/video?
5. What is the complete search index structure?
6. Are there any background jobs or queues configured?
7. What external APIs or services are integrated?
8. What is the backup/disaster recovery strategy?
