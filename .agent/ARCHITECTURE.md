# .agent/ARCHITECTURE.md — System Blueprint & Reverse-Engineered Map

> **Status**: COMPLETE ARCHITECTURAL SPECIFICATION  
> **Application**: Pak-All-Tests (Competitive Examination & Intelligence Testing Engine)  
> **Platform Core**: Laravel 13+ Monolith • Inertia.js / Vue 3 • PostgreSQL 16+ (Supabase) • Filament 5

---

## 1. High-Level System Architecture

Pak-All-Tests is a high-performance examination preparation and testing platform engineered for competitive civil and military services (PMA Long Course, ISSB, CSS, PPSC, MDCAT). It combines:
1. **Server-Driven UI (SDUI)**: Dynamic dashboard layouts synthesized from PostgreSQL domain hierarchies and rendered reactively in Vue 3.
2. **Interactive MCQ & Subjective Testing**: Real-time timed assessment players with instantaneous scoring.
3. **AI Socratic Exam Mentor**: In-browser floating mentor backed by `pgvector` semantic knowledge search, strict tool calling limits, and Server-Sent Events (SSE).
4. **Subscription & Proof-of-Payment Gate**: Manual receipt verification workflow (Easypaisa, JazzCash, Bank Wire) tied to domain access gating.

```
[ Candidate Browser ]
        │
        ▼ (HTTPS / Cloudflare / Azure)
[ Nginx 1.28.0 (client_max_body_size: 64M) ]
        │
        ▼ (FastCGI: 127.0.0.1:9000)
[ PHP 8.4-FPM (upload_max_filesize: 64M) ]
        │
        ├──▶ [ Inertia.js + Vue 3 Frontend ] (SSR / SPA with Reka UI + Tailwind 4)
        │         ├── SduiRenderer.vue (Dynamic Layout Engine)
        │         ├── McqPlayer.vue & PdfViewer.vue
        │         └── AiTutorWidget.vue (SSE Streaming)
        │
        ├──▶ [ Filament Admin Panel (v5.0) ] (/admin)
        │         ├── Domains, Content Modules, Domain Flows, Question Banks
        │         └── User Subscriptions (Receipt Verification)
        │
        └──▶ [ Core Service Layer ]
                  ├── SubscriptionManager (Domain Access Gating)
                  ├── SduiPayloadTransformer & SduiSchemaValidator
                  ├── AiTutorService & AiCompletionService
                  └── KnowledgeVectorizationService (pgvector)
                           │
                           ▼
              [ PostgreSQL 16+ / Supabase ]
                ├── Extensions: ltree, vector(1536)
                └── Tables: domains, content_modules, question_bank,
                            user_subscriptions, domain_chat_messages,
                            domain_knowledge_embeddings
```

---

## 2. Directory Structure & Namespace Mapping

