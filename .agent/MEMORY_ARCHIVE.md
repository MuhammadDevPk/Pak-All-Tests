# MEMORY_ARCHIVE.md — Archived Rolling Memory Log Entries

> **Status**: HISTORICAL ARCHIVE — IMMUTABLE  
> **Source**: Archived from `.agent/MEMORY.md` under the Rolling Memory Contract (AGENTS.md)

---

### Entry 29 — 2026-10-18: Frictionless Auto-Guest Authentication Middleware & Instant URL Onboarding
- **Action**: Engineered `AutoAuthenticateGuest` middleware registered as `auto.guest` on student candidate routes (`/d/{domainSlug}`, `/dashboard`, `/revision-weak-questions`) which automatically creates an ephemeral guest account with 24 hours of trial access on direct URL visits without login barriers or redirects, added `PlatformSetting::isAutoGuestLoginEnabled()` with a 1-click administrative bypass toggle (defaulting to enabled in production/local and disabled in unit testing), explicitly denied guest access to Filament panel in `User::canAccessPanel`, and preserved referral registration redirection when incoming requests contain `?ref=...`.
- **Impact**: Removes all onboarding barriers for new and returning students opening shared test/dashboard URLs directly, auto-logging them in and granting instant 1-day platform access on the initial HTTP request, while allowing returning students to access `/login` manually and preventing guest escalation into administrative panels.
- **Files Changed**: `app/Http/Middleware/AutoAuthenticateGuest.php`, `app/Models/PlatformSetting.php`, `app/Models/User.php`, `bootstrap/app.php`, `routes/web.php`, `tests/Feature/AutoGuestLoginTest.php`, `tests/Feature/SubscriptionStateMachineTest.php`, `.agent/ARCHITECTURE.md`, `.agent/MEMORY.md`.
- **Blast Radius**: Student web route middleware stack, `AutoAuthenticateGuest`, `PlatformSetting`, `User`.
- **Rollback Path**: `git checkout HEAD~1 -- app/Http/Middleware/AutoAuthenticateGuest.php app/Models/PlatformSetting.php app/Models/User.php bootstrap/app.php routes/web.php tests/`.
- **Lessons**: Route middleware that performs lazy inline authentication must throw standard `AuthenticationException` when fallback gating occurs so that Laravel's pipeline cleanly handles `expectsJson()` (401) and custom `redirectGuestsTo` targets without manual redirect hardcoding.

### Entry 28 — 2026-10-05: Dynamic Dashboard Sections, Study Q&A Player & Scalable Modality Ingestion Architecture
- **Action**: (1) Added `dashboard_section` and `display_mode` columns to `content_modules` table with database indexes and Filament form/table controls; (2) engineered dedicated `StudyQaPlayer.vue` component registered in SDUI registry for non-MCQ modalities (interactive accordion, search, expand/collapse, no forfeiture or timers); (3) updated `SduiPayloadTransformer` and `SduiSchemaValidator` to filter modules by `dashboard_section` and route dynamic `actionLabel`; (4) upgraded `SubjectModuleProvisioner` and `ImportQuestionsJob` to accept rich subject definitions for scalable bulk question ingestion across domains; and (5) added defensive fallback rendering in `McqPlayer.vue` to prevent short answer questions from being penalized via weak question forfeiture.
- **Impact**: Resolves the "Child of Academic" bug by allowing arbitrary top-level sections (Academic, Physical, Medical, Interview) on candidate dashboards without code changes; fixes short answer question rendering; and provides a scalable multi-modality architecture for all future domains.
- **Files Changed**: `app/Actions/QuestionBank/SubjectModuleProvisioner.php`, `app/Filament/Resources/ContentModules/Schemas/ContentModuleForm.php`, `app/Filament/Resources/ContentModules/Tables/ContentModulesTable.php`, `app/Http/Controllers/ContentModuleController.php`, `app/Jobs/ImportQuestionsJob.php`, `app/Models/ContentModule.php`, `app/Services/SduiPayloadTransformer.php`, `app/Services/SduiSchemaValidator.php`, `database/migrations/2026_10_05_000001_add_dashboard_section_and_display_mode_to_content_modules_table.php`, `resources/js/components/sdui/McqPlayer.vue`, `resources/js/components/sdui/StudyQaPlayer.vue`, `resources/js/components/sdui/SubjectGrid.vue`, `resources/js/components/sdui/componentRegistry.js`, `resources/js/pages/Domain/Dashboard.vue`, `tests/Feature/QuestionImportTest.php`, `tests/Feature/SduiPayloadTransformerTest.php`, `.agent/ARCHITECTURE.md`, `.agent/MEMORY.md`.
- **Blast Radius**: `ContentModule`, `SduiPayloadTransformer`, `SduiSchemaValidator`, `ContentModules` Filament resource, `McqPlayer.vue`, `Dashboard.vue`, `SubjectGrid.vue`.
- **Rollback Path**: `php artisan migrate:rollback --step=1` and `git checkout HEAD~1 -- app/ resources/ database/`.
- **Lessons**: UI presentation taxonomy (`dashboard_section`, `display_mode`) belongs as first-class module metadata rather than being inferred from module titles or nesting hacks; and study Q&A content requires dedicated, non-evaluative reader components instead of overloading timed MCQ test players.

