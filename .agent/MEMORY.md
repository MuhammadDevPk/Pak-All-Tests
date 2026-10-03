# MEMORY.md — Rolling Project Memory & Operational Knowledge

> **Status**: ACTIVE ROLLING RECORD
> **Last Updated**: 2026-10-03
> **Repository**: Pak-All-Tests (Laravel 13 • Vue 3 / Inertia 3 • PostgreSQL 16+ Supabase)

---

## 📜 Active Rolling Memory Log (Top 10 Baseline Entries)

This log records the 10 most critical architectural decisions, infrastructure fixes, and operational milestones shaping the active codebase.

### Entry 18 — 2026-10-04: SenseLab Luxury UI Transformation Across Candidate Dashboards & Workspaces
- **Action**: Restructured and elevated the entire candidate experience across 7 frontend surfaces to match the flagship SenseLab aesthetic established on `Welcome.vue`: (1) transformed `Domain/Dashboard.vue` with ambient radial lighting meshes, an official syllabus track pill badge, and human-readable metadata replacing exposed database `ltree` path numbers (`Path: 325.326.327`); (2) upgraded `PhaseStepperLayout.vue` into a sleek pipeline navigator with glowing active-phase rings, pulsing status dots, and concentric nested radii; (3) elevated `SubjectGrid.vue` into SenseLab feature cards with subject-specific icons/glyphs (Islamic Studies 🕌, Pak History 📜, Geography 🌍, GK & Science 🔬, Current Affairs 🛡️), high-yield syllabus chips, question counters, and hover-lift gradient action buttons (`Start Practice ➔`); (4) redesigned `WeakQuestions/Index.vue` with ambient lighting, SenseLab metric cards with colored icon boxes, and tactile drilldown question tiles; (5) elevated `McqPlayer.vue` with glassmorphic headers, refined dual-mode controls, tactile option buttons, and a luxury Session Performance report with glowing benchmark indicators and direct revision bank routing; (6) transformed root `Dashboard.vue` from empty `<PlaceholderPattern />` boxes into a complete Candidate Command Center with active exam tracks and interactive curriculum topology; and (7) upgraded `AppLogo.vue` and `.env` branding to display the official Pak-All-Tests ribbon mark.
- **Impact**: Unifies the candidate portal with the landing page design language, providing candidates with an international-grade, intuitive, and cohesive visual experience while eliminating raw database internals and placeholder states.
- **Files Changed**: `resources/js/components/AppLogo.vue`, `resources/js/components/sdui/McqPlayer.vue`, `resources/js/components/sdui/PhaseStepperLayout.vue`, `resources/js/components/sdui/SubjectGrid.vue`, `resources/js/pages/Dashboard.vue`, `resources/js/pages/Domain/Dashboard.vue`, `resources/js/pages/WeakQuestions/Index.vue`, `.env`.
- **Blast Radius**: Frontend candidate dashboard, test player, weak questions workspace, and navigation layout.
- **Rollback Path**: `git checkout HEAD~1 -- resources/js/components/AppLogo.vue resources/js/components/sdui/McqPlayer.vue resources/js/components/sdui/PhaseStepperLayout.vue resources/js/components/sdui/SubjectGrid.vue resources/js/pages/Dashboard.vue resources/js/pages/Domain/Dashboard.vue resources/js/pages/WeakQuestions/Index.vue .env`.
- **Lessons**: UI consistency requires propagating design tokens across all post-auth candidate surfaces, not just public landing pages; never expose internal raw database identifiers (`ltree` paths) to end-users when official syllabus badges and readable metadata can be rendered instead.