```
app/
├── Actions/                      # Atomic single-responsibility action classes
│   ├── Fortify/                  # CreateNewUser, ResetUserPassword, UpdateUserProfileInformation
│   └── Teams/                    # CreateTeam, DeleteTeam, UpdateTeamName
├── Auth/
│   └── CachingUserProvider.php   # Redis-backed User caching (bypasses remote DB round-trip)
├── Concerns/                     # Reusable Eloquent model & validation traits
│   ├── CachesTableData.php       # Automatic cache invalidation on table mutations
│   ├── HasTeams.php              # Multi-tenant team relationships & roles
│   ├── PasswordValidationRules.php
│   └── ProfileValidationRules.php
├── Console/Commands/
│   └── VectorizeKnowledgeCommand.php  # CLI Artisan tool to vectorize domain notes/questions
├── Data/                         # Spatie/DTO data transfer objects
│   ├── TeamPermissions.php
│   └── UserTeam.php
├── Enums/
│   ├── TeamPermission.php
│   └── TeamRole.php              # Owner, Admin, Member
├── Filament/                     # Filament Admin Panel v5
│   ├── Pages/
│   │   └── SduiSchemaGuide.php   # Internal documentation for SDUI schema nodes
│   ├── Resources/
│   │   ├── ContentModules/       # ModuleResource, Form, Table, HandlesModuleFiles trait
│   │   ├── DomainFlows/          # DomainFlowResource, Form, Table
│   │   ├── Domains/              # DomainResource, Form, Table
│   │   ├── QuestionBanks/        # QuestionBankResource, Form, Table
│   │   └── UserSubscriptions/    # UserSubscriptionResource, Verification Form & Table
├── Http/
│   ├── Controllers/
│   │   ├── AiTutorController.php         # AI Socratic mentor SSE endpoint & history
│   │   ├── ContentModuleController.php   # JSON API to fetch module & eager-load questions
│   │   ├── DashboardController.php       # Team dashboard view
│   │   ├── DomainDashboardController.php # Candidate portal SDUI compiler & renderer
│   │   ├── McqExecutionController.php    # MCQ answer evaluation & score calculation
│   │   ├── SubscriptionReceiptController.php # Proof-of-payment upload handler
│   │   ├── Settings/                     # ProfileController, SecurityController
│   │   └── Teams/                        # TeamController, TeamMemberController, TeamInvitationController
│   ├── Middleware/
│   │   ├── EnsureTeamMembership.php      # Team access guard
│   │   ├── HandleAppearance.php          # Dark/light theme cookie reader
│   │   ├── HandleInertiaRequests.php      # Global Inertia shared props (auth, teams, flash)
│   │   └── SetTeamUrlDefaults.php        # Default team slug injector for routes
│   ├── Requests/
│   │   ├── Settings/ProfileUpdateRequest.php
│   │   └── Teams/ (InviteTeamMemberRequest, UpdateTeamMemberRequest, UpdateTeamNameRequest)
│   └── Responses/                        # Fortify custom authentication responses
│       ├── LoginResponse.php             # Redirects candidate to /#exams
│       ├── RegisterResponse.php          # Redirects candidate to /#exams
│       ├── PasskeyLoginResponse.php
│       ├── TwoFactorLoginResponse.php
│       ├── VerifyEmailResponse.php
│       └── Concerns/RedirectsToCurrentTeam.php # Safe team-agnostic URL resolver
├── Actions/
│   └── QuestionBank/
│       └── StoreGeneratedQuestionsAction.php # Atomic bulk question persistence & multi-domain linking
├── Jobs/
│   └── VectorizeDomainKnowledgeJob.php   # Queued vectorization for pgvector embeddings
├── Models/                               # Eloquent Models (detailed in section 3)
├── Policies/
│   └── TeamPolicy.php
├── Providers/
│   ├── AppServiceProvider.php            # Performance logging, Redis cache provider, URL forceScheme
│   ├── FortifyServiceProvider.php        # Rate limiting, Auth views, Actions configuration
│   └── Filament/AdminPanelProvider.php   # Filament admin configuration & themes
├── Rules/
│   └── TeamName.php
└── Services/
    ├── SubscriptionManager.php           # Access gating logic based on user_subscriptions
    ├── SduiPayloadTransformer.php        # Transforms domain hierarchy into SDUI node tree
    ├── SduiSchemaValidator.php           # Strict validator for SDUI component schemas
    └── Ai/
        ├── AiCompletionService.php       # Multi-driver LLM caller (OpenAI / Gemini / Fake)
        ├── AiTutorService.php            # Socratic mentor execution loop & tool routing
        ├── EmbeddingService.php          # Vector(1536) embedding client
        ├── KnowledgeVectorizationService.php # RAG indexing pipeline for questions & notes
        └── McqGeneratorService.php       # Pure-PHP parser & batched AI distractor engine
```


---

## 3. Database Schema & Eloquent Models

### Database Extensions
- **`ltree`**: PostgreSQL extension for infinite hierarchical path structures (used in category / syllabus trees).
- **`vector` (`pgvector`)**: Stores 1536-dimensional HNSW cosine embeddings for semantic search.

