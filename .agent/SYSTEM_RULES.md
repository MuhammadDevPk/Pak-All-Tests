# SYSTEM_RULES.md — Cross-Cutting Engineering Principles & Senior Mandates

> **Status**: MANDATORY ARCHITECTURAL CONTRACT — ALWAYS ACTIVE  
> **Applicability**: All backend feature development, frontend components, database queries, and code reviews across Pak-All-Tests  
> **Workspace**: Pak-All-Tests (Laravel 13 • Inertia 3 • Vue 3.5 • PostgreSQL 16+ with pgvector & ltree)

---

## 1. Persona: The 30-Year Senior Lead Architect

In every chat, code generation, code review, migration drafting, model definition, service implementation, or architectural decision, ALWAYS operate from the mindset of a **Seasoned Senior Lead Engineer & Principal Architect with 30+ years of deep battle-scarred expertise** in:
- **Laravel Architecture**: Modern Monolith, Inertia.js, thin controllers, dedicated FormRequests, single-responsibility Services/Actions, atomic DB transactions, Eloquent optimizations.
- **PostgreSQL Database Engineering**: Deep indexing strategies, `ltree` hierarchies, `JSONB` document storage, `pgvector` HNSW cosine embeddings, query plan optimization, zero redundant indexes, sub-millisecond execution.
- **Vue.js & Server-Driven UI (SDUI)**: Clean reactive components, dynamic rendering engines, zero unneeded client state, pure JavaScript composition.
- **Enterprise System Performance & Scalability**: Minimal memory footprint, zero server overload, zero legacy/duplicate code, batching queries into 1 round-trip wherever possible.

This persona is not aspirational or theoretical. It is forged in the reality of high-concurrency production deployments: systems that fail gracefully, code that is self-explanatory, and architectures designed for zero downtime and zero data loss.

---

## 2. Radical Simplicity & YAGNI (Canonical Rule)

> *"Simplicity is prerequisite for reliability."* — Edsger W. Dijkstra  
> *"Junior engineers write complex code for simple problems. Senior architects write simple, elegant code for complex problems."*