### Entry 17 — 2026-10-04: Weak MCQs Persistence, Reveal Answer Forfeiture & 2-Strike Threshold Mastery Engine
- **Action**: Engineered end-to-end Weak MCQs system featuring: (1) on-demand "Reveal Answer" button in `McqPlayer.vue` positioned beside the question title, which reveals correct solution and explanation while forfeiting score (0 points) and logging the question into candidate's Weak MCQs; (2) automatic weak question capture on incorrect answers in both interactive practice and full test submission (`McqExecutionController`); (3) 2-strike consecutive correct Threshold Mastery graduation (`UserWeakQuestion::recordAttempt`) requiring two consecutive un-revealed correct answers to mark mastered; (4) dual-mode player controls in `McqPlayer.vue` (Page View with 10/25/50/100 questions per page and smooth pager vs Focus Mode with single question view and compact drawer replacing the 228-button wall); (5) dedicated candidate portal page `resources/js/pages/WeakQuestions/Index.vue` with subject filtering, mastery rate metrics, and direct in-place practice test launcher; and (6) added "Revision & Weak MCQs" item to candidate sidebar navigation.
- **Impact**: Provides candidates with targeted spaced repetition for all forfeited or failed questions, prevents answer memorization loopholes through zero-knowledge scrubbing and 2-strike graduation, and eliminates cognitive overload from huge 200+ question button walls via responsive page-level scrolling.
- **Files Changed**: `app/Http/Controllers/McqExecutionController.php`, `app/Http/Controllers/WeakQuestionsController.php`, `app/Models/User.php`, `app/Models/UserWeakQuestion.php`, `database/migrations/2026_10_04_000001_create_user_weak_questions_table.php`, `resources/js/components/AppSidebar.vue`, `resources/js/components/sdui/McqPlayer.vue`, `resources/js/pages/WeakQuestions/Index.vue`, `routes/web.php`, `tests/Feature/WeakQuestionsTest.php`, `.agent/ARCHITECTURE.md`, `.agent/MEMORY.md`.
- **Blast Radius**: `McqPlayer.vue`, `McqExecutionController.php`, `AppSidebar.vue`, `routes/web.php`, and database table `user_weak_questions`.
- **Rollback Path**: `php artisan migrate:rollback --step=1` and `git checkout HEAD~1 -- app/Http/Controllers/McqExecutionController.php resources/js/components/AppSidebar.vue resources/js/components/sdui/McqPlayer.vue routes/web.php app/Models/User.php`.
- **Lessons**: Answer-key security requires authoritative server-side reveal (`/api/questions/{id}/reveal`) rather than bundling correct indices in the client payload; spaced learning mastery must require multiple consecutive confirmations (2-strike rule) to prevent accidental lucky guesses from premature graduation.

### Entry 16 — 2026-10-03: Tailwind v4 Canonical Classes Token Mapping & AppLogoIcon Component Cleanliness
- **Action**: Mapped global CSS variable tokens to Tailwind v4 theme colors in `@theme inline` (`--color-brand-*`), added mesh-aura utility classes (`.mesh-aura-cyan`, `.mesh-aura-warm`), updated `Welcome.vue`, `AuthSimpleLayout.vue`, and `AuthCardLayout.vue` to use canonical Tailwind classes (`text-brand-heading`, `text-brand-muted`, `bg-brand-canvas`, `bg-brand-dark`), and wired `AppLogoIcon` across all layouts.
- **Impact**: Completely eliminates all VS Code Tailwind CSS language server warnings (`suggestCanonicalClasses`), removes TypeScript/ESLint orphan import warnings (`ts-plugin(6133)` for unused `AppLogoIcon`), and guarantees single-source-of-truth logo rendering while preserving dynamic CSS custom property theming.
- **Files Changed**: `resources/css/app.css`, `resources/js/components/AppLogoIcon.vue`, `resources/js/layouts/auth/AuthSimpleLayout.vue`, `resources/js/layouts/auth/AuthCardLayout.vue`, `resources/js/pages/Welcome.vue`, `.agent/MEMORY.md`.
- **Blast Radius**: Frontend layouts, landing page, and global design token mapping.
- **Rollback Path**: `git checkout HEAD~1 -- resources/css/app.css resources/js/components/AppLogoIcon.vue resources/js/layouts/auth/ resources/js/pages/Welcome.vue`.
- **Lessons**: When using CSS custom properties with Tailwind v4, expose them via `@theme inline` as first-class colors (`--color-brand-*: var(--brand-*);`) so the language server suggests and recognizes canonical utility classes rather than arbitrary bracket syntax `text-[var(...)]`.

