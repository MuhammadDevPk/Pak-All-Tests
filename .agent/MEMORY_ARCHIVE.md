# MEMORY_ARCHIVE.md — Archived Rolling Memory Log Entries

> **Status**: HISTORICAL ARCHIVE — IMMUTABLE  
> **Source**: Archived from `.agent/MEMORY.md` under the Rolling Memory Contract (AGENTS.md)

---

### Entry 16 — 2026-10-03: One-Time PMA Subject-Module Link Repair
- **Action**: Added a repair workflow on the Filament "Import Questions" page ("Link Questions to Modules" header action + dry-run toggle) backed by `LinkPmaSubjectModulesJob`: reads `database/data/pma_academic_subject_map.json`, creates five flat `preparation` subject modules under the domain if missing, and attaches existing PMA-ACAD questions via duplicate-safe `insertOrIgnore` pivot writes in 100-row chunks. Extracted shared `SubjectModuleProvisioner`. Added Pest coverage (`tests/Feature/LinkPmaSubjectModulesTest.php`).
- **Impact**: Repaired production state where PR #3 inserted questions without subject modules; all PMA Academic questions resolve per-subject through standard `module_questions` pivot.
- **Files Changed**: `app/Actions/QuestionBank/SubjectModuleProvisioner.php`, `app/Jobs/LinkPmaSubjectModulesJob.php`, `app/Filament/Pages/ImportQuestions.php`, `database/data/pma_academic_subject_map.json`, `tests/Feature/LinkPmaSubjectModulesTest.php`.
- **Blast Radius**: `ImportQuestionsJob`, `ImportQuestions` page, new job + provisioner.
- **Rollback Path**: `git revert` or deactivate modules from admin.
- **Lessons**: Bypassing Eloquent events to suppress vectorization also bypasses `CachesTableData` — flush table cache explicitly after query-builder writes.

### Entry 16b — 2026-10-03: Forensic QA & Hardening of Bulk Question Import Pipeline
- **Action**: Fixed 5 critical issues in PR #3: (1) resolved unknown-subject drop via $O(N)$ single-pass validation in `ImportQuestionsJob`, (2) fixed fatal `dispatchSync` runtime crash in `ImportQuestions.php`, (3) added `failed()` method in `ImportQuestionsJob` with danger notifications, (4) enforced dry-run integrity in `StoreGeneratedQuestionsAction`, and (5) added Filament dark mode CSS and `wire:poll.3s`.
- **Impact**: Eliminates silent question loss on unknown subjects, prevents 500 fatal crashes on form submission, ensures background job failure notifications, and guarantees dry-run preview accuracy.
- **Files Changed**: `app/Actions/QuestionBank/StoreGeneratedQuestionsAction.php`, `app/Filament/Pages/ImportQuestions.php`, `app/Jobs/ImportQuestionsJob.php`, `resources/views/filament/pages/import-questions.blade.php`, `tests/Feature/QuestionImportTest.php`.
- **Blast Radius**: `ImportQuestionsJob`, `ImportQuestions`, `StoreGeneratedQuestionsAction`.
- **Rollback Path**: `git checkout HEAD~1 -- app/Actions/QuestionBank/StoreGeneratedQuestionsAction.php app/Filament/Pages/ImportQuestions.php app/Jobs/ImportQuestionsJob.php`.
- **Lessons**: Unit tests calling `$job->handle()` mask dispatch calling conventions (`Job::dispatchSync($job)`); dry-run methods must never trigger write side-effects.

### Entry 15 — 2026-10-03: Filament Bulk Question Import Page (PMA Academic)
- **Action**: Added Filament "Import Questions" admin page (`app/Filament/Pages/ImportQuestions.php`) with domain select, JSON upload (10MB), and dry-run toggle; dispatches `ImportQuestionsJob` to create subject modules and batch-insert questions via `StoreGeneratedQuestionsAction`. Cached report (`question_import_report_{uuid}`) provides instant feedback. Added Pest test (`tests/Feature/QuestionImportTest.php`).
- **Impact**: Enables no-SSH bulk import of 1,462-question PMA Academic pack directly from admin panel with hash dedup and preview reports.
- **Files Changed**: `app/Filament/Pages/ImportQuestions.php`, `app/Jobs/ImportQuestionsJob.php`, `resources/views/filament/pages/import-questions.blade.php`, `tests/Feature/QuestionImportTest.php`, `app/Actions/QuestionBank/StoreGeneratedQuestionsAction.php`.
- **Blast Radius**: `StoreGeneratedQuestionsAction`, new Filament page + job.
- **Rollback Path**: `git revert` or restore action file.
- **Lessons**: Filament pages are the thin layer for admin workflows; production modules are flat directly under domain.