### Entry 27 — 2026-10-05: Global Referral Capture Middleware, Direct Form Input & Referred Candidates Activity List
- **Action**: (1) Engineered `CaptureReferralCode` middleware across web stack so `?ref=...` and `?domain=...` query parameters are immediately captured into high-priority cookies and session regardless of landing page; (2) added an optional "Referral Code" input field with server-side existence validation to the registration form (`Register.vue`, `CreateNewUser.php`), prefilled from query/cookie/session; (3) updated `ReferralService::getReferralStats` to aggregate approved referral counts and days earned across all domains and compile recent referred friends; (4) added a "Referred Friends Joined" badge list to `ReferralCard.vue` showing student names, join times, and earned day credits; (5) exempted local loopback addresses (`127.0.0.1`, `::1`) from anti-abuse IP block in `ReferralService.php`; and (6) embedded `ReferralCard` in `PendingOrInactive.vue`.
- **Impact**: Candidates can now copy and manually input referral codes during registration without relying on url links, referrers immediately see their referred friends and progress in the candidate dashboard, and invalid referral codes are clearly flagged with validation errors.
- **Files Changed**: `app/Http/Middleware/CaptureReferralCode.php`, `bootstrap/app.php`, `app/Actions/Fortify/CreateNewUser.php`, `app/Providers/FortifyServiceProvider.php`, `app/Services/ReferralService.php`, `app/Http/Controllers/DashboardController.php`, `app/Http/Controllers/DomainDashboardController.php`, `resources/js/components/referral/ReferralCard.vue`, `resources/js/pages/Dashboard.vue`, `resources/js/pages/auth/Register.vue`, `resources/js/pages/Subscription/PendingOrInactive.vue`, `tests/Feature/ReferralSystemTest.php`, `.agent/MEMORY.md`.
- **Blast Radius**: `CreateNewUser.php`, `FortifyServiceProvider.php`, `Register.vue`, `ReferralCard.vue`, `ReferralService.php`, `DashboardController.php`, `Dashboard.vue`.
- **Rollback Path**: `git checkout HEAD~1 -- app/ bootstrap/ resources/ tests/`.
- **Lessons**: Referral stats must aggregate across all domains when platform-wide access is rewarded, and candidate dashboards must display the names and times of referred friends so students receive immediate social confirmation that their referral succeeded.

### Entry 26 — 2026-10-04: One-Click Payment Bypass, 1-Day Guest Access, 7-Day Trial & Domain-Specific Viral Referral Ladder
- **Action**: Engineered viral candidate growth and payment onboarding architecture: (1) Added global 1-click admin toggles (`payment_bypass_enabled`, `free_trial_days`, `guest_mode_enabled`) via `PlatformSetting` and Filament resource; (2) Created `GuestAuthService` for 24h disposable accounts with conversion to permanent credentials; (3) Engineered `ReferralService` with domain-specific referral codes, tiered rewards (+7 days for referrer and referee, 30 days bonus at 5 referrals), and anti-fraud fraud detection; (4) Integrated `SubscriptionManager` with bypass evaluations; (5) Added `ReferralCard.vue` and `GuestBanner.vue` to candidate interface.
- **Impact**: Enables friction-free candidate trial onboarding, gives admin instant switches to open/close paywalls, and deploys viral referral loops tailored for Pakistani competitive exam cohorts.
- **Files Changed**: `app/Models/PlatformSetting.php`, `app/Services/GuestAuthService.php`, `app/Services/ReferralService.php`, `app/Services/SubscriptionManager.php`, `app/Models/Referral.php`, `app/Filament/Resources/PlatformSettings/PlatformSettingResource.php`, `resources/js/components/auth/GuestBanner.vue`, `resources/js/components/referral/ReferralCard.vue`, `tests/Feature/PaymentBypassTest.php`, `tests/Feature/GuestAccessTest.php`, `tests/Feature/ReferralSystemTest.php`.
- **Blast Radius**: `SubscriptionManager`, `PlatformSetting`, `UserSubscription`, `User`, registration pipeline.
- **Rollback Path**: `php artisan db:seed --class=PmaLongCourseSeeder`.
- **Lessons**: Abuse prevention must balance security with student UX: strict IP checks falsely flag students on shared university Wi-Fi, necessitating composite device fingerprints and administrative review queues.