### Entry 15 — 2026-10-03: SenseLab UI/UX Transformation & Global CSS Variable Tokens
- **Action**: Completely restructured and elevated the platform UI/UX matching the luxury SenseLab design language across all 5 screenshot specifications; established global CSS variable tokens in `:root` and `.dark` (`--brand-primary-blue`, `--brand-primary-orange`, `--brand-gradient`, `--brand-card-bg`, etc.) allowing instantaneous one-variable color updates across the entire site; redesigned `Welcome.vue` with ambient radial lighting meshes, 3-card metric window with 3 overlapping floating cards, 6-card bento grid, deep obsidian dark laser stage, 6-node architecture hub with central glowing core, and orbital arch pricing tiers; and updated authentication layouts with matching rounded-3xl cards and ribbon glyphs.
- **Impact**: Elevates the platform visual standard from a standard portal to an international-grade modern AI startup aesthetic, with zero hardcoded ad-hoc color fragments and total centralized theme configurability through pure CSS variables.
- **Files Changed**: `resources/css/app.css`, `resources/js/pages/Welcome.vue`, `resources/js/layouts/auth/AuthCardLayout.vue`, `resources/js/layouts/auth/AuthSimpleLayout.vue`, `.agent/MEMORY.md`.
- **Blast Radius**: Frontend landing page, authentication layouts, global stylesheet, and CSS token architecture.
- **Rollback Path**: `git checkout HEAD~1 -- resources/css/app.css resources/js/pages/Welcome.vue resources/js/layouts/auth/`.
- **Lessons**: Global CSS variables (`var(--brand-...)`) must be configured at the root CSS layer before updating view templates, ensuring single-source-of-truth style cascades.
### Entry 16 — 2026-10-03: Forensic QA & Hardening of Bulk Question Import Pipeline
- **Action**: Conducted forensic real-user code QA and fixed 5 critical issues in PR #3: (1) resolved unknown-subject drop bug by refactoring `ImportQuestionsJob` from nested per-subject filtering into an $O(N)$ single-pass validation loop tracking `unmapped_invalid`, (2) fixed fatal `dispatchSync($job)` / `dispatch($job)` runtime crash in `ImportQuestions.php` by passing named constructor arguments directly, (3) added `failed()` method in `ImportQuestionsJob` adhering to Job Hygiene Rule 9 with danger notifications, (4) enforced dry-run integrity in `StoreGeneratedQuestionsAction` to prevent module creation and accurately return processed totals in preview mode, and (5) added Filament dark mode CSS and `wire:poll.3s` live feedback in `import-questions.blade.php`.
- **Impact**: Eliminates silent question loss on unknown subjects, prevents 500 fatal crashes on form submission, ensures background job failures notify admins with full context, provides dark-mode fidelity in Filament, and guarantees preview accuracy before committing to the database.
- **Files Changed**: `app/Actions/QuestionBank/StoreGeneratedQuestionsAction.php`, `app/Filament/Pages/ImportQuestions.php`, `app/Jobs/ImportQuestionsJob.php`, `resources/views/filament/pages/import-questions.blade.php`, `tests/Feature/QuestionImportTest.php`, `.agent/MEMORY.md`.
- **Blast Radius**: `ImportQuestionsJob`, `ImportQuestions`, `StoreGeneratedQuestionsAction`, `resources/views/filament/pages/import-questions.blade.php`.
- **Rollback Path**: `git checkout HEAD~1 -- app/Actions/QuestionBank/StoreGeneratedQuestionsAction.php app/Filament/Pages/ImportQuestions.php app/Jobs/ImportQuestionsJob.php resources/views/filament/pages/import-questions.blade.php tests/Feature/QuestionImportTest.php`.
- **Lessons**: Passing unit tests (`$job->handle()`) can mask fatal runtime calling conventions (`Job::dispatchSync($job)`); dry-run methods must never trigger write side-effects (e.g. creating content modules) during validation passes.

