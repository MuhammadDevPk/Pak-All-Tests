# .agent/PATTERNS.md — Engineering Patterns, Mandates & Conventions

> **Status**: MANDATORY ARCHITECTURAL CONTRACT — ALWAYS ACTIVE  
> **Applicability**: All backend feature development, frontend components, and automated testing  
> **Workspace**: Pak-All-Tests (Laravel 13 • Inertia 3 • Vue 3.5 • PostgreSQL 16+ Supabase)

---

## 🏛️ Tier 1: MANDATES (Non-Negotiable Laws)

### 1. The Legacy Baseline Rule
- **Scope Discipline**: Do **NOT** attempt to fix pre-existing lint errors, formatting warnings, or architectural flaws in files you did not touch in your current session.
- **Zero Scope Creep**: Keep diffs surgically focused on the task at hand. Modifying untouched files to "clean them up" introduces unnecessary blast radius and risks CI breakage.
- **Test Integrity**: If an existing legacy test fails, determine whether your changes broke the contract. **NEVER modify or weaken an existing test to cheat or force a green build.** Fix the regression in your implementation code.

### 2. Chaos Testing Protocol
- **Adversarial Assertion Mandate**: Do **NOT** write simple, superficial "happy path" tests.
- **Simulate Real-World Chaos**: Real users execute erratic, malicious, and out-of-order operations. Tests must actively probe:
  - Malformed and edge-case inputs (empty arrays, boundary numbers, negative integers, script tags).
  - Race conditions and duplicate triggers (replay attacks on webhooks, concurrent exam submissions, double-clicks).
  - Unauthorized traversal (BOLA/IDOR attacks where User A attempts to access User B's domain or team).
  - State machine boundary violations (submitting answers after test expiry, re-activating expired passes).
- **Execution Environment**: Always test against real database states using `DatabaseTransactions`. Unit tests with superficial mocks hide critical bugs.

### 3. Staff Engineer PR Review (Pre-Conclusion Self-Audit)
Before marking any task complete or returning output to the user, conduct a strict, adversarial **Staff Engineer PR Review** on your own code:
1. **Adversarial Scrutiny**: Inspect every changed line through the eyes of an attacker. Ask:
   - *"How can someone exploit this change to gain free access or elevate privileges?"*
   - *"Will this query execute in under 10ms when a table has 500,000 rows?"*
   - *"What happens if this external HTTP call times out or throws 500?"*
2. **Review Bar**: Ask: *"Would a Staff Systems Architect accept this PR on first sight as production-ready and flawless?"*
3. **Remediation**: Fix all identified issues, edge cases, or potential leaks before declaring work complete.

### 4. Standardized 5-Phase Feature Lifecycle
Every backend endpoint or feature mutation must strictly follow this 5-phase structure:
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

## 📐 Tier 2: CONVENTIONS (Quality & Consistency Standards)

### 1. Code Style & Tooling Commands
- **Formatting**: Laravel Pint (`preset: "laravel"`). Format modified files before concluding:
  ```bash
  ./vendor/bin/pint --dirty
  # Verification:
  ./vendor/bin/pint --dirty --test
  ```
- **Static Analysis**: Larastan / PHPStan Level 6:
  ```bash
  ./vendor/bin/phpstan analyse <modified-files>
  ```
- **Test Suite**: Pest test suite:
  ```bash
  ./vendor/bin/pest <path/to/Test.php>
  ```

### 2. Code Hygiene & Control Flow
- **Early Returns & Guard Clauses**: Eliminate nested `if/else` hierarchies (max 2 indentation levels). Handle validation and guard clauses immediately at the top of methods.
- **Strict Comparison**: Always use strict comparisons (`===`, `!==`). Never use loose comparisons (`==`, `!=`).
- **Strict Typing**: Head every new PHP file with `declare(strict_types=1);` and enforce explicit parameter and return types.
- **Single Responsibility**: Target methods under 20 lines and classes under 200 lines. Extract complex workflows into Action or Service classes.
- **Pure JavaScript in Vue**: All components in `resources/js/` must use standard JavaScript `<script setup>`. Never introduce TypeScript syntax.
- **Financial Precision**: Store PKR amounts as integer paisa (never float). Store timestamps in UTC, format for PKT at presentation layer.

---

## 🔍 Tier 3: OBSERVATIONS (Contextual Architecture Notes)

- **Server-Driven UI (SDUI)**: Domain dashboards compile dynamically from backend JSON schemas via `SduiPayloadTransformer`. The frontend renders components dynamically through `SduiRenderer.vue`.
- **Role Isolation**: Admin dashboard is strictly segmented under the `'admin'` guard. Public candidate sessions operate under `'web'`.
- **Socratic AI Tutor**: Real-time SSE streaming (`text/event-stream`) provides pedagogical hints without ever leaking answer keys.
- **Livewire Payload Bounds**: Never pass $>20$ Eloquent models into Livewire modal properties; use the Cache-Pointer pattern (`$batchId`).
