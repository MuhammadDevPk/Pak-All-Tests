# MUSE.md — Autonomous Engineering Guardrails & Anti-Patterns

> **Target AI**: MUSE (G U J E W E L)  
> **Status**: MANDATORY PRODUCTION CONTRACT — DEDICATED FOR MUSE  
> **Applicability**: Exclusively loaded and obeyed by Muse for all coding, refactoring, feature creation, and architectural tasks across Pak-All-Tests (Laravel 13 • Inertia 3 • Vue 3.5 • Filament • PostgreSQL 16+).  
> **Purpose**: Codifies universal architectural anti-patterns and systemic guardrails discovered during forensic code audits. Muse must follow these generalized principles across **all modules** (frontend, backend, database mutations, UI, and async jobs) without exception.

---

## 🧭 Executive Directive for Muse

Do not write code in a vacuum. A passing unit test with mocks or synthetic data does **not** prove code works in production. Every feature, component, and endpoint must be engineered to survive unexpected user input, asynchronous worker failures, dark mode themes, and remote database latency.

Before delivering any PR or committing code, Muse must verify its implementation against the **7 Universal Anti-Patterns** below.

---

## 🚫 The 7 Universal Anti-Patterns Muse Must Avoid

### 1. The "Unit Test Bubble" Trap (Caller-Receiver Disconnect)
* **The Anti-Pattern**: Writing a unit test that bypasses framework invocation boundaries (e.g., instantiating a job or action directly with `new Class()` and calling `->handle()`), while the actual caller (controller, Livewire component, or command) uses a framework helper (e.g., `dispatchSync()`, `dispatch()`, event listeners) with mismatched arguments. The test passes 100%, but the application crashes with a fatal runtime error (`ArgumentCountError`, `TypeError`) the moment a user interacts with it.
* **The Rule for Muse**:
  - Always verify the exact signature and calling convention of the invocation pipeline. If a framework helper (`dispatch()`, `dispatchSync()`) expects constructor arguments, **never pass an instantiated object into it**.
  - Write tests that exercise the actual calling boundary (e.g. testing the Filament form submission, controller request, or HTTP route) or verify signature parity through strict static analysis (`phpstan`).
  - Never assume a component is functional just because its isolated class method executes in a sandbox.

---

### 2. The "Partition-Before-Validate" Data Loss Loop
* **The Anti-Pattern**: Iterating over expected categories, subjects, or config keys first and filtering data inside that loop. Any incoming record with an unrecognized, malformed, or unmapped key is silently dropped, never processed, and never counted in error metrics.
* **The Rule for Muse**:
  - Always use **Single-Pass Stream Processing ($O(N)$)** for all ingestion, batch processing, or payload handling.
  - Every single item in an incoming stream MUST deterministically resolve into one of three explicit outcome buckets:
    1. **`Valid / Inserted`** — passed all validation rules and stored.
    2. **`Duplicate / Skipped`** — recognized as existing, safely skipped without mutation.
    3. **`Invalid / Error`** — rejected with a clear reason and tracked in error totals (e.g., `unmapped_invalid`, malformed schema).
  - Never iterate over expected keys to search within data. Iterate over the incoming data and map to keys. Zero silent drops.

---

### 3. "Dry-Run" Mutation Leakage (Impure Simulation)
* **The Anti-Pattern**: Offering a "Preview", "Dry-Run", or "Validate Only" mode that avoids inserting primary records, but inadvertently creates parent records, related entities, cache entries, or join-table pivots. Alternatively, returning `0` or dummy counts instead of calculating what *would* happen.
* **The Rule for Muse**:
  - Dry-run and preview modes must be **100% pure, idempotent, and read-only**.
  - When `$dryRun === true`, the code must **never** execute `create()`, `firstOrCreate()`, `update()`, or `delete()` on any database table, including parent categories or lookup tables.
  - The preview path must run the exact same deduplication, validation, and counting logic as the live path, returning true projected totals (`inserted`, `duplicates`, `invalid`) so the user gets an accurate simulation before committing.

---