### Entry 15 — 2026-10-03: Filament Bulk Question Import Page (PMA Academic)
- **Action**: Added a Filament "Import Questions" admin page (`app/Filament/Pages/ImportQuestions.php`, nav group "Question Bank") with domain select (defaults to PMA_INITIAL), JSON upload (10MB, local disk) and a dry-run toggle (default on); submissions validate the payload and dispatch `ImportQuestionsJob`, which find-or-creates one flat `preparation` ContentModule per subject key directly under the domain (no parent/child — ContentModule has no parent_id) and inserts questions in batches of 50 via `StoreGeneratedQuestionsAction` (extended with per-item `question_type` + `dryRun` preview mode; `short_answer` items persist `payload: ['model_answer' => ...]`). Dry runs execute synchronously and render a per-subject report (inserted / duplicates_skipped / invalid) from cache (`question_import_report_{uuid}`, 10-min TTL); live runs are queued and notify the admin via a Filament database notification. A codebase-wide search confirmed the rumored "Universal Question Ingestion & Generation Engine" modal does not exist in the repo (no matches for any of its strings; ListQuestionBanks has only CreateAction), so the new page is not redundant. Added Pest coverage (`tests/Feature/QuestionImportTest.php`).
- **Impact**: Enables no-SSH bulk import of the 1,462-question PMA Academic pack (166 MCQs + 1,296 short answers across 5 subjects) directly from the admin panel; hash-dedup guarantees re-runs never create duplicates; invalid items are skipped and reported instead of failing the batch.
- **Files Changed**: `app/Filament/Pages/ImportQuestions.php`, `app/Jobs/ImportQuestionsJob.php`, `resources/views/filament/pages/import-questions.blade.php`, `tests/Feature/QuestionImportTest.php`, `app/Actions/QuestionBank/StoreGeneratedQuestionsAction.php`, `.agent/MEMORY.md`, `.agent/ARCHITECTURE.md`.
- **Blast Radius**: `StoreGeneratedQuestionsAction` (new optional params only — existing callers unaffected), new Filament page + job.
- **Rollback Path**: `git revert` the feature branch merge commit, or `git checkout <base> -- app/Actions/QuestionBank/StoreGeneratedQuestionsAction.php` to restore the action.
- **Lessons**: Filament pages are the correct "thin" layer for admin workflows (form-schema validation replaces FormRequest); dry-run accuracy requires the preview path to share the exact dedup code as the write path; production modules are flat — subject modules are created directly under the domain, never nested; verify admin-panel intel against the actual codebase before building (the reported ingestion modal did not exist).

### Entry 16 — 2026-10-03: One-Time PMA Subject-Module Link Repair
- **Action**: Added a repair workflow on the Filament "Import Questions" page ("Link Questions to Modules" header action + dry-run toggle defaulting on) backed by `LinkPmaSubjectModulesJob`: reads `database/data/pma_academic_subject_map.json` (797 code=>subject entries generated from the final PMA dataset), creates the five flat `preparation` subject modules under the chosen domain if missing, and attaches every existing PMA-ACAD question to its module via duplicate-safe `insertOrIgnore` pivot writes in 100-row chunks (one transaction per chunk). Questions already linked are counted as `already_linked`; the ~5 import-skipped duplicates resolve through a normalized-text-hash fallback (`linked_by_text_match`); unknown codes land in `question_not_found` — zero silent drops. Dry-run is 100% read-only and runs the identical counting logic. Extracted shared `SubjectModuleProvisioner` (canonical subject titles/slugs/firstOrCreate keys) now used by both `ImportQuestionsJob` and the repair job. The repair dispatches no vectorization jobs: module creation bypasses Eloquent events (`findOrCreateQuietly`) with explicit sort_order defaulting and manual table-cache flush. Added Pest coverage (`tests/Feature/LinkPmaSubjectModulesTest.php`).
- **Impact**: Repairs the production state where the PR #3 import inserted 792 questions but never created the five subject modules, leaving them unlinked and invisible to subject-scoped flows; after the repair run, all PMA Academic questions resolve per-subject through the standard `module_questions` pivot.
- **Files Changed**: `app/Actions/QuestionBank/SubjectModuleProvisioner.php`, `app/Jobs/LinkPmaSubjectModulesJob.php`, `app/Filament/Pages/ImportQuestions.php`, `app/Jobs/ImportQuestionsJob.php`, `resources/views/filament/pages/import-questions.blade.php`, `database/data/pma_academic_subject_map.json`, `tests/Feature/LinkPmaSubjectModulesTest.php`, `.agent/MEMORY.md`, `.agent/ARCHITECTURE.md`.
- **Blast Radius**: `ImportQuestionsJob` (module creation now delegates to the provisioner — behavior identical, titles still come from the uploaded file), `ImportQuestions` page (new section + action; existing import flow untouched), new job + provisioner.
- **Rollback Path**: `git revert` the feature branch merge commit; already-linked pivot rows are harmless (duplicate-safe) and modules can be deactivated from the admin.
- **Lessons**: Bypassing Eloquent events (to suppress vectorization) also bypasses `CachesTableData` invalidation — flush the table cache explicitly after query-builder writes; Filament database notifications + cached reports are the established admin feedback channel for background jobs.