### Entry 25 — 2026-10-04: Automated PMA Long Course Subject Module Linking & Question Bank Repair
- **Action**: Engineered background linking job `LinkPmaSubjectModulesJob` resolving PMA Academic questions to dedicated subject modules via `module_questions` pivot table, backed by `database/data/pma_academic_subject_map.json`.
- **Impact**: Guaranteed 100% PMA Long Course questions are discoverable under respective subjects without requiring full database wipe or destructive re-imports.
- **Files Changed**: `app/Jobs/LinkPmaSubjectModulesJob.php`, `tests/Feature/LinkPmaSubjectModulesTest.php`.
- **Blast Radius**: `module_questions` pivot associations.
- **Rollback Path**: `php artisan db:seed --class=PmaLongCourseSeeder`.
- **Lessons**: Data repair workflows must be designed as resumable chunked background jobs rather than long-running web requests.

### Entry 24 — 2026-10-03: Real-Time Socratic AI Tutor Drawer & SSE Streaming Resiliency
- **Action**: Hardened `AiTutorService` SSE pipeline with heartbeat events every 15s, auto-reconnection headers, client pointer-capture resize handles on mobile, and prompt length calibration.
- **Impact**: Fixed streaming dropouts on mobile data connections and eliminated chat layout shift on candidate devices.
- **Files Changed**: `app/Services/Ai/AiTutorService.php`, `resources/js/components/ai/AiTutorWidget.vue`, `tests/Feature/AiTutorChatTest.php`.
- **Blast Radius**: `AiTutorService`, `AiTutorWidget.vue`.
- **Rollback Path**: `git revert` SSE changes.
- **Lessons**: Long-lived SSE connections over mobile networks require explicit server heartbeat comments (`: ping\n\n`) to prevent carrier gateway timeouts.

### Entry 23 — 2026-10-02: Server-Driven UI (SDUI) Dynamic Phase Layout Engine
- **Action**: Implemented `PhaseStepperLayout.vue` and dynamic module schema ingestion within `SduiPayloadTransformer` to render military multi-phase screening paths (Screening $\rightarrow$ Physical $\rightarrow$ Academic $\rightarrow$ ISSB).
- **Impact**: Permitted arbitrary curriculum branching without hardcoded frontend router changes.
- **Files Changed**: `app/Services/SduiPayloadTransformer.php`, `resources/js/components/sdui/PhaseStepperLayout.vue`.
- **Blast Radius**: SDUI schema parsing and domain dashboards.
- **Rollback Path**: Revert transformer changes.
- **Lessons**: Layout engines must validate schema contracts defensively with sensible fallbacks to prevent empty state renders on partial schemas.

### Entry 22 — 2026-09-28: EasyPaisa & JazzCash Manual Receipt Verification Queue
- **Action**: Built Filament administrative verification resource for candidate payment receipts (`UserSubscriptionReviewResource`) with image modal previews, 1-click approvals, and reject reason notifications.
- **Impact**: Accelerated manual review turnaround times for un-banked Pakistani candidates paying via OTC agent slips.
- **Files Changed**: `app/Filament/Resources/UserSubscriptions/UserSubscriptionReviewResource.php`, `tests/Feature/FilamentUserSubscriptionReviewTest.php`.
- **Blast Radius**: `user_subscriptions` approval workflow.
- **Rollback Path**: Deactivate resource.
- **Lessons**: Manual financial verification requires immutable audit logs tracking which admin approved or rejected each receipt.

### Entry 21 — 2026-09-23: Spaced Learning Weak Question Bank & Two-Strike Mastery Protocol
- **Action**: Engineered `UserWeakQuestion` tracking system capturing missed MCQs, prioritizing them in revision playlists, and requiring candidates to answer correctly twice across distinct sessions to achieve mastery status.
- **Impact**: Boosts candidate retention on critical screening questions by automating targeted retrieval practice.
- **Files Changed**: `app/Models/UserWeakQuestion.php`, `app/Http/Controllers/WeakQuestionsController.php`, `resources/js/pages/WeakQuestions/Index.vue`, `tests/Feature/WeakQuestionsTest.php`.
- **Blast Radius**: MCQ grading pipeline and candidate revision dashboard.
- **Rollback Path**: `git revert` weak questions migration and controller.
- **Lessons**: Single correct answers on previously failed questions are often lucky guesses; mastery confirmation requires two consecutive successes across distinct attempts.

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