### Core Models & Relationships

| Model | Table | Key Attributes / Columns | Relationships |
| :--- | :--- | :--- | :--- |
| **`User`** | `users` | `name`, `email`, `password`, `current_team_id` | `subscriptions()`, `subscribedDomains()`, `teams()`, `ownedTeams()` |
| **`Domain`** | `domains` | `name`, `slug`, `code`, `is_active`, `path` (`ltree`), `description` | `contentModules()`, `flows()`, `knowledgeEmbeddings()` |
| **`ContentModule`**| `content_modules`| `domain_id`, `title`, `slug`, `module_type` (preparation/mock_test/pdf_repo), `sort_order`, `content_meta` (`jsonb`) | `belongsTo(Domain)`, `belongsToMany(QuestionBank)` |
| **`QuestionBank`** | `question_bank` | `code`, `question_text`, `question_type` (mcq/subjective), `difficulty_level`, `payload` (`jsonb`), `is_active` | `belongsToMany(ContentModule)` via `module_questions` |
| **`UserSubscription`**| `user_subscriptions`| `user_id`, `domain_id`, `status` (active/pending/rejected/expired), `payment_method`, `receipt_url`, `starts_at`, `expires_at` | `belongsTo(User)`, `belongsTo(Domain)` |
| **`DomainFlow`** | `domain_flows` | `domain_id`, `title`, `slug`, `sort_order`, `prerequisites` (`jsonb`), `is_active` | `belongsTo(Domain)` |
| **`DomainChatMessage`**| `domain_chat_messages`| `domain_id`, `user_id`, `role` (user/assistant), `content`, `context_metadata` (`jsonb`) | `belongsTo(Domain)`, `belongsTo(User)` |
| **`DomainKnowledgeEmbedding`**| `domain_knowledge_embeddings`| `domain_id`, `content_module_id`, `title`, `content`, `embedding` (`vector(1536)`), `metadata` (`jsonb`) | `belongsTo(Domain)`, `belongsTo(ContentModule)` |
| **`Team`** | `teams` | `name`, `slug`, `is_personal` | `members()`, `invitations()`, `owner()` |
| **`Membership`** | `team_members` | `team_id`, `user_id`, `role` (owner/admin/member) | `belongsTo(Team)`, `belongsTo(User)` |

---

## 4. Frontend Architecture (Vue 3 + Inertia + SDUI)