### Entry 14 — 2026-10-03: AI Exam Mentor Conciseness Calibration, Markdown Hash Sanitization & Resizable UI
- **Action**: Calibrated `AiTutorService` system prompt to mandate extreme brevity (2–3 lines / single compact paragraph by default, key terms in bold, strictly no markdown heading hashes, and adherence to user-specified line constraints); updated `AiTutorWidget.vue` `formatContent` to strip raw `#`/`##`/`###` hashtags and format clean headings; and implemented smooth mouse-drag and touch-swipe resize handles (top, left, corner) with pointer capture plus a header maximize/restore toggle.
- **Impact**: Eliminates massive, overwhelming walls of text and raw `###` heading artifacts in candidate chat; provides mobile and desktop users tactile control to swipe or drag the chat drawer to any preferred height and width or toggle full-height with one tap.
- **Files Changed**: `app/Services/Ai/AiTutorService.php`, `app/Services/Ai/EmbeddingService.php`, `resources/js/components/ai/AiTutorWidget.vue`, `.agent/MEMORY.md`.
- **Blast Radius**: `AiTutorService`, `EmbeddingService`, `AiTutorWidget.vue`.
- **Rollback Path**: `git checkout HEAD~1 -- app/Services/Ai/AiTutorService.php app/Services/Ai/EmbeddingService.php resources/js/components/ai/AiTutorWidget.vue`.
- **Lessons**: LLM prompts require strict negative constraints ("NEVER output markdown heading hashtags") and explicit length defaults (2-3 lines); chat widgets must provide flexible resizing handles with `setPointerCapture` and `touch-action: none` so mobile touch gestures and desktop dragging work seamlessly without triggering page scrolling.

### Entry 13 — 2026-10-03: Phase 5 AI Mentor & MCQ Engine Forensic Code Audit & Hardening
- **Action**: Performed line-by-line manual code audit of Phase 5 AI tutoring, vectorization, and MCQ execution engines; resolved 7 latent bugs and edge cases: (1) wired live candidate option selections from `McqPlayer.vue` to `AiTutorWidget.vue`, (2) added HTTP status validation to `streamOpenAiResponse` to prevent silent empty message saves, (3) sanitized multi-turn conversation roles for Google Gemini API to eliminate 400 Bad Request crashes, (4) wrapped SSE streaming in `try/catch` with structured `event: error` emissions, (5) chunked Gemini (100) and OpenAI (500) embedding batches to prevent API batch overflow, (6) fixed a critical PHP type juggling security flaw in `McqExecutionController` where non-numeric strings (e.g. `'hacked'`) evaluated to `(int) 0` and matched option 0 as correct, and (7) updated test submission validation from `'required'` to `'present'` so empty test submissions never throw 422 errors.
- **Impact**: Guarantees zero silent failures across external AI providers, ensures candidates receive real-time Socratic feedback tailored to their exact selected choice on screen, enforces Zero-Knowledge answer key scrubbing on active test fetches, prevents submission type-juggling exploits, and connects `McqPlayer` to server-authoritative grading (`POST /api/modules/{module}/submit`).
- **Files Changed**: `app/Services/Ai/AiCompletionService.php`, `app/Services/Ai/EmbeddingService.php`, `app/Services/Ai/KnowledgeVectorizationService.php`, `app/Services/Ai/AiTutorService.php`, `app/Http/Controllers/AiTutorController.php`, `app/Http/Controllers/ContentModuleController.php`, `app/Http/Controllers/McqExecutionController.php`, `resources/js/components/sdui/McqPlayer.vue`, `resources/js/components/ai/AiTutorWidget.vue`, `tests/Feature/ContentModuleExecutionTest.php`.
- **Blast Radius**: `AiCompletionService`, `EmbeddingService`, `KnowledgeVectorizationService`, `AiTutorService`, `AiTutorController`, `ContentModuleController`, `McqExecutionController`, `McqPlayer.vue`, `AiTutorWidget.vue`.
- **Rollback Path**: `git checkout HEAD~1 -- app/Services/Ai/ app/Http/Controllers/ resources/js/components/sdui/McqPlayer.vue resources/js/components/ai/AiTutorWidget.vue tests/Feature/ContentModuleExecutionTest.php`.
- **Lessons**: Passing unit tests with `fake` drivers and clean sample data easily conceal third-party provider schema constraints, client-side event disconnection, and PHP loose type comparison exploits (`(int) 'string' === 0`). Forensic manual code inspection with real user scenarios is mandatory.