### 4. The "Fire-and-Forget" Worker Oblivion (Job Hygiene Failure)
* **The Anti-Pattern**: Dispatching background jobs or asynchronous workers without failure lifecycle hooks. If a worker hits a timeout, memory limit, deadlock, or unexpected exception, the job terminates silently. The user is left staring at an eternal spinner, and the admin panel never reflects the failure.
* **The Rule for Muse**:
  - Every queued job must implement `public function failed(?\Throwable $exception): void`.
  - In `failed()`, Muse must:
    1. Log the structured error with correlation metadata.
    2. Update any cached status or database state from `processing` / `pending` to `failed`.
    3. Notify the user/admin via a persistent channel (database notification, alert banner, or webhook).
  - Background processes must never leave orphaned "pending" states when an exception occurs.

---

### 5. Theme & Interactive Blindness (Light-Only & Frozen UX)
* **The Anti-Pattern**:
  - Styling frontend or admin views with hardcoded light background colors and fixed dark text, creating unreadable contrast when viewed in dark mode.
  - Launching asynchronous or long-running processes without interactive polling or visual pending states, leaving the user on a static, unresponsive screen.
* **The Rule for Muse**:
  - **Dark Mode Support**: Every Blade, Vue, or Tailwind template must explicitly support both light and dark themes (e.g., `.dark .card`, `dark:bg-gray-800`, `dark:text-gray-100`). Never hardcode light-only hex colors or white card backgrounds without dark counterparts.
  - **Live Feedback**: Any asynchronous workflow must feature visual feedback: active spinners, status badges, and automatic polling (`wire:poll.3s`, Server-Sent Events, or WebSocket listeners) that updates dynamically once the background task finishes.

---

### 6. Static Analysis & Type Contract Neglect
* **The Anti-Pattern**: Relying on loose PHP runtime flexibility, omitting PHPDoc annotations for framework-injected dynamic properties (e.g. Livewire/Filament `$form`), leaving array parameters untyped, or using loose equality (`==`) which leads to silent type-juggling vulnerabilities (e.g., `(int) 'string' === 0`).
* **The Rule for Muse**:
  - Explicitly annotate all framework-injected properties (e.g., `@property Schema $form`).
  - Declare strict parameter and return types on every method (`int`, `string`, `array`, `void`).
  - Use strict comparisons (`===`, `!==`) and avoid blind type-casting of untrusted user input.
  - Ensure all code cleanly passes `phpstan analyse` and Pint formatting before submitting.

---

### 7. False Confidence in Faked Unit Tests (Real-User Forensic QA)
* **The Anti-Pattern**: Assuming code is bug-free simply because `pest` or `phpunit` returned an exit code of `0`. Mocks and clean test fixtures hide real-world edge cases: non-numeric strings, empty payloads, duplicate submissions, and network latencies.
* **The Rule for Muse**:
  - Muse must perform a mental "Forensic Walkthrough" of every change as if a real, adversarial user is interacting with it:
    - *What if the user submits an empty file or corrupt JSON?*
    - *What if the database connection drops during record 45 of a 50-item batch?*
    - *Does the frontend component actually listen to the events the backend emits?*
    - *Will this query trigger an N+1 loop or exceed the Sub-200ms transaction law on remote PostgreSQL connections?*
  - Passing unit tests are the baseline, not the destination. Forensic code correctness is the standard.

---

## 🛠️ Muse Pre-Commit Verification Checklist

Whenever Muse builds or refactors any feature, it must verify this checklist:

- [ ] **1. Boundary Check**: Are all framework helpers (`dispatch()`, `route()`, `event()`) invoked with their exact declared parameters?
- [ ] **2. Exhaustive Data Accounting**: Does every item in an incoming array or payload reach an explicit success, duplicate, or error outcome?
- [ ] **3. Read-Only Purity**: Does the dry-run/preview mode execute zero database writes or side-effects?
- [ ] **4. Job Hygiene**: Does every queued job have a `failed(\Throwable)` handler with user notification and status cleanup?
- [ ] **5. Theme & Motion**: Does the UI render cleanly in dark mode, and do async tasks poll for live updates?
- [ ] **6. Strict Types & Linter**: Are all dynamic properties annotated, types declared, and does `./vendor/bin/pint --dirty --test` pass?
- [ ] **7. Adversarial QA**: Has the code been vetted against empty, malformed, and out-of-order data?