1. **Server-Driven UI (SDUI) Engine**:
   - `DomainDashboardController` calls `SduiPayloadTransformer` to compile a complete tree of UI nodes.
   - [resources/js/components/sdui/SduiRenderer.vue](file:///resources/js/components/sdui/SduiRenderer.vue) recursively traverses the node tree and resolves components via [componentRegistry.js](file:///resources/js/components/sdui/componentRegistry.js).
   - Registered layouts: `VerticalStack`, `TabbedLayout`, `PhaseStepperLayout`, `SubjectGrid`, `ProgressCard`, `ResourceList`, `EmptyStateLayout`.
2. **Assessment Execution**:
   - `McqPlayer.vue`: Interactive timed question player with immediate feedback, pattern breakdown, and answer submission to `/api/modules/{module}/submit`.
   - `PdfViewer.vue`: Fullscreen PDF viewer supporting `/storage/...` documents and Google Drive previews.
   - `SubjectiveUpload.vue`: Subjective word-association and situation-reaction input with real-time character counters.
3. **AI Socratic Tutor Widget**:
   - [resources/js/components/ai/AiTutorWidget.vue](file:///resources/js/components/ai/AiTutorWidget.vue): Floating action trigger + slide-over drawer.
   - Streams responses via Server-Sent Events (`POST /api/domains/{domainSlug}/ai-tutor/ask`).
   - Renders clean Markdown and communicates with the Socratic hint engine.

---

## 5. Security, Gating & Environment Constraints

1. **Subscription Gating (`SubscriptionManager`)**:
   - Access to `/d/{domainSlug}` and `/api/modules/{module}` is gated strictly by `SubscriptionManager::hasActiveAccess($user, $domain)`.
   - If unsubscribed, the user is redirected to `/d/{domainSlug}` which renders `Subscription/PendingOrInactive.vue`.
2. **Reverse Proxy & SSL**:
   - Configured with `$middleware->trustProxies(at: '*')` in `bootstrap/app.php` and `URL::forceScheme('https')` in `AppServiceProvider.php` for Azure/Cloudflare edge SSL termination.
3. **File Upload Limits**:
   - Synchronized across Nginx (`client_max_body_size 64M;` in `default`) and PHP-FPM (`upload_max_filesize = 64M`, `post_max_size = 64M` in `uploads.ini`).

---

## 6. Sub-Architectural Rule Index (`.agents/rules/`)

This architecture is governed by 13 detailed sub-architectural specifications located in [`.agents/rules/`](file:///.agents/rules/):

| Rule File | Scope & Mandatory Standards |
| :--- | :--- |
| [`.agents/rules/senior_architect_principles.md`](file:///.agents/rules/senior_architect_principles.md) | Radical simplicity (YAGNI), pure JavaScript in Vue (`<script setup>`), zero assumptions, sub-second execution. |
| [`.agents/rules/ui_design_guidelines.md`](file:///.agents/rules/ui_design_guidelines.md) | Academic Rigor & Military Precision tone, Anti-AI visual laws, mathematical nested radius formula ($R_{\text{inner}} = \max(0, R_{\text{outer}} - P)$). |
| [`.agents/rules/agent_architecture_guidelines.md`](file:///.agents/rules/agent_architecture_guidelines.md) | Socratic AI Tutor SSE streaming, intent routing, tool calling limits (max 2 rounds), zero correct answer leakage, PMA/ISSB/CSS/MDCAT institutional knowledge. |
| [`.agents/rules/user_subscriptions.md`](file:///.agents/rules/user_subscriptions.md) | EasyPaisa / JazzCash / Bank Transfer payment state machine (`pending` $\rightarrow$ `active`/`rejected`/`expired`), WhatsApp Cloud API notifications. |
| [`.agents/rules/domains.md`](file:///.agents/rules/domains.md) | PostgreSQL `ltree` path format, GiST index, sub-millisecond tree updates, Redis caching hooks. |
| [`.agents/rules/domain_flows.md`](file:///.agents/rules/domain_flows.md) | Multi-phase exam workflows, prerequisites dependency evaluation, SDUI phase stepper synthesis. |
| [`.agents/rules/content_modules.md`](file:///.agents/rules/content_modules.md) | Content module polymorphic types (`mcq_test`, `pdf_resource`, `subjective_prompt`), resource linkage, file handling. |
| [`.agents/rules/question_bank.md`](file:///.agents/rules/question_bank.md) | Question schema, JSONB options, answer key security, pgvector semantic search embedding storage. |
| [`.agents/rules/database_performance.md`](file:///.agents/rules/database_performance.md) | Sub-millisecond queries, single round-trip query batching, indexing strategies, zero N+1. |
| [`.agents/rules/security_gating.md`](file:///.agents/rules/security_gating.md) | Controller-level `SubscriptionManager` gating, Fortify auth, tenant isolation, CSRF protection. |
| [`.agents/rules/testing_validation.md`](file:///.agents/rules/testing_validation.md) | Pest test suite execution, `DatabaseTransactions` (banning `migrate:fresh` in tests), zero dynamic properties. |
| [`.agents/rules/sdui_blueprints.md`](file:///.agents/rules/sdui_blueprints.md) | SDUI JSON schema definitions, component registry contracts, server-side layout compilation. |
| [`.agents/rules/mistakes_and_descriptions.md`](file:///.agents/rules/mistakes_and_descriptions.md) | Catalog of known engineering pitfalls and historical traps to avoid. |

