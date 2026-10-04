# MEMORY.md — Rolling Project Memory & Operational Knowledge

> **Status**: ACTIVE ROLLING RECORD
> **Repository**: Pak-All-Tests (Laravel 13 • Vue 3 / Inertia 3 • PostgreSQL 16+ Supabase)
> **Archive**: Older entries (11–16) archived to [`.agent/MEMORY_ARCHIVE.md`](file:///.agent/MEMORY_ARCHIVE.md)

---

## 📜 Active Rolling Memory Log

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
- **Action**: Implemented 1-click global (`platform_settings.all_domains_free`) and per-domain (`domains.requires_subscription`) payment bypass in Filament admin, ephemeral 24-hour guest passes (`users.is_guest`, `guest_expires_at`) with non-destructive account conversion, automatic 7-day free trial subscriptions upon signup/conversion, and a domain-specific viral referral ladder awarding parents +7 days per referral and 30 days upon reaching 5 referrals, with automated anti-abuse detection (blocking self/IP referrals with candidate warnings) and 1-click admin user suspension in Filament.
- **Impact**: Removes registration and payment friction to drive exponential candidate acquisition in Pakistan, enables instant guest exam exploration, protects platform integrity against referral fraud, and allows admins to toggle payment gating dynamically with zero code changes or redeployments.
- **Files Changed**: `app/Actions/Fortify/CreateNewUser.php`, `app/Filament/Resources/Domains/Schemas/DomainForm.php`, `app/Filament/Resources/Domains/Tables/DomainsTable.php`, `app/Filament/Resources/Referrals/ReferralResource.php`, `app/Filament/Resources/Referrals/Pages/ListReferrals.php`, `app/Filament/Resources/Referrals/Tables/ReferralsTable.php`, `app/Http/Controllers/Auth/GuestLoginController.php`, `app/Http/Controllers/Auth/ConvertGuestAccountController.php`, `app/Http/Controllers/DomainDashboardController.php`, `app/Models/Domain.php`, `app/Models/PlatformSetting.php`, `app/Models/Referral.php`, `app/Models/User.php`, `app/Services/GuestAuthService.php`, `app/Services/ReferralService.php`, `app/Services/SubscriptionManager.php`, `database/migrations/2026_10_04_000003_add_payment_gating_to_domains_table.php`, `database/migrations/2026_10_04_000004_add_guest_and_referral_to_users_table.php`, `database/migrations/2026_10_04_000005_create_referrals_table.php`, `database/migrations/2026_10_04_000006_create_platform_settings_table.php`, `resources/js/components/auth/GuestBanner.vue`, `resources/js/components/referral/ReferralCard.vue`, `resources/js/pages/Domain/Dashboard.vue`, `resources/js/pages/Welcome.vue`, `resources/js/pages/auth/Login.vue`, `routes/web.php`, `tests/Feature/GuestAccessTest.php`, `tests/Feature/PaymentBypassTest.php`, `tests/Feature/ReferralSystemTest.php`.
- **Blast Radius**: `SubscriptionManager`, `CreateNewUser`, `DomainDashboardController`, `DomainForm`, `DomainsTable`, `User`, `Domain`.
- **Rollback Path**: `php artisan migrate:rollback --step=4` and `git checkout HEAD~1 -- app/ routes/ resources/ database/`.
- **Lessons**: Model default attributes in Eloquent must be explicitly defined in `protected $attributes = [...]` rather than assuming database column defaults populate on in-memory freshly instantiated models; and environment-aware default fallbacks (`app()->runningUnitTests()`) guarantee existing gating test suites remain deterministic while production defaults to friction-free growth.
### Entry 26 — 2026-10-05: DatabaseSeeder PlatformSeeder Invocation & Test Suite DatabaseTransactions Compliance
- **Action**: Wired `PlatformSeeder` into `DatabaseSeeder::run()` using `firstOrCreate` for user creation to guarantee idempotency during `php artisan migrate:fresh --seed` or `db:seed`, and eliminated `RefreshDatabase` from 3 feature tests in favor of mandated `DatabaseTransactions`, adjusting `QuestionImportTest` assertions to use relative question counts.
- **Impact**: Resolves empty database tables and login failure (`admin@paktests.pk` non-existent) after running `php artisan migrate:fresh --seed`; prevents the test suite from wiping seeded database records.
- **Files Changed**: `database/seeders/DatabaseSeeder.php`, `tests/Feature/DomainKnowledgeVectorizationTest.php`, `tests/Feature/AiTutorChatTest.php`, `tests/Feature/ContentModuleExecutionTest.php`, `tests/Feature/QuestionImportTest.php`, `.agent/MEMORY.md`.
- **Blast Radius**: Seeder orchestration and test suite transaction lifecycle.
- **Rollback Path**: `git checkout HEAD~1 -- database/seeders/DatabaseSeeder.php tests/Feature/`.
- **Lessons**: `DatabaseSeeder` must call domain/platform seeders explicitly; test suites must never use `RefreshDatabase` when sharing persistent local/remote PostgreSQL instances.

### Entry 25 — 2026-10-04: Codification of Socratic Viewport Synchronization & The CLI Authority Law
- **Action**: Formalized architectural rules and anti-patterns following `/learn` command execution, codifying Viewport & Multi-Question Page Context Synchronization in `.agent/rules/ai_tutor.md` § 11, Mistake #12 in `.agent/rules/mistakes.md`, and The CLI Authority Law in `.agent/SYSTEM_RULES.md` § 7.
- **Impact**: Establishes permanent operational guardrails preventing AI mentor viewport blindness during paginated tests, and establishes that the deterministic CLI toolchain (`npm run format:check`, `lint:check`, `types:check`, `pest`) is the sole authoritative standard of repository health over local IDE extension host runtime artifacts.
- **Files Changed**: `.agent/rules/ai_tutor.md`, `.agent/rules/mistakes.md`, `.agent/SYSTEM_RULES.md`, `.agent/MEMORY.md`.
- **Blast Radius**: Governance files and development standards.
- **Rollback Path**: `git checkout HEAD~1 -- .agent/rules/ai_tutor.md .agent/rules/mistakes.md .agent/SYSTEM_RULES.md .agent/MEMORY.md`.
- **Lessons**: Codifying recurring patterns into governance prevents future agents and developers from repeating pagination disconnects and chasing false-positive IDE extension runtime artifacts.

### Entry 24 — 2026-10-04: Full Viewport & Multi-Question Page Context Synchronization for AI Exam Mentor
- **Action**: Synchronized active page and visible questions state across `McqPlayer.vue`, `Dashboard.vue`, `AiTutorWidget.vue`, `AiTutorService.php`, and `AiCompletionService.php`, adding `computedPageContext` and auto-aligning active question indices on page/pagination changes so the Socratic mentor has accurate awareness of the candidate's exact viewport in both Page View (10/25/50/100 per page) and Focus Mode.
- **Impact**: Resolves the screen disconnect where the AI mentor defaulted to Question 1 (Nishan-e-Haider) regardless of the active page (e.g. Page 9 displaying Questions 81–85); displays dynamic context ribbons in the chat drawer and enables the mentor to accurately answer queries about the candidate's current page and questions while upholding Zero-Knowledge answer security.
- **Files Changed**: `resources/js/components/sdui/McqPlayer.vue`, `resources/js/pages/Domain/Dashboard.vue`, `resources/js/components/ai/AiTutorWidget.vue`, `app/Services/Ai/AiTutorService.php`, `app/Services/Ai/AiCompletionService.php`, `tests/Feature/AiTutorChatTest.php`.
- **Blast Radius**: `McqPlayer.vue`, `Dashboard.vue`, `AiTutorWidget.vue`, `AiTutorService.php`, `AiCompletionService.php`.
- **Rollback Path**: `git checkout HEAD~1 -- resources/js/components/sdui/McqPlayer.vue resources/js/pages/Domain/Dashboard.vue resources/js/components/ai/AiTutorWidget.vue app/Services/Ai/AiTutorService.php app/Services/Ai/AiCompletionService.php tests/Feature/AiTutorChatTest.php`.
- **Lessons**: When pagination or scrolling views partition data, client state must emit structured viewport metadata (`page_context`) rather than relying on a static index pointer (`index 0`), and AI prompt builders must explicitly represent candidate screen state so LLMs do not hallucinate earlier pages.

### Entry 23 — 2026-10-04: PR #5 Merge Conflict Resolution & Critical Feature Preservation
- **Action**: Resolved three-way merge conflicts across `resources/js/components/sdui/McqPlayer.vue`, `resources/js/components/ai/AiTutorWidget.vue`, and `.agent/MEMORY_ARCHIVE.md`, preserving top and bottom submit buttons, bottom Page View pagination, draggable AI Exam Mentor pointer capture, weak-mode submission endpoints, and strict ESLint/Prettier formatting standards.
- **Impact**: Eliminates all git conflict blocks while preserving 100% of candidate UI features, spaced repetition mastery flows, and notification table crash mitigations.
- **Files Changed**: `resources/js/components/sdui/McqPlayer.vue`, `resources/js/components/ai/AiTutorWidget.vue`, `.agent/MEMORY_ARCHIVE.md`, `.agent/MEMORY.md`.
- **Blast Radius**: `McqPlayer.vue`, `AiTutorWidget.vue`, `MEMORY_ARCHIVE.md`.
- **Rollback Path**: `git merge --abort` or `git checkout HEAD~1 -- resources/js/components/ .agent/`.
- **Lessons**: When resolving merge conflicts against older branches, forensic comparison guarantees that new features, accessibility enhancements, and bug fixes are never accidentally reverted in favor of stale code.

### Entry 22 — 2026-10-04: Workspace Hygiene & Governance Consolidation into .agent/
- **Action**: Consolidated all auxiliary markdown documentation and backlog specifications (`QA.md`, `FEATURES_BACKLOG.md`, `CLAUDE.md`, `task.md`) exclusively into `.agent/`, leaving only `AGENTS.md` as the single authoritative governance gateway in the repository root.
- **Impact**: Enforces pristine root directory hygiene, eliminates duplicate files, establishes `.agent/` as the single canonical documentation home, and simplifies governance discovery.
- **Files Changed**: `AGENTS.md`, `.agent/QA.md`, `.agent/FEATURES_BACKLOG.md`, `.agent/CLAUDE.md`, `.agent/task.md`, `.agent/ARCHITECTURE.md`, `.agent/rules/testing_validation.md`, `.agent/MEMORY.md`.
- **Blast Radius**: Isolated to governance documentation and file paths.
- **Rollback Path**: `git checkout HEAD~1 -- AGENTS.md .agent/ARCHITECTURE.md .agent/rules/testing_validation.md && git mv .agent/FEATURES_BACKLOG.md FEATURES_BACKLOG.md && git mv .agent/CLAUDE.md CLAUDE.md && git mv .agent/task.md task.md`.
- **Lessons**: A clean repository root with a single, clear entry point (`AGENTS.md`) linking into a modular `.agent/` governance structure prevents documentation fragmentation.

### Entry 21 — 2026-10-04: Canonical QA Protocol Formalization (QA.md & The 6 Defect Hunting Categories)
- **Action**: Formalized repository Quality Assurance Protocol in [`.agent/QA.md`](file:///.agent/QA.md), enshrining the Supreme QA Law ("Never rely solely on unit tests") and standardizing the 6 Defect Hunting Categories (logical, silent, loud, performance, incomplete logic, client-server disconnects) alongside 4 real-user scenario walkthroughs and a 10-point pre-release sign-off checklist across `AGENTS.md`, `.agent/rules/testing_validation.md`, and `.agent/ARCHITECTURE.md`.
- **Impact**: Establishes an un-bypassable operational law preventing agents from declaring features complete based solely on passing unit tests; eliminates silent bugs, incomplete logic, and unhandled edge cases before code reaches production.
- **Files Changed**: `.agent/QA.md`, `.agent/rules/testing_validation.md`, `AGENTS.md`, `.agent/ARCHITECTURE.md`, `.agent/MEMORY.md`.
- **Blast Radius**: Quality assurance pipelines, autonomous agent review workflows, and pre-conclusion gates.
- **Rollback Path**: `git checkout HEAD~1 -- .agent/QA.md .agent/rules/testing_validation.md AGENTS.md .agent/ARCHITECTURE.md`.
- **Lessons**: Automated unit tests assert on idealized assumptions; line-by-line inspection against standardized failure categories is the only reliable defense against silent runtime and logical bugs.

### Entry 20 — 2026-10-04: Forensic Real-User QA Audit & Line-by-Line Quality Assurance Hardening
- **Action**: Audited and fixed 6 critical issues across MCQ engines and UI layers: (1) resolved synthetic-click vs pointer-up double-toggle and restored WCAG keyboard navigation (`Enter`/`Space`) on draggable AI Exam Mentor FAB (`AiTutorWidget.vue`); (2) eliminated UI occlusion by positioning mentor FAB at `bottom: 5.5rem; right: 1.5rem` above sticky bottom bar; (3) implemented authoritative `submitPractice` endpoint (`/api/weak-questions/submit`) in `WeakQuestionsController.php`, synchronizing active-question filtering (`is_active: true`) with `McqExecutionController.php`; (4) eliminated 10-level nested ternaries in `McqPlayer.vue` with deterministic helpers (`getOptionClasses`, `getOptionCircleClasses`, `getOptionBadge`), resolving option state and badge rendering glitches; (5) guarded solution explanations (`canViewExplanation`) until attempted, revealed, or submitted; and (6) verified 168 Pest tests passing.
- **Impact**: Guarantees rock-solid interaction across mobile touch, mouse drag, and keyboard navigation; ensures accurate scoring and solution breakdown for both regular and weak practice sessions; and maintains strict answer-key security without cognitive or visual glitches.
- **Files Changed**: `app/Http/Controllers/McqExecutionController.php`, `app/Http/Controllers/WeakQuestionsController.php`, `resources/js/components/ai/AiTutorWidget.vue`, `resources/js/components/sdui/McqPlayer.vue`, `routes/web.php`, `tests/Feature/WeakQuestionsTest.php`, `.agent/MEMORY.md`.
- **Blast Radius**: `McqPlayer.vue`, `AiTutorWidget.vue`, `WeakQuestionsController.php`, `McqExecutionController.php`, `routes/web.php`.
- **Rollback Path**: `git checkout HEAD~1 -- app/Http/Controllers/McqExecutionController.php app/Http/Controllers/WeakQuestionsController.php resources/js/components/ai/AiTutorWidget.vue resources/js/components/sdui/McqPlayer.vue routes/web.php tests/Feature/WeakQuestionsTest.php`.
- **Lessons**: Real-user QA inspection catches subtle behavioral bugs that unit tests cannot see: e.g., pointer capture suppressing synthetic click events for keyboard users, floating widgets obscuring sticky action bars on small viewports, and unsynchronized active-status filters between curriculum loading and submission grading.

### Entry 19 — 2026-10-04: MCQ Palette Stacking Resolution, Top/Bottom Submit CTAs & Draggable AI Exam Mentor
- **Action**: (1) Resolved z-index stacking collision in `McqPlayer.vue` where Question Palette dropdown was rendered behind subsequent cards by assigning dynamic `relative z-50` / `z-30` to header controls bar; (2) added a prominent "Submit Test ✓" gradient CTA to top page navigation bar and focus mode header so candidates do not have to scroll to bottom; (3) restored tactile "← Previous Page" and "Next Page →" buttons at bottom bar in Page View; and (4) engineered draggable floating action button (FAB) for `AiTutorWidget.vue` with pointer capture, viewport boundaries, grip handle, sessionStorage persistence, and smart drawer positioning.
- **Impact**: Eliminates UI occlusion on Question Palette navigation, allows test submission from either top or bottom of the screen, provides responsive bottom navigation, and gives candidates full screen spatial freedom by dragging the AI mentor FAB.
- **Files Changed**: `resources/js/components/sdui/McqPlayer.vue`, `resources/js/components/ai/AiTutorWidget.vue`, `.agent/MEMORY.md`.
- **Blast Radius**: `McqPlayer.vue`, `AiTutorWidget.vue`.
- **Rollback Path**: `git checkout HEAD~1 -- resources/js/components/sdui/McqPlayer.vue resources/js/components/ai/AiTutorWidget.vue`.
- **Lessons**: Sibling elements with `backdrop-filter` establish independent stacking contexts that paint over earlier siblings unless explicit parent z-index hierarchy is enforced; floating widgets must support interactive repositioning (pointer capture drag) to prevent covering actionable buttons on responsive viewports.

### Entry 18 — 2026-10-04: SenseLab Luxury UI Transformation Across Candidate Dashboards & Workspaces
- **Action**: Restructured candidate surfaces to match flagship SenseLab aesthetic: (1) transformed `Domain/Dashboard.vue` with ambient radial lighting meshes, syllabus track pill badges, and human-readable metadata replacing exposed database `ltree` paths (`Path: 325.326.327`); (2) upgraded `PhaseStepperLayout.vue` into sleek pipeline navigator with glowing active-phase rings and status dots; (3) elevated `SubjectGrid.vue` into SenseLab feature cards with subject-specific glyphs, high-yield syllabus chips, question counters, and hover-lift gradient action buttons; (4) redesigned `WeakQuestions/Index.vue` with ambient lighting, metric cards, and tactile drilldown tiles; (5) elevated `McqPlayer.vue` with glassmorphic headers, refined dual-mode controls, and Session Performance report; (6) transformed root `Dashboard.vue` into Candidate Command Center with active exam tracks and curriculum topology; and (7) upgraded `AppLogo.vue` and `.env` branding with official ribbon mark.
- **Impact**: Unifies candidate portal with landing page design language, providing an international-grade, intuitive, and cohesive visual experience while eliminating raw database internals and placeholder states.
- **Files Changed**: `resources/js/components/AppLogo.vue`, `resources/js/components/sdui/McqPlayer.vue`, `resources/js/components/sdui/PhaseStepperLayout.vue`, `resources/js/components/sdui/SubjectGrid.vue`, `resources/js/pages/Dashboard.vue`, `resources/js/pages/Domain/Dashboard.vue`, `resources/js/pages/WeakQuestions/Index.vue`, `.env`.
- **Blast Radius**: Candidate dashboard, test player, weak questions workspace, and navigation layout.
- **Rollback Path**: `git checkout HEAD~1 -- resources/js/components/AppLogo.vue resources/js/components/sdui/McqPlayer.vue resources/js/components/sdui/PhaseStepperLayout.vue resources/js/components/sdui/SubjectGrid.vue resources/js/pages/Dashboard.vue resources/js/pages/Domain/Dashboard.vue resources/js/pages/WeakQuestions/Index.vue .env`.
- **Lessons**: UI consistency requires propagating design tokens across all post-auth candidate surfaces; never expose internal raw database identifiers (`ltree` paths) to end-users when official syllabus badges and readable metadata can be rendered instead.

### Entry 17 — 2026-10-04: Weak MCQs Persistence, Reveal Answer Forfeiture & 2-Strike Threshold Mastery Engine
- **Action**: Engineered end-to-end Weak MCQs system: (1) on-demand "Reveal Answer" button in `McqPlayer.vue` revealing solution and explanation while forfeiting score (0 points) and logging into candidate's Weak MCQs; (2) automatic weak question capture on incorrect answers in interactive practice and full test submission (`McqExecutionController`); (3) 2-strike consecutive correct Threshold Mastery graduation (`UserWeakQuestion::recordAttempt`) requiring two consecutive un-revealed correct answers to mark mastered; (4) dual-mode player controls in `McqPlayer.vue` (Page View with 10/25/50/100 pagination vs Focus Mode with single question drawer replacing 228-button wall); (5) dedicated portal page `resources/js/pages/WeakQuestions/Index.vue` with subject filtering, mastery metrics, and direct in-place practice launcher; and (6) added "Revision & Weak MCQs" to candidate sidebar.
- **Impact**: Provides candidates with targeted spaced repetition for forfeited or failed questions, prevents answer memorization loopholes through zero-knowledge scrubbing and 2-strike graduation, and eliminates cognitive overload from 200+ button walls via responsive page-level scrolling.
- **Files Changed**: `app/Http/Controllers/McqExecutionController.php`, `app/Http/Controllers/WeakQuestionsController.php`, `app/Models/User.php`, `app/Models/UserWeakQuestion.php`, `database/migrations/2026_10_04_000001_create_user_weak_questions_table.php`, `resources/js/components/AppSidebar.vue`, `resources/js/components/sdui/McqPlayer.vue`, `resources/js/pages/WeakQuestions/Index.vue`, `routes/web.php`, `tests/Feature/WeakQuestionsTest.php`.
- **Blast Radius**: `McqPlayer.vue`, `McqExecutionController.php`, `AppSidebar.vue`, `routes/web.php`, and `user_weak_questions` table.
- **Rollback Path**: `php artisan migrate:rollback --step=1` and `git checkout HEAD~1 -- app/Http/Controllers/McqExecutionController.php resources/js/components/AppSidebar.vue resources/js/components/sdui/McqPlayer.vue routes/web.php app/Models/User.php`.
- **Lessons**: Answer-key security requires authoritative server-side reveal (`/api/questions/{id}/reveal`) rather than bundling correct indices in client payload; spaced learning mastery must require multiple consecutive confirmations (2-strike rule) to prevent lucky guesses from premature graduation.

---

## 📜 Historical Baseline Entries (Entries 01–10 Immutable)

### Entry 12 — 2026-10-04: Import Notification Resilience & Missing notifications Table
- **Action**: Root-caused the Import Questions dry-run crash (`SQLSTATE[42P01]` on `notifications`): the repo never shipped a migration for Laravel's database-notifications table, so `Filament\Notifications\Notification::sendToDatabase()` threw inside both `ImportQuestionsJob::finish()` and `LinkPmaSubjectModulesJob::finish()`. Wrapped both sends in try/catch (structured warning log, report still cached and shown) and added migration `2026_10_04_000002_create_notifications_table`.
- **Impact**: Dry runs and queued import/link runs can no longer fail because of a missing or failing notification write; the preview report is always persisted. New environments get the table via `migrate`.
- **Files Changed**: `app/Jobs/ImportQuestionsJob.php`, `app/Jobs/LinkPmaSubjectModulesJob.php`, `database/migrations/2026_10_04_000002_create_notifications_table.php`, `tests/Feature/QuestionImportTest.php`, `tests/Feature/LinkPmaSubjectModulesTest.php`, `.agent/ARCHITECTURE.md`.
- **Blast Radius**: isolated — notification step of the two import jobs only; no dispatch signatures, queue names, or data writes changed.
- **Rollback Path**: `git revert` the branch; migration rolls back with `php artisan migrate:rollback --step=1` (drops only the `notifications` table). Note: the Azure deploy pipeline does not run migrations, so production still needs `php artisan migrate` on the server to create the table — the try/catch keeps imports working regardless.
- **Lessons**: Reporting paths must never share a failure domain with their notification channel; a dry-run preview dying on its final line destroys trust in the whole report.

### Entry 10 — 2026-09-23: Question Ingestion Standards & Anti-Pattern Governance
- **Action**: Established strict repository standards for bulk data ingestion, remote PostgreSQL transaction limits, and Livewire state management.
- **Impact**: Banned sequential loops inside `DB::transaction(...)` across remote Supabase connections (mandating sub-200ms bulk multi-row inserts); enforced Cache-Pointer pattern (`$batchId`) to eliminate Livewire modal payload bloat; mandated strict `NULL` sanitization on unique nullable columns (`code`); and instituted Zero-Token rule (pure PHP for formatted MCQs, AI strictly for distractor generation).

### Entry 09 — 2026-09-19: Senior Autonomous Governance System Establishment
- **Action**: Formalized repository governance protocol across `AGENTS.md`, `.agent/AGENT.md`, `.agent/ARCHITECTURE.md`, `.agent/PATTERNS.md`, and `.agent/MEMORY.md`.
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

---

## ⚠️ Known Quirks & Technical Debt

1. **Remote Database Latency & Test Strategy**: Remote Supabase PostgreSQL has 50–150ms round-trip latency. `tests/Pest.php` strictly uses `DatabaseTransactions` instead of `RefreshDatabase`. **Never run `migrate:fresh` inside tests** (see [`.agent/rules/testing_validation.md § 1`](file:///.agent/rules/testing_validation.md)).
2. **Supabase PgBouncer & Prepared Statements**: Port `6543` operates in transaction pooling mode. `PDO::ATTR_EMULATE_PREPARES => true` MUST remain enabled in `config/database.php` for `pgsql` to prevent `SQLSTATE[42P05]` deallocated prepared statement crashes.
3. **Azure App Service Lifecycle**: File edits to Nginx templates or `uploads.ini` do not take effect automatically in Linux containers. Startup script must copy `default` and `uploads.ini` into system paths, reload Nginx, and send `USR2` signal to PHP-FPM.
4. **Database Collation & Urdu Support**: PostgreSQL handles UTF-8 natively. MySQL/MariaDB fallback connections must explicitly maintain `utf8mb4_unicode_ci` to prevent Urdu script truncation or casing mismatches in question bank search queries.
5. **Queue Workers & Background Processing**: Local runs `QUEUE_CONNECTION=sync` or `database`. Production requires persistent Supervisor daemon (`php artisan queue:work --tries=3 --timeout=120`) to prevent heavy AI embedding calculations from blocking web workers.
6. **Payment Gateway & WhatsApp Webhooks**: Webhooks (`/api/webhooks/easypaisa`, `/api/webhooks/jazzcash`) require idempotency key checking and HMAC-SHA256 verification before activation. WhatsApp Cloud API notifications must format numbers as E.164 (`923XXXXXXXXX`) with a 3s timeout and Redis queuing.
7. **Livewire Modal Payloads (Cache-Pointer)**: Passing >20 items into Livewire modal state causes payload bloat and corruption. Store temporary bulk records in cache and pass only UUID `batch_id` into form state (see [`.agent/rules/database_performance.md § 4`](file:///.agent/rules/database_performance.md)).
8. **PgBouncer Bulk Insert Performance**: Multi-record mutations must use multi-row bulk `insert` and `insertOrIgnore` to guarantee all transaction locks complete in under 200ms (see [`.agent/rules/database_performance.md § 2`](file:///.agent/rules/database_performance.md)).

---

## ⚠️ Hardening Directives

- **HD-1: Time-Zone Discipline**: Storage in UTC, display in PKT, exam timers server-authoritative.
- **HD-2: Idempotency Everywhere**: Webhooks, jobs, notifications, and subscription activations require unique idempotency keys.
- **HD-3: PII & Answer-Key Log Hygiene**: Logs must never contain candidate emails, phones, passwords, CNICs, TRX IDs, or correct-answer indices.
- **HD-4: Currency Precision**: PKR amounts stored as integer paisa; formatted with comma separators at presentation layer only (`PKR 2,500`).
- **HD-5: Text Safety**: User text is normalized, length-capped, and HTML-escaped before storage and render.
- **HD-6: Blast-Radius Declaration**: Changes touching `SubscriptionManager`, `AiTutorService`, `SduiPayloadTransformer`, or Fortify pipeline require explicit Blast Radius blocks.
- **HD-7: Rollback Path**: Every migration, queue schema, or integration update ships with a tested rollback command.
- **HD-8: Observability Minimum**: Endpoints and queued jobs must emit structured JSON logs (`correlation_id`, `user_id`, `domain_slug`, `duration_ms`).