### Entry 12 — 2026-10-03: Governance Layer Consolidation & Deduplication Refactor
- **Action**: Consolidated dual governance directories (`.agents/` into canonical `.agent/`), eliminated 12 cross-cutting duplicate rules via single canonical homes, created `SYSTEM_RULES.md` and `UI_REFERENCE.md`, migrated all 12 rule files to `.agent/rules/`, and established the 4-tier Load-Tier reading system in root `AGENTS.md`.
- **Impact**: Slashes per-turn agent token consumption from 40–60k to tier-based reads, eliminates rule contradiction risks, and hardens the pure JavaScript Vue standard and version pins while preserving 100% of scar tissue (all 11 RCAs).
- **Files Changed**: `AGENTS.md`, `.agent/AGENT.md`, `.agent/ARCHITECTURE.md`, `.agent/PATTERNS.md`, `.agent/MEMORY.md`, `.agent/SYSTEM_RULES.md`, `.agent/UI_REFERENCE.md`, `.agent/rules/*.md`.
- **Blast Radius**: Autonomous agents, subagent prompt injection, governance rules, and context loading budgets.
- **Rollback Path**: Revert governance refactor via `git checkout HEAD~1 -- AGENTS.md .agent/`.
- **Lessons**: A split directory structure (`.agent/` vs `.agents/`) causes rule divergence; centralized load-tiered governance ensures deterministic senior architect behavior on every turn.

### Entry 11 — 2026-10-03: Governance Hardening & Engineering Resilience Protocol
- **Action**: Codified exhaustive production failure mode mitigations, 12-point Pre-Conclusion Gate, concurrency/locking contracts, OWASP security protections, queue worker isolation, and data lifecycle compliance across the governance layer.
- **Impact**: Eliminates high-severity race conditions (double submissions, duplicate webhook processing), prevents PgBouncer statement crashes and FastCGI buffer overflow, enforces sub-200ms transaction boundaries, and guarantees zero PII/answer-key leakage.
- **Files Changed**: `.agent/AGENT.md`, `.agent/ARCHITECTURE.md`, `.agent/PATTERNS.md`, `.agent/MEMORY.md`.
- **Blast Radius**: Autonomous agents, code review pipelines, database transactions, background queue workers, and test validation runners.
- **Rollback Path**: Revert governance markdown commits via `git checkout HEAD~1 -- .agent/`.
- **Lessons**: Autonomous coding assistants require explicit, falsifiable architectural gates (12-point checklist) and hard failure-mode matrices to prevent recurring regressions on remote cloud databases.

### Entry 10 — 2026-09-23: Question Ingestion Standards & Anti-Pattern Governance
- **Action**: Established strict repository standards for bulk data ingestion, remote PostgreSQL transaction limits, and Livewire state management.
- **Impact**: Banned sequential loops inside `DB::transaction(...)` across remote Supabase connections (mandating sub-200ms bulk multi-row inserts); enforced the Cache-Pointer pattern (`$batchId`) to eliminate Livewire modal payload bloat; mandated strict `NULL` sanitization on unique nullable columns (`code`); and instituted the Zero-Token rule (pure PHP for formatted MCQs, AI strictly for distractor generation).

### Entry 09 — 2026-09-19: Senior Autonomous Governance System Establishment
- **Action**: Formalized the repository governance protocol across `AGENTS.md`, `.agent/AGENT.md`, `.agent/ARCHITECTURE.md`, `.agent/PATTERNS.md`, and `.agent/MEMORY.md`.
- **Impact**: Established mandatory pre-conclusion checks (Pest testing, Pint code formatting, zero orphan classes), strict thin controller patterns, loop complexity limits ($O(1)$ lookups, max 2 indentation levels), and anti-AI frontend design laws.


### Entry 08 — 2026-09-17: Azure App Service File Upload Limits (64MB)
- **Action**: Configured `client_max_body_size 64M;` in root `default` Nginx configuration and deployed `uploads.ini` (`upload_max_filesize = 64M`, `post_max_size = 64M`, `memory_limit = 256M`).
- **Impact**: Resolved HTTP 413 `Request Entity Too Large` errors generated by Nginx reverse proxy during large PDF past-paper and syllabus uploads.

### Entry 07 — 2026-09-16: Candidate Authentication & 403 Redirection Resolution
- **Action**: Refactored `RedirectsToCurrentTeam` middleware and Fortify login/signup pipelines.
- **Impact**: Ensured standard candidate accounts lacking administrative Filament tenant access are gracefully redirected to the public portal `/#exams` rather than hitting a fatal 403 tenant authorization abort.

