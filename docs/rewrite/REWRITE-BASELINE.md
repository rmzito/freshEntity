# REWRITE BASELINE — Phase 0

**Status:** VERIFIED  
**Date:** Generated from legacy code analysis

---

## Repository Structure

```
entity-legacy/
├── app/
│   ├── Console/Commands/     (7 commands)
│   ├── Enums/                (2 enums)
│   ├── Helpers/              (SlugHelper)
│   ├── Http/
│   │   ├── Controllers/      (45 controllers)
│   │   ├── Middleware/       (HandleInertiaRequests)
│   │   └── Requests/         (16 form requests)
│   ├── Models/               (28 models)
│   ├── Observers/            (4 observers)
│   ├── Policies/             (EntityPolicy)
│   ├── Providers/            (AppServiceProvider)
│   ├── Repositories/         (EntityBaseRepository)
│   ├── Services/             (8 services)
│   └── Traits/               (polymorphic relations)
├── config/                   (11 config files)
├── database/
│   ├── factories/
│   ├── migrations/           (25 migrations)
│   └── seeders/
├── resources/
│   ├── assets/               (Vue.js frontend)
│   └── views/
├── routes/                   (web.php, api.php, console.php)
├── storage/
├── tests/
│   ├── Browser/              (Dusk tests)
│   ├── Feature/
│   └── Unit/
└── composer.json, package.json
```

---

## Model Inventory

### Core Entity Models
| Model | Verified | Purpose |
|-------|----------|---------|
| Entity | ✓ | Base entity with polymorphic relations |
| Book | ✓ | Book entity type |
| BookChild | ✓ | Book content chapters/sections |
| Manuscript | ✓ | Manuscript entity type |
| ManuscriptChild | ✓ | Manuscript sections |
| ManuscriptPage | ✓ | Manuscript page images |
| Audio | ✓ | Audio entity type |
| AudioSegment | ✓ | Audio segments/timeline |
| Video | ✓ | Video entity type |
| VideoSegment | ✓ | Video segments/timeline |

### Content Models
| Model | Verified | Purpose |
|-------|----------|---------|
| EntityContent | ✓ | Polymorphic content storage |
| Version | ✓ | Entity versioning |
| ReadingPosition | ✓ | User reading progress |

### Taxonomy Models
| Model | Verified | Purpose |
|-------|----------|---------|
| Author | ✓ | Entity authors |
| Publisher | ✓ | Entity publishers |
| Booker | ✓ | Book curators |
| Language | ✓ | Content languages |
| Category | ✓ | Entity categories |
| Tag | ✓ | Entity tags |
| Topic | ✓ | Entity topics |
| Series | ✓ | Entity series |
| Collection | ✓ | Entity collections |
| Shelf | ✓ | User shelves |

### Interaction Models
| Model | Verified | Purpose |
|-------|----------|---------|
| Comment | ✓ | Entity comments |
| Note | ✓ | User notes |
| Activity | ✓ | Activity tracking |
| Deletion | ✓ | Soft deletion records |
| User | ✓ | Authentication |

---

## Enum Types

### EntityType
**Location:** `app/Enums/EntityType.php`
**VERIFIED values:**
- BOOK
- MANUSCRIPT
- AUDIO
- VIDEO

### ContentNodeType
**Location:** `app/Enums/ContentNodeType.php`
**VERIFIED values:**
- To be documented from source

---

## Controller Inventory

### Web Controllers (29)
1. ActivityController
2. AudioController
3. AuthorController
4. BookContentController
5. BookController
6. BookerController
7. CategoryController
8. CollectionController
9. CommentController
10. ContentNodeController
11. DashboardController
12. DeletionController
13. EditorTestController
14. EntityController
15. GlobalSearchController
16. LanguageController
17. ManuscriptController
18. MediaStreamController
19. NoteController
20. PublisherController
21. ReaderController
22. SeriesController
23. ShelfController
24. SystemController
25. TagController
26. TopicController
27. UnifiedEditorController
28. VideoController
29. Auth: LoginController, RegisterController

### API Controllers (16)
Under `app/Http/Controllers/Api/`:
1. ActivityController
2. AudioController
3. BookContentOrderController
4. BookController
5. BookExportController
6. CategoryController
7. CollectionController
8. CommentController
9. DeletionController
10. ManuscriptController
11. NoteController
12. SegmentController
13. SeriesController
14. TagController
15. VideoController

---

## Service Layer

| Service | Verified | Responsibility |
|---------|----------|----------------|
| EntityManagerService | ✓ | Entity CRUD operations |
| EntityQueryService | ✓ | Entity querying/filtering |
| EntityContentService | ✓ | Content node management |
| EntityRelationService | ✓ | Entity relationships |
| BookContentService | ✓ | Book content operations |
| MediaManagerService | ✓ | Media file handling |
| ReadingPositionService | ✓ | Reading progress tracking |
| MarkdownStructureParser | ✓ | Markdown parsing for books |

---

## Migration Inventory

