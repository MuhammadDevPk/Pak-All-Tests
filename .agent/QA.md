# QA.md — Forensic Quality Assurance & Line-by-Line Code Review Protocol

> **Status**: MANDATORY OPERATIONAL LAW — ALWAYS ACTIVE  
> **Applicability**: All Developers, Code Reviewers, and Autonomous AI Agents  
> **Repository**: Pak-All-Tests (Laravel 13 • Inertia 3 • Vue 3.5 • PostgreSQL 16+ / Supabase)

---

## ⚡ The Supreme QA Law: Never Rely Solely on Unit Tests

> **"Passing unit tests are a baseline sanity check — NEVER proof of correctness.  
> Automated unit tests test only what their author remembered to verify under idealized conditions.  
> True quality assurance demands rigorous, manual, line-by-line code review across hostile, edge-case, and real-user execution paths."**

Unit tests frequently pass with flying colors while catastrophic production bugs remain hidden:
* Unit tests run against clean, mocked factories — real users submit dirty, partial, or malicious data.
* Unit tests do not verify CSS z-index stacking orders, visual occlusion, or backdrop-filter stacking contexts.
* Unit tests do not detect synthetic-click vs pointer-capture event conflicts on touch devices or keyboard users.
* Unit tests do not catch silent UI failures where an action aborts without notifying the user.

Every code change in this repository MUST be subjected to a **Line-by-Line Forensic Code Audit** covering the **6 Defect Hunting Categories** detailed below.

---

## 🔍 The 6 Defect Hunting Categories (Line-by-Line Audit Checklist)

When reviewing any pull request, newly generated feature, or refactored component, inspect every single line against these six failure categories:

### 1. 🧠 Logical Bugs & Edge-Case Traps
* **Falsy Zero Traps (`0 == false`)**:
  - In JavaScript, `0`, `""`, and `null` are falsy. A check like `if (!module?.id)` silently aborts virtual test sessions where `module.id === 0` (e.g. Weak MCQs practice sets).
  - A check like `if (!selectedOption)` treats option index `0` (Option A) as unselected.
  - **Rule**: Always use explicit identity checks: `if (module?.id === undefined || module?.id === null)` and `if (selectedIndex !== null && selectedIndex !== undefined)`.
* **Off-By-One & Slice Boundary Errors**:
  - Check 0-indexed array vs 1-indexed UI conversions (`idx + 1` vs `currentQuestionIndex`).
  - Verify slice limits in pagination: `questions.slice(start, start + pageSize)`.
* **Inverted Precedence & Complex Ternary Operators**:
  - Never chain more than 2 ternary operators in template expressions. Extract into computed properties or deterministic helper functions (e.g. `getOptionClasses()`).
  - Verify operator precedence between null coalescing (`??`) and logical OR (`||`). Remember that `??` only checks for `null`/`undefined`, whereas `||` checks for any falsy value.

---

### 2. 🥷 Silent Bugs (The Stealth Killers)
* **Silent Abortions & Unhandled Early Returns**:
  - Code that encounters an unexpected condition and calls `return;` without updating user feedback, resetting loading states (`isSubmitting = false`), or logging a warning.
* **Swallowed Exceptions**:
  - `catch (err) { /* empty */ }` blocks that swallow network or parsing failures, leaving the user with a perpetually spinning loader or non-responsive button.
  - **Rule**: Every `catch` block must either display a user-facing error toast/banner or explicitly log to observability channels.
* **Loose PHP Type Juggling Vulnerabilities**:
  - In PHP, string comparisons can juggle types unexpectedly. For instance, `(int) "malicious_string"` equals `0`. If option `0` is the correct answer, malicious strings falsely score as correct.
  - **Rule**: Enforce strict type validation: `if ($choice !== null && is_numeric($choice) && (int) $choice === (int) $correctIndex)`.
* **Scrubbed Payload Assumptions**:
  - In anti-cheat question banks, `correct_option_index` is scrubbed from client payloads. Client-side scoring must never assume the answer key exists in `payload.correct_option_index` before server grading.

---

### 3. 💥 Loud Bugs (Runtime Crashes & HTTP 500s)
* **Unchecked Null Dereferencing**:
  - Calling methods or properties on nullable relations: `$module->domain->id` crashes if domain is null. Always use nullsafe operators `$module->domain?->id` or explicit instance checks (`if ($domain instanceof Domain)`).
  - In Vue: `props.module.content_meta.duration_minutes` crashes if `content_meta` is null. Use optional chaining: `props.module?.content_meta?.duration_minutes`.
* **Undefined Object Keys / Collection Offsets**:
  - Accessing array indices without `isset()` or null-coalescing.
* **Malformed External API Handling**:
  - External endpoints (Gemini, OpenAI, EasyPaisa, JazzCash) can return HTML error pages (Azure 502, Cloudflare 504) instead of JSON. Always check `$response->successful()` before calling `$response->json()`.

---

### 4. 🧩 Incomplete Logic & Half-Baked Features
* **Missing Backend Endpoints for Frontend Actions**:
  - A frontend button dispatches a request (e.g. "Submit Weak MCQs Test"), but the corresponding API route or controller method does not exist or was left as a no-op.
* **Desynchronized Query Filters**:
  - Frontend displays active questions only (`where('is_active', true)`), but backend submission controller counts all attached questions regardless of status, skewing total percentages and pass/fail thresholds.
  - **Rule**: Both retrieval and grading queries MUST share identical scope constraints.