### Entry 06 — 2026-09-16: FastCGI Buffer Expansion & 502 Bad Gateway Fix
- **Action**: Enlarged Nginx FastCGI buffer sizes (`fastcgi_buffers 4 256k; fastcgi_buffer_size 128k;`) and disabled asset preload link headers (`AddLinkHeadersForPreloadedAssets`).
- **Impact**: Prevented HTTP 502 Bad Gateway crashes triggered when upstream PHP-FPM responses exceeded standard 4KB/8KB header buffer thresholds during Inertia page hydration.

### Entry 05 — 2026-09-15: Reverse Proxy SSL Termination & Mixed Content Security
- **Action**: Added `$middleware->trustProxies(at: '*')` in `bootstrap/app.php` and enforced HTTPS asset URL generation.
- **Impact**: Fixed mixed-content blocking on Azure App Service where Front Door/ARR terminates SSL and forwards cleartext HTTP to container port 8080.

### Entry 04 — 2026-09-14: Real-time Socratic AI Tutor SSE Streaming Pipeline
- **Action**: Built `AiTutorService` utilizing Server-Sent Events (`text/event-stream`), pedagogical Socratic ladder hints, and pgvector cosine search.
- **Impact**: Delivers instant, low-latency streaming hints to candidates without ever revealing active MCQ answer keys, backed by automated fallback drivers for deterministic testing.

### Entry 03 — 2026-09-12: Server-Driven UI (SDUI) Dynamic Layout Engine
- **Action**: Engineered `LayoutEngineService` to dynamically compile domain landing pages, subject hierarchies, and exam flows from database JSON schema definitions.
- **Impact**: Eliminated brittle hardcoded frontend routes. Landing pages and subject trees adapt instantly to backend schema updates with zero client bundle recompilation.

### Entry 02 — 2026-09-10: Supabase PostgreSQL PgBouncer & Emulated Prepares
- **Action**: Configured `PDO::ATTR_EMULATE_PREPARES => true` in `config/database.php` on port 6543 (transaction pooling) and created `CachingUserProvider` with Redis caching.
- **Impact**: Prevented fatal deallocated prepared statement exceptions during PgBouncer pooler connection switches and eliminated a ~1,700ms remote database latency penalty on authenticated requests.

### Entry 01 — 2026-09-08: Multi-Tenant Filament v5 Administration Foundation
- **Action**: Established administrative panels for domains, hierarchical nodes, question banks, candidate management, and team-scoped resources.
- **Impact**: Separated system administrative capabilities from public candidate interfaces with granular Spatie role and permission enforcement.

---

## ⚠️ Known Quirks & Technical Debt