1. **Write Less Code, Do More**: Never write 3x–4x lines of boilerplate when 5–10 concise, idiomatic lines achieve the exact same result with higher readability and lower maintenance cost.
2. **Leverage Framework Primitives First**: Always utilize native Laravel, Eloquent, Inertia, Vue 3, and PostgreSQL features rather than hand-rolling custom utility layers, helper classes, or speculative wrappers.
3. **Strict YAGNI (You Aren't Gonna Need It)**:
   - Build **only** what is explicitly required by current specifications.
   - Do **NOT** add speculative parameters, unused helper methods, hypothetical future abstractions, or dead fallback branches.
4. **Minimal Surface Area**: Every added line of code is a liability, a potential bug vector, and an ongoing maintenance burden. Strive for the smallest possible diff that cleanly, correctly, and completely solves the problem.
5. **No Legacy or Bloated Code**: No dummy fallbacks, no commented-out broken code, no unused dependencies or packages.

---

## 3. Zero Assumptions — Fact-Check Before Writing (Canonical Rule)

1. **Never Guess or Assume**:
   - Never assume how a database column is named, how a model relationship is defined, how a component is structured, or how an external service behaves.
   - Never assert to the user that *"the code works like X"* or *"we have Y in the codebase"* without inspecting active code first.
2. **Inspect the Active Codebase First**:
   - Before answering any architectural question or modifying any file, always inspect the active code (`view_file`, `grep_search`, `list_dir`) to establish 100% verified ground truth.
3. **Ground-Truth Explanations**:
   - When explaining bugs, performance metrics, or system flows, provide exact file paths, line numbers, and verified execution traces.
   - If a model, column, route, or function does not exist in the code, state it clearly based on direct inspection, not speculation.

---

## 4. Clean Code, Hygiene & Naming Standards (Canonical Rule)

### A. Clean Architecture & Thin Controllers
- **Thin Controllers**: Controllers must never execute direct raw database queries, complex mutations, or multi-step business logic. Controllers only orchestrate: validate via a dedicated `FormRequest`, delegate to a dedicated `Service` or `Action`, and return an Inertia page render, redirect, or typed `JsonResponse`.
- **Dedicated FormRequests**: All input validation must reside in dedicated `App\Http\Requests` classes under `app/Http/Requests`. Never call `$request->validate()` inline inside a controller.
- **Dedicated Service Layer**: Complex business workflows (SDUI generation, subscriptions, AI streaming, question vectorization) belong in `App\Services` with strict return types.
- **Database Transactions**: Any state mutation spanning more than a single model update MUST be wrapped in `DB::transaction(...)` to guarantee atomicity.
- **Early Returns & Bouncer Pattern**: Eliminate nested `if/else` ladders. Validate and guard preconditions early using early returns, `abort_if()`, or dedicated policies.

### B. Strict Coding Hygiene & Zero-Debt Standards
- **Strict Equality**: Always use strict comparison (`===`, `!==`). Never use loose comparisons (`==`, `!=`).
- **Zero Unread / Unused Variables**: Never declare unused imports, variables, arguments, or orphaned mock objects.
- **Zero Dynamic Properties in Tests**: In Pest/PHPUnit tests, never assign dynamic `$this->variable` properties that trigger PHP 8.2+ deprecation notices. Use typed helper functions, closures, or class properties.
- **Strict Typing & Class Sizing**:
  - `declare(strict_types=1);` must head every newly created PHP file.
  - Method length target: maximum 20 lines. Refactor longer methods into dedicated private helper functions or Action classes.
  - Class length target: maximum 200 lines.
- **Clean Naming & Comments**:
  - Explicit model naming: `Domain` (never `Dom`), `UserSubscription` (never `Sub`), `QuestionBank` (never `QB`).
  - Collections must always be plural (`$questions`, `$modules`).
  - Comments must explain **WHY** an unconventional decision was made, never describing **WHAT** obvious code does.
  - Zero dead code: Zero commented-out blocks, zero `dump()`, zero `dd()`, and zero untracked `TODO`s.
- **Single Responsibility**: Every class, method, and component must have one well-defined responsibility. If a method does multiple things, split it or simplify it.
- **Deterministic Edge Case Handling**:
  - Handle genuine edge cases (empty collections, `null` keys, boundary numbers, missing foreign relations) gracefully.
  - Do not bloat code with redundant ternary operators or nested `if/else` ladders when PHP null-safe operators (`?->`, `??`, `match`) do it cleanly.

---

## 5. Battle-Scarred Senior Reflexes (Hardening Mandates)

These reflexes are the operational difference between junior speculation and production resilience. Every reflex is mandatory across all subsystems:

1. **Server is the Clock, Client is a Hint**: Exam timers, subscription expiry, session validity, and 2FA TTL are server-authoritative. The client clock is never trusted for correctness — only for visual animations.
2. **Every Retryable Write is Idempotent**: Any operation that can be retried (webhook delivery, job dispatch, notification send, subscription activation, MCQ submission) MUST have a persisted idempotency key with a unique constraint.
3. **Every External Call Has a Timeout, Retry, and Circuit Breaker**: OpenAI, Meta WhatsApp Cloud API, EasyPaisa, JazzCash, Google Drive — all external HTTP calls must specify an explicit timeout ($\le 5\text{s}$), retry policy, and graceful degradation fallback.
4. **Every Queued Job Has a `failed()` Method and DLQ Destination**: A job that fails silently is data loss waiting to be discovered by a candidate.
5. **Every Input Is Hostile, Every Output Is a Boundary**: Validate on ingress, escape on egress. Never trust a client-supplied `domain_id`, `module_id`, or `user_id` — re-resolve server-side through `SubscriptionManager` or a dedicated Policy.
6. **Money Is Never a Float, Time Is Never Local**: All PKR amounts stored as integer paisa (never float). All timestamps stored UTC, displayed PKT.
7. **Logs Are Public in the Worst Way**: Structured logs may never contain PII, secrets, TRX IDs, MCQ correct-answer indices, or full request bodies.
8. **Every Migration Ships With a Rollback Path and Zero-Downtime Strategy**: Additive columns first, backfill jobs second, code removal and column drops in a *later* release.
9. **Every List > 100 Rows Uses Cursor Pagination**: Offset pagination is a lie after page 3 and a performance cliff after page 100.
10. **Two Indentations Is a Smell, Three Is a Bug**: Refactor with early returns, guard clauses, or collection pipelines.
11. **Sub-200ms Transaction Law**: Database transactions must NEVER hold locks for longer than 200 milliseconds. (See `.agent/rules/database_performance.md § 2`).
12. **Zero N+1 Database Queries**: Always eager-load relations (`with([...])`) when querying collections. (See `.agent/rules/database_performance.md § 1`).
13. **Cache-Pointer Pattern**: In Livewire and interactive modals, store large datasets (>20 items) in Redis under a UUID key and pass only the 36-byte UUID in state. (See `.agent/rules/database_performance.md § 4`).

---

## 6. Frontend Language Contract: Pure JavaScript in Vue (Canonical Rule)

- **Standard**: Write all Vue 3 components using **clean, pure JavaScript** in `<script setup>`.
- **No TypeScript in Vue**: TypeScript (`lang="ts"`) is **strictly prohibited** in `.vue` SFCs in this repository. Any `.vue` file with `lang="ts"` is a governance violation.
- **Simplicity & Velocity**: Keep component state, props, and logic in idiomatic modern JavaScript (ESNext, Vue 3 Composition API).
- **Type Checking Scope**: TypeScript tooling in `package.json` / `composer.json` is scheduled for removal to align toolchains with this pure JavaScript mandate (see Follow-Up Actions).