* **Missing State Resets**:
  - Toggling between test modes (e.g. Page View vs Focus Mode) or starting a new session without resetting `selectedAnswers`, `showExplanations`, `forfeitedQuestions`, or timers.
* **Dead Clicks & Visual Feedback Deficits**:
  - Every clickable element MUST have:
    1. Cursor pointer (`cursor-pointer`).
    2. Distinct hover state (`hover:bg-...`).
    3. Active click feedback (`active:scale-95`).
    4. Disabled state styling (`disabled:opacity-40 disabled:cursor-not-allowed`).
    5. Asynchronous loading indicator (spinner or "Evaluating...").

---

### 5. 🔌 Client-Server Disconnects & Reactive Mismatches
* **Synthetic Click vs Pointer-Capture Event Conflicts**:
  - When elements are draggable (e.g. `setPointerCapture`), pointer release fires a synthetic `click` event. Failing to debounce this causes double-toggling or immediate re-closing.
  - Relying exclusively on pointer events breaks keyboard users (`Tab` + `Enter`/`Space`) who emit `click` with zero pointer movement. Always maintain clean `@click` delegation with drag debouncing.
* **Z-Index & Backdrop Stacking Collisions**:
  - Elements with `backdrop-blur-md` create new CSS stacking contexts that paint on top of earlier siblings unless explicit parent stacking orders (`relative z-50` vs `relative z-20`) are enforced.
  - Floating action buttons must never occlude primary action buttons (e.g. sticky Submit button) by default.
* **Missing CSRF & AJAX Headers**:
  - Raw `fetch()` calls without `X-CSRF-TOKEN` or `X-Requested-With: XMLHttpRequest` fail on POST routes.

---

### 6. ⚡ Performance & Scalability Bugs
* **Hidden N+1 Database Queries**:
  - Accessing Eloquent relationships inside loops or collection mapping closures (`$items->map(fn($q) => $q->domain->name)`). Always eager load relationships (`with(['domain', 'question'])`).
* **Supabase Remote WAN Latency Multipliers**:
  - Every remote database query incurs 50–150ms WAN latency. Sequential queries inside loops or transactions violate the Sub-200ms Transaction Law. Always use bulk operations (`whereIn`, multi-row `insert`, `upsert`).
* **Unindexed `WHERE` & `ORDER BY` Columns**:
  - Querying dynamic tables without compound indexes (e.g. `user_weak_questions` requires `[user_id, status]`).
* **Frontend Memory Leaks**:
  - Adding `window.addEventListener('resize', ...)` or `setInterval(...)` in `onMounted` without removing them in `onUnmounted`.

---

## 🚶 The 4 Real-User Scenario Walkthroughs

Before certifying ANY feature as ready, walk through the code from these 4 real-world user perspectives:

```
┌────────────────────────────────────────────────────────────────────────┐
│                   REAL-USER SCENARIO AUDIT MATRIX                      │
├───────────────────────┬────────────────────────────────────────────────┤
│ 1. Novice First-Timer │ Fresh session, empty cache, default positions. │
│                       │ Zero occlusion, crystal-clear visual hierarchy.│
├───────────────────────┼────────────────────────────────────────────────┤
│ 2. Rapid Power-User   │ Rapid double-clicks, keyboard shortcuts, fast  │
│                       │ navigation. No race conditions or UI freezes.  │
├───────────────────────┼────────────────────────────────────────────────┤
│ 3. Hostile & Edge     │ 0 questions answered, timer expiring, network  │
│                       │ drop mid-request, hostile/empty inputs.        │
├───────────────────────┼────────────────────────────────────────────────┤
│ 4. Mobile & A11y      │ Small viewports (375px), touch drag, dark mode,│
│                       │ Tab/Enter/Space navigation, screen readers.   │
└───────────────────────┴────────────────────────────────────────────────┘
```

---

## 📋 Forensic QA Verification Checklist (Sign-Off Standard)

Before declaring any task or feature complete, verify that:

- [ ] **1. Line-by-Line Inspection Complete**: Every modified line was read, understood, and audited against the 6 Defect Hunting Categories.
- [ ] **2. Zero Silent Bugs**: No early returns without UI reset; no swallowed exceptions; no falsy zero traps (`id === 0`).
- [ ] **3. Incomplete Logic Resolved**: All referenced endpoints exist; all states (loading, error, empty, success) are implemented.
- [ ] **4. Server-Authoritative Integrity**: Ground truth (scoring, answers, subscription access) is strictly computed on the backend.
- [ ] **5. Active Filters Synchronized**: Frontend display filters match backend evaluation query filters 1:1.
- [ ] **6. Visual & Stacking Hierarchy Verified**: No floating widgets occlude buttons by default; dropdown menus paint above siblings.
- [ ] **7. Touch & Keyboard A11y Intact**: Draggable items work with touch and mouse; keyboard users (`Enter`/`Space`) can activate all controls.
- [ ] **8. Automated Tests Passing**: `./vendor/bin/pest` passes 100% with exit code `0`.
- [ ] **9. Code Style Validated**: `./vendor/bin/pint --dirty --test` passes cleanly.
- [ ] **10. Assets Built Cleanly**: `npm run build` compiles with zero warnings or errors.