### 1. Remote Database Latency & Test Execution Strategy
- **Quirk**: The primary PostgreSQL database is hosted on remote Supabase infrastructure. Round-trip network latency can reach 50–150ms per query.
- **Rule**: `tests/Pest.php` strictly utilizes `Illuminate\Foundation\Testing\DatabaseTransactions` instead of `RefreshDatabase`. **Never run `php artisan migrate:fresh` inside tests**, as full DDL drops over remote connections will cause tests to take 10+ minutes or hit connection timeouts. (Canonical rule: Pest test transaction rule — see [`.agent/rules/testing_validation.md § 1`](file:///.agent/rules/testing_validation.md)).

### 2. Supabase PgBouncer & Emulated Prepared Statements
- **Quirk**: Supabase port `6543` operates in transaction pooling mode. PgBouncer destroys server-side named prepared statements (`pdo_stmt_XXXXX`) when pooling worker connections.
- **Requirement**: `PDO::ATTR_EMULATE_PREPARES => true` MUST remain enabled in `config/database.php` for `pgsql`. Disabling this will immediately trigger `SQLSTATE[42P05]: prepared statement already exists` or `statement does not exist` errors in production.

### 3. Azure App Service Container Process Lifecycle
- **Quirk**: In Azure App Service Linux containers, file edits to Nginx templates or `uploads.ini` do not take effect automatically because the container runs isolated daemon processes.
- **Requirement**: Startup commands must execute:
  ```bash
  cp /home/site/wwwroot/default /etc/nginx/sites-available/default && \
  cp /home/site/wwwroot/uploads.ini /usr/local/etc/php/conf.d/uploads.ini && \
  service nginx reload && \
  (kill -USR2 $(pidof php-fpm | awk '{print $1}') 2>/dev/null || true)
  ```
- Any storage symlink must be verified or recreated using `php artisan storage:link 2>/dev/null || true`.

### 4. Database Collation & Multi-Byte Unicode / Urdu Support
- **Quirk**: PostgreSQL handles UTF-8 natively with standard collation, but MySQL/MariaDB fallback connections must explicitly maintain `utf8mb4_unicode_ci` to prevent Urdu script truncation, casing mismatches in question bank search queries, or emoji corruption.

### 5. Queue Workers & Background Processing
- **Quirk**: Default local environment runs `QUEUE_CONNECTION=sync` or `database`. In production, heavy AI embedding calculations (`GenerateEmbeddingJob`) and bulk question imports will block web workers if processed synchronously.
- **Requirement**: Production deployment requires a persistent daemon worker (`php artisan queue:work --tries=3 --timeout=120`) managed via Supervisor or systemd.

### 6. Regional Payment Gateway & Notification Webhooks (EasyPaisa / JazzCash / WhatsApp)
- **Quirk**: Candidate subscriptions currently use manual proof uploads (TRX ID and payment receipt image), with automated IPN webhooks planned for EasyPaisa and JazzCash merchant accounts.
- **Requirement**:
  - Webhook endpoints (`POST /api/webhooks/easypaisa` and `POST /api/webhooks/jazzcash`) must implement idempotency key verification and HMAC-SHA256 signature checking before calling `SubscriptionManager::activate()`.
  - Meta WhatsApp Cloud API integrations must format recipient numbers in international E.164 format (`923XXXXXXXXX`) with a 3-second HTTP timeout and Redis queuing to ensure notification dispatches never block candidate web workers.

### 7. Livewire Modal Payload Size & Cache-Pointer Architecture
- **Quirk**: Livewire serializes component properties on every client-server exchange. Passing large datasets (>20 items) into Filament modal state degrades UI responsiveness and risks payload corruption.
- **Requirement**: Always store temporary bulk records in cache and pass only the UUID `batch_id` into Livewire form state. (Canonical pattern: Cache-Pointer pattern — see [`.agent/rules/database_performance.md § 4`](file:///.agent/rules/database_performance.md)).

### 8. Remote PostgreSQL PgBouncer Bulk Insert Performance
- **Quirk**: Supabase PgBouncer (port 6543) incurs 50–80ms round-trip latency per query. Executing loops of individual `INSERT`s or pivot `attach()` calls inside a transaction causes long-lived locks (>10s) and pool exhaustion.
- **Requirement**: Multi-record mutations must strictly use multi-row bulk `insert` and `insertOrIgnore` to guarantee all transaction locks complete in under 200 milliseconds. (Canonical rule: Sub-200ms transaction law — see [`.agent/rules/database_performance.md § 2`](file:///.agent/rules/database_performance.md)).

---

## ⚠️ Hardening Directives

The following 8 directives are durable, non-negotiable architectural mandates:

- **HD-1: Time-Zone Discipline**: All storage UTC. All display PKT. All exam timers server-authoritative. Client clock is an untrusted hint used only for visual animations.
- **HD-2: Idempotency Everywhere**: Webhooks, job dispatch, notification send, subscription activation — each requires a unique idempotency key persisted before side-effects occur.
- **HD-3: PII & Answer-Key Log Hygiene**: Structured logs may never contain candidate emails, phone numbers, passwords, CNIC numbers, TRX IDs, or MCQ correct-answer indices.
- **HD-4: Currency Precision**: All PKR amounts stored as integer paisa (never float). Format with comma thousands separators at the presentation layer only (`PKR 2,500`).
- **HD-5: Text Safety**: All user-supplied text (question banks, Roman Urdu chat, receipts) is normalized, length-capped, and HTML-escaped before storage and again before render.
- **HD-6: Blast-Radius Declaration**: Any change touching `SubscriptionManager`, `AiTutorService`, `SduiPayloadTransformer`, or the Fortify pipeline requires an explicit Blast Radius block and full CI run.
- **HD-7: Rollback Path**: Every migration, queue schema change, and integration update ships with a documented, tested rollback command.
- **HD-8: Observability Minimum**: Every new endpoint and every new queued job emits a structured JSON log line with `correlation_id`, `user_id` (nullable), `domain_slug` (nullable), and `duration_ms`.