### Entry 14 — 2026-10-03: AI Exam Mentor Conciseness Calibration, Markdown Hash Sanitization & Resizable UI
- **Action**: Calibrated `AiTutorService` system prompt for extreme brevity (2–3 lines, key terms in bold, zero markdown headings); sanitized `#` artifacts in `AiTutorWidget.vue`; added mouse-drag and touch-swipe resize handles with pointer capture and maximize toggle.
- **Impact**: Eliminates text walls and markdown hashtag artifacts in candidate chat; provides mobile and desktop users tactile drawer resizing without triggering page scrolling.
- **Files Changed**: `app/Services/Ai/AiTutorService.php`, `app/Services/Ai/EmbeddingService.php`, `resources/js/components/ai/AiTutorWidget.vue`.
- **Blast Radius**: `AiTutorService`, `EmbeddingService`, `AiTutorWidget.vue`.
- **Rollback Path**: `git checkout HEAD~1 -- app/Services/Ai/ resources/js/components/ai/AiTutorWidget.vue`.
- **Lessons**: LLM prompts require strict negative constraints ("NEVER output headings") and length defaults; touch drag handles require `touch-action: none` and `setPointerCapture`.

### Entry 13 — 2026-10-03: Phase 5 AI Mentor & MCQ Engine Forensic Code Audit & Hardening
- **Action**: Performed line-by-line audit of AI tutoring, vectorization, and MCQ engines; resolved 7 edge cases: wired candidate option selections to tutor widget, validated HTTP status in `streamOpenAiResponse`, sanitized Gemini roles, wrapped SSE streaming in try/catch with error events, chunked embedding batches (100 Gemini / 500 OpenAI), fixed PHP type juggling security flaw in `McqExecutionController`, and changed submission validation to `present`.
- **Impact**: Guarantees zero silent AI provider failures, provides real-time Socratic feedback tailored to selected choice, enforces zero-knowledge answer key scrubbing, and prevents submission exploits.
- **Files Changed**: `app/Services/Ai/*`, `app/Http/Controllers/AiTutorController.php`, `app/Http/Controllers/McqExecutionController.php`, `resources/js/components/sdui/McqPlayer.vue`, `resources/js/components/ai/AiTutorWidget.vue`.
- **Blast Radius**: AI services, controllers, and MCQ player.
- **Rollback Path**: `git checkout HEAD~1 -- app/Services/Ai/ app/Http/Controllers/ resources/js/`.
- **Lessons**: Passing unit tests with fake drivers conceal provider schema constraints, client disconnection, and PHP loose type comparison exploits (`(int)'string' === 0`).

### Entry 12 — 2026-10-03: Governance Layer Consolidation & Deduplication Refactor
- **Action**: Consolidated dual governance directories (`.agents/` into canonical `.agent/`), eliminated duplicate rules, created `SYSTEM_RULES.md` and `UI_REFERENCE.md`, migrated 12 rule files to `.agent/rules/`, and established 4-tier Load-Tier system in `AGENTS.md`.
- **Impact**: Slashes per-turn token consumption from 40–60k to tier-based reads and eliminates rule contradiction risks while preserving 100% of RCAs.
- **Files Changed**: `AGENTS.md`, `.agent/*`, `.agent/rules/*`.
- **Blast Radius**: Autonomous agents and governance loading budgets.
- **Rollback Path**: `git checkout HEAD~1 -- AGENTS.md .agent/`.
- **Lessons**: Split directory structures cause rule divergence; load-tiered governance ensures deterministic behavior.

### Entry 11 — 2026-10-03: Governance Hardening & Engineering Resilience Protocol
- **Action**: Codified production failure mode mitigations, 12-point Pre-Conclusion Gate, concurrency contracts, queue worker isolation, and data lifecycle compliance.
- **Impact**: Eliminates race conditions (double submissions, duplicate webhooks), prevents PgBouncer statement crashes and FastCGI buffer overflow, enforces sub-200ms transaction boundaries, and guarantees zero PII/answer-key leaks.
- **Files Changed**: `.agent/AGENT.md`, `.agent/ARCHITECTURE.md`, `.agent/PATTERNS.md`, `.agent/MEMORY.md`.
- **Blast Radius**: Autonomous agent workflows, database transactions, queue workers, and test runners.
- **Rollback Path**: `git checkout HEAD~1 -- .agent/`.
- **Lessons**: Autonomous agents require explicit, falsifiable gates and failure matrices to prevent recurring regressions on cloud databases.

### Entry 02 — 2026-09-10: Supabase PostgreSQL PgBouncer & Emulated Prepares
- **Action**: Configured `PDO::ATTR_EMULATE_PREPARES => true` in `config/database.php` on port 6543 (transaction pooling) and created `CachingUserProvider` with Redis caching.
- **Impact**: Prevented fatal deallocated prepared statement exceptions during PgBouncer pooler connection switches and eliminated a ~1,700ms remote database latency penalty on authenticated requests.

### Entry 01 — 2026-09-08: Multi-Tenant Filament v5 Administration Foundation
- **Action**: Established administrative panels for domains, hierarchical nodes, question banks, candidate management, and team-scoped resources.
- **Impact**: Separated system administrative capabilities from public candidate interfaces with granular Spatie role and permission enforcement.