| Migration | Date | Purpose |
|-----------|------|---------|
| create_users_table | 0001_01_01 | Users authentication |
| create_cache_table | 0001_01_01 | Cache system |
| create_jobs_table | 0001_01_01 | Queue jobs |
| create_activities_table | 2025_12_22 | Activity logging |
| create_tags_table | 2025_12_22 | Tags |
| create_books_table | 2025_12_22 | Books |
| create_videos_table | 2025_12_22 | Videos |
| create_audio_table | 2025_12_22 | Audio |
| create_manuscripts_table | 2025_12_22 | Manuscripts |
| create_categories_table | 2025_12_23 | Categories |
| create_comments_table | 2025_12_24 | Comments |
| create_notes_table | 2025_12_24 | Notes |
| create_deletions_table | 2025_12_24 | Deletion tracking |
| create_collections_table | 2025_12_24 | Collections |
| create_series_table | 2025_12_24 | Series |
| create_personal_access_tokens_table | 2025_12_24 | Sanctum tokens |
| create_authors_table | 2025_12_28 | Authors |
| create_bookers_table | 2025_12_28 | Bookers |
| create_publishers_table | 2025_12_28 | Publishers |
| create_languages_table | 2025_12_28 | Languages |
| create_shelves_table | 2025_12_28 | Shelves |
| create_topics_table | 2025_12_28 | Topics |
| create_versions_table | 2025_12_28 | Versioning |
| add_title_to_versions_table | 2026_01_06 | Version title |
| migrate_to_separated_collections | 2026_01_07 | Collection migration |
| create_reading_positions_table | 2026_01_25 | Reading positions |

---

## Console Commands

| Command | Purpose |
|---------|---------|
| AnalyzeArchitecture | Architecture analysis |
| ImportTranscripts | Transcript import |
| RegenerateContentSlugs | Slug regeneration |
| SeedRealisticData | Data seeding |
| SyncManuscriptPages | Manuscript page sync |
| SyncManuscriptsData | Manuscript data sync |
| SyncStorage | Storage synchronization |

---

## Test Inventory

### Feature Tests (25+)
- BookChildTest, BookControllerTest, BookEditorControllerTest
- BookExportTest, BookWorkflowTest
- BulkDeletionTest, CategoryAndTagControllerTest
- EntityControllerTest, EntitySlugRoutingTest, EntityVersioningTest, EntityWorkflowTest
- GlobalSearchTest, InertiaResponseTest
- ManuscriptCreationIntegrationTest
- MediaControllersTest, MongoDBIntegrationTest
- SecurityValidationTest, StandardControllersTest
- UnifiedContentTest
- Studio: SmartSplitterTest
- Editor: EditorRoutingTest, PolymorphicSaveTest
- Console: StorageSyncTest, ConsoleCommandsTest
- PolymorphicRelationsIntegrationTest

### Unit Tests (20+)
- Models: Activity, Audio, Book, BookRelations, Category, Manuscript, Tag
- Services: EntityManagerService, EntityQueryService, EntityRelationService, MediaManagerService
- Repositories: EntityBaseRepository
- Observers: EntityAuditObserver, EntityCacheObserver, EntityLifecycleObserver
- Traits: CompletePolymorphicSystem, HasCommonScopes, HasPolymorphicRelations
- Factories: FactoryGenerationTest
- ArabicSlugTest

### Browser Tests - Dusk (22+)
- ActivityTest, AuthorCRUDTest, BooksPageTest, DashboardTest
- GlobalSearchTest, ManuscriptCRUDTest, NavigationTest
- ReaderNavigationTest
- Studio: StudioConstitutionalStandardTest, StudioContentProcessTest, StudioContentStructureTest
- StudioDuplicationTest, StudioHierarchyTest, StudioInteractionTest
- StudioNavigationTest, StudioPlayerEditTest, StudioPlayerTest
- StudioReloadTest, StudioSegmentEditTest, StudioSmartSplitterTest

---

## Dependency Inventory

### Backend (composer.json)
```json
{
  "php": "^8.2",
  "laravel/framework": "^12.0",
  "mongodb/laravel-mongodb": "^5.0",
  "inertiajs/inertia-laravel": "^2.0",
  "barryvdh/laravel-dompdf": "^3.1",
  "phpoffice/phpword": "^1.4",
  "spatie/simple-excel": "^3.8",
  "tightenco/ziggy": "^2.6",
  "laravel/sanctum": "^4.0"
}
```

### Frontend (package.json)
```json
{
  "vue": "^3.5.26",
  "@inertiajs/vue3": "^2.3.4",
  "@tiptap/*": "^3.15.3",
  "pinia": "^3.0.4",
  "tailwindcss": "^4.0.0",
  "vite": "^7.0.7",
  "vitest": "^4.0.16"
}
```

---

## Storage Semantics

**Database:** Hybrid SQL + MongoDB
- SQL: Laravel migrations for structured data
- MongoDB: mongodb/laravel-mongodb ^5.0 for flexible content storage

**File Storage:**
- Location: `/workspace/entity-legacy/storage/`
- Media files, manuscripts, imports

---

## Frontend Module Inventory

**Framework:** Vue 3 + Inertia.js + Pinia

**Editor:** Tiptap (rich text)
- Extensions: heading, link, image, table, highlight, character-count, etc.

**Build Tool:** Vite 7.0

**Key Dependencies:**
- @heroicons/vue
- lucide-vue-next
- vuedraggable
- mammoth (DOCX parsing)
- tippy.js

---

## Route Patterns

**Web Routes:** `/routes/web.php`
**API Routes:** `/routes/api.php`
**Console Routes:** `/routes/console.php`

---

## Compatibility Notes

### VERIFIED
- Entity types: Book, Manuscript, Audio, Video
- Polymorphic relations system
- Versioning via Version model
- Content nodes via EntityContent
- Reading position tracking
- Soft deletion via Deletion model
- Activity tracking

### INFERRED
- MongoDB used for content storage (based on package)
- Tiptap editor for rich text editing
- Inertia.js for SPA-like experience

### UNKNOWN
- Exact MongoDB collection structures
- API endpoint specifications
- Frontend component hierarchy
- Import/export formats details

---

## Next Steps

1. Complete DOMAIN_CONTRACT.md
2. Complete COMPATIBILITY_MATRIX.md
3. Analyze key model relationships
4. Document API endpoints
5. Map frontend components

---

## STOP POINT

**Phase 0 Status:** IN PROGRESS  
**Next Action:** Review and complete remaining baseline documentation
