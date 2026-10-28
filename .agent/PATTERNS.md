# .agent/PATTERNS.md — Engineering Patterns, Mandates, Conventions & Domain Intelligence

> **Status**: MANDATORY ARCHITECTURAL CONTRACT — ALWAYS ACTIVE  
> **Applicability**: All backend feature development, frontend components, database queries, and automated testing  
> **Workspace**: Pak-All-Tests (Laravel 13 • Inertia 3 • Vue 3.5 • PostgreSQL 16+ Supabase)

---

# 🏛️ Tier 1: MANDATES (Non-Negotiable Laws)

## 1. The Legacy Baseline Rule
- **Scope Discipline**: Do **NOT** attempt to fix pre-existing lint errors, formatting warnings, or architectural flaws in files you did not touch in your current session.
- **Zero Scope Creep**: Keep diffs surgically focused on the task at hand. Modifying untouched files to "clean them up" introduces unnecessary blast radius and risks CI breakage.
- **Test Integrity**: If an existing legacy test fails, determine whether your changes broke the contract. **NEVER modify or weaken an existing test to cheat or force a green build.** Fix the regression in your implementation code.

---

## 2. Chaos Testing Protocol & Pest Testing Standards
- **Adversarial Assertion Mandate**: Do **NOT** write simple, superficial "happy path" tests. Unit tests that only test idealized mocks conceal catastrophic production bugs.
- **Simulate Real-World Chaos**: Tests must actively probe:
  - Malformed and edge-case inputs (empty arrays, boundary numbers, negative integers, script tags).
  - Race conditions and duplicate triggers (replay attacks on webhooks, concurrent exam submissions, double-clicks).
  - Unauthorized traversal (BOLA/IDOR attacks where User A attempts to access User B's domain or team).
  - State machine boundary violations (submitting answers after test expiry, re-activating expired passes).
- **DatabaseTransactions Mandate**: All tests interacting with the database must use `Illuminate\Foundation\Testing\DatabaseTransactions`. Never use `RefreshDatabase` or `migrate:fresh` (which resets the remote Supabase database and drops extensions).
- **Zero Dynamic Properties in Tests**: In Pest tests, never assign dynamic `$this->variable` properties that trigger PHP 8.2+ deprecation notices. Use typed helper functions, closures, or class properties.

---

## 3. Staff Engineer PR Review & Forensic QA Protocol (The 6 Defect Hunting Categories)
Before marking any task complete or returning output to the user, conduct a strict, line-by-line **Staff Engineer PR Review** auditing every change against the **6 Defect Hunting Categories**:

### A. Logical Bugs & Edge-Case Traps
- **Falsy Zero Traps (`0 == false`)**: In JavaScript, `0`, `""`, and `null` are falsy. A check like `if (!module?.id)` silently aborts virtual test sessions where `module.id === 0` (e.g. Weak MCQs practice sets). A check like `if (!selectedOption)` treats option index `0` (Option A) as unselected. Always use explicit checks: `if (module?.id === undefined || module?.id === null)` and `if (selectedIndex !== null && selectedIndex !== undefined)`.
- **Off-By-One & Slice Boundaries**: Verify 0-indexed array vs 1-indexed UI conversions (`idx + 1` vs `currentQuestionIndex`). Check slice limits in pagination: `questions.slice(start, start + pageSize)`.
- **Inverted Precedence & Complex Ternary Operators**: Never chain more than 2 ternary operators in template expressions. Extract into computed properties or deterministic helper functions. Verify precedence between `??` (null/undefined only) and `||` (any falsy value).

### B. Silent Bugs (The Stealth Killers)
- **Silent Abortions & Unhandled Early Returns**: Never execute an early `return;` without resetting loading states (`isSubmitting = false`) or notifying the candidate.
- **Swallowed Exceptions**: Never write empty `catch (err) {}` blocks that swallow network or parsing failures. Every `catch` must display a user-facing error toast/banner and log structured context.
- **Loose PHP Type Juggling**: In PHP, string comparisons can juggle types: `(int) "malicious_string" === 0`. If option `0` is the correct answer, malicious strings score as correct. Always enforce strict typing: `if ($choice !== null && is_numeric($choice) && (int) $choice === (int) $correctIndex)`.
- **Answer-Key Scrubbing Assurance**: Anti-cheat question banks scrub `correct_option_index` from client payloads. Client scoring must never assume the answer key exists in client state prior to server grading.

### C. Loud Bugs (Runtime Crashes & HTTP 500s)
- **Unchecked Null Dereferencing**: Always use nullsafe operators (`$module->domain?->id`, `props.module?.content_meta?.duration_minutes`) or explicit `instanceof` checks.
- **Undefined Keys & Offsets**: Access array indices with `isset()` or null-coalescing (`??`).
- **Malformed External API Handling**: External endpoints (OpenAI, Gemini, EasyPaisa, JazzCash) can return HTML error pages (Azure 502, Cloudflare 504). Always verify `$response->successful()` before calling `$response->json()`.

### D. Incomplete Logic & Half-Baked Features
- **Missing Endpoints for Frontend Actions**: Never leave a frontend button dispatching to an unimplemented route.
- **Desynchronized Query Filters**: Ensure retrieval queries and grading queries share identical scopes (`where('is_active', true)`).
- **Missing State Resets**: Switching test modes (Page View vs Focus Mode) must reset `selectedAnswers`, `showExplanations`, `forfeitedQuestions`, and timers.
- **Dead Clicks**: Every interactive element must have `cursor-pointer`, distinct hover states, and active feedback (`active:scale-95`).

### E. Security, Authorization & Privacy Leaks
- **BOLA/IDOR Checks**: Verify server-side ownership of `domain_id`, `module_id`, and `user_id`.
- **Log Hygiene**: Never log passwords, tokens, CNICs, mobile numbers, transaction IDs, or MCQ correct answers.

### F. Scalability & Database Performance
- **Zero N+1 Queries**: Eager-load relations (`with([...])`) on collections.
- **Sub-200ms Transaction Law**: Database locks must complete in $<200\text{ms}$.

---

## 4. The 11 Historical Architectural Scars & RCA Preventions

These 11 real-world engineering mistakes are preserved as permanent scar tissue to ensure they are never repeated:

1. **Static Infinite Recursion in Form Schemas (PHP Memory Exhaustion)**:
   - *Cause*: Calling a form schema method recursively inside a static array definition (`Repeater::make()->schema([...static::contentNodeFields()])`).
   - *Fix*: Define explicit, non-recursive schemas for each hierarchy level (`phaseFields`, `tabFields`, `contentItemFields`).
2. **Incorrect Filament Package Namespace Imports**:
   - *Cause*: Importing from `Filament\Forms\Components` instead of modern modular namespaces.
   - *Fix*: Use `Filament\Schemas\Components\Utilities\Get`, `Filament\Schemas\Components\Section`, and `Filament\Schemas\Components\Grid`.
3. **Livewire Modal Payload Bloat (`CorruptComponentPayloadException`)**:
   - *Cause*: Hydrating collections with $>20$ models into Livewire modal properties.
   - *Fix*: Cache-Pointer Pattern: Write bulk dataset to Redis under UUID key (`import_batch_{uuid}`). Store **only** the 36-byte UUID in Livewire state.
4. **Remote PostgreSQL WAN Latency & Single-Row Transaction Loops**:
   - *Cause*: Sequential single-row queries inside `DB::transaction` across remote Supabase connections.
   - *Fix*: Sub-200ms Transaction Law: Execute multi-row bulk `insert`, `insertOrIgnore`, and `upsert` operations.
5. **Answer-Key Leakage on Egress**:
   - *Cause*: Serializing Eloquent models directly to Inertia props without hiding `correct_option_index`.
   - *Fix*: Always scrub answer keys on egress. Reveal correct answers only through server-side grading or explicit forfeiture endpoints (`/api/questions/{id}/reveal`).
6. **Loose Equality in State Machines**:
   - *Cause*: Using `==` on subscription statuses or payment TRX IDs, causing type coercion bugs.
   - *Fix*: Strictly enforce `===` and typed Enums across all state transitions.
7. **Premature Spaced Learning Graduation**:
   - *Cause*: Marking a weak question as "mastered" after a single lucky guess.
   - *Fix*: Two-Strike Mastery Protocol: Require two consecutive correct answers across distinct sessions before graduating from `user_weak_questions`.
8. **Uncaught SSE Mobile Carrier Drops**:
   - *Cause*: Cellular carriers silently closing idle TCP connections after 30–60s during AI tutoring.
   - *Fix*: Emit server-side `: heartbeat\n\n` comments every 15s; implement client-side reconnect with `Last-Event-ID`.
9. **Multibyte Urdu String Truncation**:
   - *Cause*: Using single-byte string methods (`substr`, `strlen`) on Nastaliq/Urdu text.
   - *Fix*: Strictly use `mb_substr`, `mb_strlen`, `mb_strtolower`, and Unicode `/u` regex flags.
10. **Unsafe Column Drops in Same Release**:
    - *Cause*: Dropping a column while running container instances still execute code reading that column.
    - *Fix*: 3-Step Release Lifecycle: Add new column $\rightarrow$ Deploy sync/backfill $\rightarrow$ Drop old column in next sprint.
11. **Bypassing Model Events with Query Builder Writes**:
    - *Cause*: Using raw `DB::table('...')->insert()` bypasses Eloquent model events and table caching.
    - *Fix*: Explicitly invalidate related table cache tags after direct query-builder bulk mutations.

---

## 5. Standardized 5-Phase Feature Lifecycle
Every backend endpoint or feature mutation must strictly follow this structure:
```
[ Incoming Request ]
        │
        ▼ (Phase 1: Validation & Authorization)
  [ FormRequest ]  app/Http/Requests/...
        │
        ▼ (Phase 2: Orchestration & Delegation)
  [ Thin Controller ]  app/Http/Controllers/...
        │
        ▼ (Phase 3: Business Logic & Atomicity)
  [ Service / Action ]  app/Services/... (inside DB::transaction)
        │
        ▼ (Phase 4: Presentation & Projection)
  [ Inertia / SDUI / Resource ]  resources/js/pages/... or JsonResponse
        │
        ▼ (Phase 5: Automated Verification)
  [ Pest Feature Test ]  tests/Feature/...
```
- **Thin Controllers**: Controllers validate via FormRequest, delegate to a Service/Action, and return a response. Zero business logic or raw SQL inside controllers.
- **Atomic Transactions**: Any multi-step mutation must execute inside `DB::transaction(...)`.
- **Sub-200ms Transaction Law**: Database locks must complete in $<200\text{ms}$. Use multi-row bulk queries.
- **Zero N+1 Queries**: Always eager-load relations (`with([...])`) on collections.

---

## 6. The Mandatory CI Pre-Flight Gate (`composer ci:check`)
Before completing any task, recommending a commit, or pushing code to any branch, the agent **MUST** run the full CI suite via `composer ci:check`.
- **What it enforces**:
  1. `npm run lint:check` (ESLint on all JavaScript / Vue)
  2. `npm run format:check` (Prettier on all resources)
  3. `npm run types:check` (vue-tsc type safety)
  4. `./vendor/bin/pint --parallel --test` (Laravel Pint code style)
  5. `./vendor/bin/phpstan analyse` (Larastan / PHPStan Level 7)
  6. `./vendor/bin/pest` (Automated backend test suite)
- **Zero Tolerance**: Never push unverified code or assume a change is too small to break formatting or static analysis. Every PR or push recommendation must be preceded by a clean `composer ci:check` run.

---

## 7. Branch, Pull Request & Merge Permission Protocol (Explicit User Approval Gate)
Every feature modification and deployment must adhere to this explicit human-in-the-loop permission sequence:
1. **Separate Feature Branch**: Always create and commit changes to a dedicated feature branch (`feat/...`).
2. **PR Creation Gate**: **NEVER** create a GitHub Pull Request automatically. You **MUST** ask the user for permission, present the proposed PR title/summary, and create it only after explicit user approval.
3. **PR Merge Gate**: **NEVER** merge a Pull Request or merge the feature branch into `main` automatically. You **MUST** ask the user for permission to merge, and execute the merge only when the user explicitly grants approval.
4. **Post-Merge Branch Deletion Gate**: After merging into `main`, if the feature branch is no longer needed, you **MUST** ask the user for explicit permission before deleting the branch locally or remotely. Never delete branches autonomously.

---

# 📐 Tier 2: CONVENTIONS (Quality & Consistency Standards)

## 1. 🎨 Anti-AI Visual Laws & Mathematical Design System (SenseLab Aesthetic)

### A. The 4 Anti-AI Visual Laws
1. **No Generic Violet Gradients**: Ban saturated violet-to-cyan mesh gradients (`bg-gradient-to-r from-purple-600 to-indigo-600`). Use deliberate, high-contrast, purpose-built palettes (emerald, deep zinc, slate, amber, military olive).
2. **No Cardification of Plain Text**: Do NOT wrap every isolated paragraph or sentence in an elevated white card with heavy drop shadows. Group related functional items into disciplined semantic containers with subtle border strokes (`border border-border/60`).
3. **Exactly One Solid Action CTA per View**: Every screen, section, or modal must have exactly **ONE** primary solid button. Secondary actions must be ghost, outline, or text links. Prevent visual decision paralysis.
4. **Zero Dead Clicks**: Every interactive element (button, tab, link, icon) must be backed by a functional implementation. Banned: `href="#"`, `@click=""`, `@click="alert('Coming soon')"`.

### B. The 8 AI Visual Symptoms vs Senior Designer Standards
| AI Symptom | What AI Generates (BANNED ❌) | Senior Designer Standard (ENFORCED ✅) |
|---|---|---|
| **The "Eyebrow Pill"** | Generic capsule with 10% opacity tint and 100% saturated text. | Semantic typography hierarchy, crisp micro-labels (`text-xs font-mono uppercase tracking-wider text-muted-foreground`). |
| **The Violet/Blue Gradient** | Low-opacity violet-to-blue background halos and hero gradient blobs. | Crisp background tones, clean slate surfaces, subtle grid meshes, high-contrast card boundaries. |
| **"Cardification" of Everything** | Every paragraph, stat, and text block wrapped in floating cards. | Negative space, horizontal rule dividers (`border-b border-border/40`), cards reserved for discrete interactive entities. |
| **Indecisive CTAs** | Equal-weight side-by-side buttons. | Strict hierarchy: **One primary solid CTA per viewport**. Secondary actions use ghost buttons or text links. |
| **The 3-Card Lucide Grid** | Exactly 3 feature boxes with 2px Lucide icons in pastel squares. | Organic, data-dense layouts. Icons integrated directly alongside text without childish colored background squares. |
| **Uniform Border Radius** | Blindly applying `rounded-xl` to outer cards, inner buttons, and badges alike. | **Mathematical Nested Radius Rule**: $R_{\text{inner}} = \max(0, R_{\text{outer}} - \text{Padding})$. |
| **"Dead" Dark Mode** | Pure `#000000` pitch black with harsh 10% white borders. | **Rich Atmospheric Dark Mode**: Deep slate/navy undertones (`hsl(222, 47%, 7%)`), layered elevation surfaces, subtle borders. |
| **Generic Sparkles / Badges** | Floating stars, sparkle emoji, vague "AI Powered" marketing badges. | Concrete capability metrics ("84% Accuracy", "Timed: 20 Mins", "Sub-second verification"). |

### C. Mathematical Nested Border Radius Formula
When placing elements inside containers, follow the concentric curvature formula:
$$R_{\text{inner}} = \max(0, R_{\text{outer}} - \text{Padding})$$
- Container with `rounded-2xl` (16px) and `p-4` (16px padding) $\rightarrow$ Inner element must have `rounded-none` (0px) or `rounded-sm` (2px–4px).
- Container with `rounded-xl` (12px) and `p-2` (8px padding) $\rightarrow$ Inner button must have `rounded-sm` (4px).

---

## 2. 🤖 Socratic AI Tutor Architecture & Pedagogy

### A. Non-Negotiable Rules
1. **Zero-Knowledge standard**: NEVER reveal the correct option letter or text of an active MCQ.
2. **Frontend language contract**: Pure JavaScript in Vue `<script setup>`. No TypeScript.
3. **Tool Calling**: Maximum 2 tool-calling rounds per request. Tool available: `search_domain_knowledge` (cosine similarity search in pgvector).
4. **Honesty over Hallucination**: If knowledge search returns empty, admit it honestly. Never fabricate military dates, age limits, or medical criteria.

### B. Deterministic Intent Routing
Evaluate in this exact order:
1. **GREETING / SMALL TALK** (“hi”, “salam”, “aoa”) $\rightarrow$ Warm reply as domain mentor. **No tools**.
2. **PROBLEM SOLVING / SOCRATIC** (“why wrong”, “hint”, “explain active question”) $\rightarrow$ Socratic pedagogy only. **No tools**.
3. **EXAM STRUCTURE & ADVISING** (“what is ISSB?”, “what are WAT/SRT?”, “how to prepare?”) $\rightarrow$ Domain knowledge + `search_domain_knowledge` if needed.
4. **KNOWLEDGE LOOKUP / SYLLABUS FACT** (“registration dates”, “medical standards”, “age limit”) $\rightarrow$ Call `search_domain_knowledge`.
*Fallback*: If ambiguous and `active_mcq` is present $\rightarrow$ treat as Socratic.

### C. 3-Level Pedagogical Ladder
- **Level 1 (Nudge)**: Point out a subtle detail or pattern in the question stem without referencing options.
- **Level 2 (Elimination)**: Guide candidate to eliminate one clearly incorrect distractor by explaining why its underlying logic fails.
- **Level 3 (Conceptual Breakdown)**: Explain the core governing rule (grammar rule, physics formula, number series pattern) and ask candidate to apply it.

---

## 3. 📦 Subsystem Domain Contracts

### A. `content_modules` (Polymorphic Modalities)
- `module_type`: `mcq_test` (timed test engine), `pdf_resource` (past papers), `subjective_prompt` (study accordion).
- `dashboard_section`: `academic`, `intelligence`, `physical`, `medical`, `interview`.
- `display_mode`: `grid`, `stepper`, `list`.

### B. `question_bank` (Schema & Scrubbing)
- `payload` (`jsonb`): contains `options` (array of strings), `explanation`, `tags`.
- **Egress Scrubbing**: The column or attribute `correct_option_index` must NEVER be serialized in public payloads.

### C. `user_subscriptions` (Payment State Machine)
- States: `pending` $\rightarrow$ `active` / `rejected` / `expired`.
- Bypasses: Governed by `PlatformSetting::isPaymentBypassEnabled()`.
- Idempotency: Webhook IPN notifications must check unique `transaction_id` before mutations.

### D. `domains` & `domain_flows`
- Hierarchy: Implemented via PostgreSQL `ltree` extension for sub-millisecond tree traversals.
- Workflows: Multi-phase screening flows specify `prerequisites` (`jsonb`) governing node unlocking.

---

## 4. Code Style & Tooling Commands
- **Mandatory Pre-Flight CI Suite**:
  ```bash
  composer ci:check
  ```
- **Formatting**:
  ```bash
  # Backend formatting
  ./vendor/bin/pint --dirty
  ./vendor/bin/pint --dirty --test

  # Frontend formatting
  npm run format:check
  npm run format
  ```
- **Static Analysis**:
  ```bash
  # Frontend types & lint
  npm run lint:check
  npm run types:check

  # Backend types (Larastan / PHPStan Level 7)
  ./vendor/bin/phpstan analyse
  ```
- **Test Suite**: Pest test suite:
  ```bash
  ./vendor/bin/pest <path/to/Test.php>
  ```
- **Control Flow**: Early returns and guard clauses (max 2 indentation levels).
- **Strict Typing**: Head every new PHP file with `declare(strict_types=1);`.
- **Pure JavaScript in Vue**: All components in `resources/js/` must use standard JavaScript `<script setup>`. Never introduce TypeScript syntax.
- **Financial Precision**: Store PKR amounts as integer paisa (never float). Store timestamps in UTC, format for PKT at presentation layer.

---

# 🔍 Tier 3: OBSERVATIONS (Domain Intelligence)

### 1. Pakistani Competitive Examination Curricula
- **PMA Long Course**: Verbal Intelligence, Non-Verbal Intelligence (Matrix, Series, Odd-One-Out), Academic (English, Math, Pak Studies, Islamiat, General Knowledge).
- **ISSB (Inter Services Selection Board)**: Word Association Tests (WAT - 10s per word), Sentence Completion (Urdu & English), Situation Reaction Tests (SRT), Group Discussions, Outdoor Tasks (PGT/HGT), and Psychological Interviews.
- **CSS / PMS Competitive Exams**: Compulsory subjects (Essay, Précis, General Science & Ability, Current Affairs, Pakistan Affairs, Islamic Studies).
- **MDCAT / Medical Entry**: Biology, Chemistry, Physics, English, Logical Reasoning.

### 2. Socratic Roman Urdu Communication Guidelines
- When candidates communicate in Roman Urdu, respond naturally in respectful, encouraging Roman Urdu (“Bohat acha sawal hai,” “Zara is option par ghor karein,” “Sawal mein pattern dekhein”).
- Keep tone authoritative yet mentor-like. Avoid overly complex English jargon when explaining foundational concepts.
- Never use markdown hashtags (`#`) inside chat bubbles; use bold text for key terms.
