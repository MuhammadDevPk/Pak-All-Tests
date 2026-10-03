# AGENTS.md — Autonomous Governance System

> **Status**: MANDATORY PRODUCTION CONTRACT — ALWAYS ACTIVE  
> **Applicability**: All AI coding agents, pair programmers, and autonomous subagents  
> **Workspace**: Pak-All-Tests (Laravel 13 • Inertia 3 • Vue 3.5 • PostgreSQL 16+ with pgvector & ltree)

---

## ⚡ SYSTEM INITIALIZATION PROTOCOL (Mandatory Step 0)

Before executing ANY file edit, running modifying terminal commands, generating new features, or presenting solutions to the user, you MUST consult the governance files according to the **Load Tiers** below.

### 1. Core Operational Governance (`.agent/`)
1. [`.agent/AGENT.md`](file:///.agent/AGENT.md) — Detected technology stack, runtime specs, and mandatory auto-update protocol.
2. [`.agent/ARCHITECTURE.md`](file:///.agent/ARCHITECTURE.md) — Complete reverse-engineered architectural blueprint of this codebase (directories, models, services, controllers, Filament resources, jobs).
3. [`.agent/PATTERNS.md`](file:///.agent/PATTERNS.md) — Standardized Laravel Feature Lifecycle (FormRequest → Thin Controller → Service/Transaction → Inertia/SDUI → Pest Test), loop limits, and anti-patterns.
4. [`.agent/MEMORY.md`](file:///.agent/MEMORY.md) — Active rolling memory log of project decisions, known environment quirks, and hardening directives.
5. [`.agent/SYSTEM_RULES.md`](file:///.agent/SYSTEM_RULES.md) — Cross-cutting engineering principles, senior architect mandate, YAGNI, zero assumptions, and clean code standards.
6. [`.agent/UI_REFERENCE.md`](file:///.agent/UI_REFERENCE.md) — Persistent visual memory, design tokens, typography, and reference screenshot registry.
7. [`FEATURES_BACKLOG.md`](file:///FEATURES_BACKLOG.md) — Master product feature backlog, strategic demand matrix, and architectural specifications.

### 2. Domain & Sub-Architectural Rulebook (`.agent/rules/`)
* [`QA.md`](file:///QA.md) — Mandatory forensic line-by-line code review, 6 defect hunting categories, and real-user walkthroughs.
* [`.agent/rules/domains.md`](file:///.agent/rules/domains.md) & [`.agent/rules/domain_flows.md`](file:///.agent/rules/domain_flows.md) — PostgreSQL `ltree` hierarchies, SDUI page layout compilation.
* [`.agent/rules/content_modules.md`](file:///.agent/rules/content_modules.md) & [`.agent/rules/question_bank.md`](file:///.agent/rules/question_bank.md) — Content nodes, MCQ players, test session state machines, answer-key scrubbing.
* [`.agent/rules/user_subscriptions.md`](file:///.agent/rules/user_subscriptions.md) & [`.agent/rules/security_gating.md`](file:///.agent/rules/security_gating.md) — EasyPaisa / JazzCash payment gating, TRX verification queue, route guards.
* [`.agent/rules/database_performance.md`](file:///.agent/rules/database_performance.md) — Sub-millisecond queries, indexing strategies, zero N+1, Sub-200ms transaction law, Cache-Pointer pattern.
* [`.agent/rules/testing_validation.md`](file:///.agent/rules/testing_validation.md) — Pest test execution, `DatabaseTransactions`, zero dynamic properties.
* [`.agent/rules/ai_tutor.md`](file:///.agent/rules/ai_tutor.md) — Socratic AI Tutor, SSE streaming protocol, intent routing, PMA/ISSB/CSS/MDCAT institutional domain standards.
* [`.agent/rules/ui_design.md`](file:///.agent/rules/ui_design.md) — Academic Rigor & Military Precision, anti-AI visual fingerprint, nested radius, no dead clicks.
* [`.agent/rules/mistakes.md`](file:///.agent/rules/mistakes.md) — Anti-patterns and 11 historical root-cause analyses (RCA).

### 3. Dedicated Agent Governance (MUSE)
* [`.agent/MUSE.md`](file:///.agent/MUSE.md) — Anti-patterns, code lifecycle discipline, and architectural guardrails dedicated specifically for Muse (G U J E W E L). Must be loaded and adhered to whenever Muse writes, modifies, or reviews code in this repository.

---

## 📚 Load Tiers (Read Discipline)

Not every governance file is read on every turn. Load only what the task requires to conserve context and prevent rule confusion.

**Tier 1 — ALWAYS loaded on every turn:**
- `AGENTS.md` (this file)
- `.agent/MUSE.md` (MANDATORY for Muse / G U J E W E L on every turn)

**Tier 2 — Loaded when the task involves writing, reviewing, refactoring code, or conducting QA:**
- `.agent/SYSTEM_RULES.md`
- `.agent/PATTERNS.md`
- `.agent/rules/mistakes.md`
- `QA.md` (Forensic line-by-line inspection checklist)

**Tier 3 — Loaded only when the task touches the corresponding subsystem:**
- Touching `ltree` or domain hierarchy → `.agent/rules/domains.md`
- Touching SDUI layout or `ui_schema` → `.agent/rules/domain_flows.md` + `.agent/rules/sdui_blueprints.md`
- Touching content modules or uploads → `.agent/rules/content_modules.md`
- Touching MCQ banks, attempts, or grading → `.agent/rules/question_bank.md`
- Touching payments, subscriptions, or receipts → `.agent/rules/user_subscriptions.md`
- Touching route guards, `is_active` gating, or `SubscriptionManager` → `.agent/rules/security_gating.md`
- Touching queries, indexes, or migrations → `.agent/rules/database_performance.md`
- Writing or modifying tests or performing QA sign-off → `.agent/rules/testing_validation.md` + `QA.md`
- Touching AI tutor, LLM calls, or SSE → `.agent/rules/ai_tutor.md`
- Touching Vue/SDUI components or visual design → `.agent/rules/ui_design.md` + `.agent/UI_REFERENCE.md`

**Tier 4 — On-demand reference only (never loaded proactively):**
- `.agent/ARCHITECTURE.md` (system blueprint — read when you need the full picture)
- `.agent/MEMORY.md` (rolling log — read when checking known quirks before touching an affected subsystem)
- `.agent/AGENT.md` (stack + runtime specs — read when onboarding a new subsystem)

If you are unsure which tier a file belongs to, err on the side of loading fewer files, not more.

---

> Persona, radical simplicity, zero assumptions, and clean code hygiene — see [`.agent/SYSTEM_RULES.md`](file:///.agent/SYSTEM_RULES.md).

---

## 📋 Mandatory Pre-Conclusion Checklist

Before marking any task as complete or returning the final response to the user, you MUST execute the following 4-step validation sequence:

- [ ] **1. Static Audit for Orphan Code**: Verify that all created classes, controllers, models, and helper functions are properly imported, bound in container/routes, and contain zero unused `use` statements.
- [ ] **2. Automated Test Suite Execution**:
  Run tests to ensure zero regressions:
  ```bash
  ./vendor/bin/pest
  # or
  php artisan test
  ```
  All tests must exit with code `0`.
- [ ] **3. Code Style & Pint Validation**:
  Run Pint to verify formatting against Laravel standard:
  ```bash
  ./vendor/bin/pint --dirty --test
  # or format:
  ./vendor/bin/pint --dirty
  ```
- [ ] **4. Autonomous Governance Auto-Update**:
  If you added, moved, or deleted any models, services, routes, migrations, Filament resources, or discovered environment quirks, you MUST immediately update:
  - [`.agent/ARCHITECTURE.md`](file:///.agent/ARCHITECTURE.md)
  - [`.agent/MEMORY.md`](file:///.agent/MEMORY.md)

### Extended Pre-Conclusion Gate (13-Point Self-Audit)

The 4-step checklist above is the minimum bar. Before declaring ANY task done, you MUST also confirm the following 13 points, printing each with ✅/❌ and the evidence (command output summary or file path). If any line fails, continue working — do not conclude.

1. **Compilation Integrity** — `pint --parallel --test`, and `phpstan analyse` pass.
2. **Full CI Chain** — `composer test` passes fully (config clear → Pint → PHPStan → Pest).
3. **Bundle Integrity** — `npm run build` passes; if SSR is in scope, `npm run build:ssr` passes.
4. **Query Plan Sanity** — No new N+1 queries on changed endpoints (verify via query log). No new unindexed `WHERE` / `ORDER BY`.
5. **Authorization Re-Check** — No new trust in client-supplied IDs without a `SubscriptionManager` or policy re-check.
6. **Config Hygiene** — No new `env()` call outside `config/` (breaks on config cache).
7. **Test Coverage** — No new untested branch; every changed endpoint has happy / unauthorized / validation / edge-path tests.
8. **Log Hygiene** — No PII, answer keys, secrets, or full request bodies emitted to logs.
9. **Job Hygiene** — Every dispatched job has a `failed()` method and a DLQ destination.
10. **HTTP Hygiene** — Every external call has a timeout and a retry policy.
11. **Blast Radius Declared** — If the change touches `SubscriptionManager`, `AiTutorService`, `SduiPayloadTransformer`, or the Fortify pipeline, a Blast Radius block is present in the response.
12. **Memory Updated** — MEMORY.md has a new rolling entry in the mandated format (Action / Impact / Files / Blast Radius / Rollback Path / Lessons).
13. **Forensic Real-User Code QA** — Never rely solely on unit tests. Confirmed that every line of modified code was audited against the 6 Defect Hunting Categories (logical bugs, silent bugs, loud bugs, performance bugs, incomplete logic, client-server disconnects) and tested across real-user execution paths (see [`QA.md`](file:///QA.md) and [`.agent/rules/testing_validation.md § 4`](file:///.agent/rules/testing_validation.md#4-forensic-qa--the-real-user-verification-law-canonical-rule)).

---

## 🎯 Blast Radius Declaration Protocol

Before editing ANY file that touches a critical subsystem, you MUST emit a **Blast Radius** block at the top of your response:

**Critical Subsystems (any edit requires a Blast Radius block):**
- `App\Services\SubscriptionManager`
- `App\Services\Ai\AiTutorService` and `AiCompletionService`
- `App\Services\SduiPayloadTransformer` and `SduiSchemaValidator`
- Fortify pipeline (`app/Actions/Fortify/**`, `app/Providers/FortifyServiceProvider.php`, `config/fortify.php`)
- Any migration altering an existing column, dropping a column, or changing a constraint
- Any webhook controller under `App\Http\Controllers\**Webhook**`
- Any change to queue names, worker configuration, or job dispatch signatures

**Required Blast Radius block format:**

```
### Blast Radius
- Affected Models: ...
- Affected Controllers / Actions: ...
- Affected Services: ...
- Affected Jobs / Queues: ...
- Affected Inertia Props: ...
- Affected Vue Components: ...
- Affected Routes: ...
- Migration Required: yes/no (with rollback command)
- Rollback Plan: <explicit steps>
- Tests That Must Re-Run: <list>
```

If you cannot fill a row, write `none` — do not omit the row. An empty row is information; a missing row is a governance breach.

---

## 🔁 Regression Reflex

Any edit touching a file already referenced in `MEMORY.md` entries 1–10 (or their successor hardening directives) triggers an automatic expanded verification:

- If the edit touches **DB queries, migrations, or connection config**: re-run the full `composer test` chain, plus a manual query-log inspection of the changed endpoint.
- If the edit touches **queue names, job signatures, or worker config**: re-run the full `composer test` chain, plus a dry-run dispatch of the affected job in a local environment.
- If the edit touches **webhook signature verification, idempotency logic, or payment state machine**: re-run the full `composer test` chain, plus a replay test simulating duplicate delivery.
- If the edit touches **Fortify pipeline or session lifecycle**: re-run the full `composer test` chain, plus manual 2FA / passkey / password-reset smoke test.

You may not "skip" the Regression Reflex because the diff "looks small." Small diffs to critical subsystems are the highest-risk category of change.

---

## 🧊 Idempotency Mandate

Every operation in the following categories MUST design and document an idempotency key before writing implementation code:

| Operation | Idempotency Key Source | Storage |
| :--- | :--- | :--- |
| EasyPaisa / JazzCash webhook | Gateway-provided `transaction_id` | `webhook_events` table (unique index) |
| WhatsApp notification dispatch | `hash('sha256', $user_id . $template . $context_hash)` | `notification_log` table |
| Subscription activation | `user_id` + `domain_id` + `payment_reference` | `user_subscriptions` composite index |
| MCQ answer submission | `user_id` + `module_id` + `question_id` + `attempt_id` | `mcq_attempts` unique constraint |
| Vectorization job dispatch | `content_module_id` + `content_hash` | `vectorization_log` table |
| Domain knowledge embedding | `content_hash` of source text | `domain_knowledge_embeddings.content_hash` |

If the idempotency key already exists on receipt, the operation returns the **previous result** — it does not re-execute side effects, does not re-send notifications, and does not double-activate subscriptions.

---

## 🧨 Failure-Mode Awareness Matrix

The following failure modes are known to occur on this exact stack. Detection and mitigation are mandatory for any change that touches the associated subsystem.

| # | Failure Mode | Detection | Mandated Mitigation |
| :--- | :--- | :--- | :--- |
| 1 | PgBouncer deallocates prepared statements (`SQLSTATE[42P05]`) | Error log spike on port 6543 | `PDO::ATTR_EMULATE_PREPARES => true` must remain set (see MEMORY §2) |
| 2 | Nginx FastCGI buffer overflow → HTTP 502 during Inertia hydration | 502 spike on large payload pages | Keep `fastcgi_buffers 4 256k` and `fastcgi_buffer_size 128k` (see MEMORY §6) |
| 3 | Azure container daemon ignores config edits | Upload limits revert after deploy | Startup script must re-copy `default` + `uploads.ini` and reload (see MEMORY §3) |
| 4 | Supabase WAN latency spikes (50–150ms/query) | Slow query log / APM | Enforce Sub-200ms transaction law, bulk inserts, `chunkById` |
| 5 | Filament Livewire payload corruption on large modals | `CorruptComponentPayloadException` | Cache-Pointer pattern (`$batchId` only in Livewire state) |
| 6 | EasyPaisa / JazzCash webhook replay | Duplicate `transaction_id` in logs | HMAC verify + idempotency table + unique index |
| 7 | WhatsApp Cloud API timeout blocking web workers | Job queue depth spike | Redis queue + 3s HTTP timeout + `failed()` DLQ |
| 8 | SSE connection drops on mobile networks | Abrupt `text/event-stream` terminations | Client-side auto-reconnect with `Last-Event-ID` resume |
| 9 | Client clock drift during exam | Timer mismatch reports | Server-authoritative timer, client displays server delta only |
| 10 | Urdu / RTL text truncation in DB writes | Garbled strings in question bank | `utf8mb4_unicode_ci` fallback + length-safe truncation (see MEMORY §4) |

---

## 📡 Observability Minimum Contract

Every new endpoint, job, and webhook handler MUST emit at least one structured log line with the following fields:

```json
{
  "correlation_id": "uuid-v4",
  "user_id": 12345,
  "domain_slug": "css-competitive",
  "event": "mcq.submitted",
  "duration_ms": 87,
  "status": "success"
}
```

- **`correlation_id`** — generated at the outermost boundary (middleware or job dispatch) and propagated through all nested calls.
- **`user_id`** — nullable for anonymous flows; never include email or phone.
- **`domain_slug`** — nullable; required when the operation is domain-scoped.
- **`event`** — dotted namespace, always lower-case, always present-tense verb (`exam.started`, `webhook.verified`, `subscription.activated`).
- **`duration_ms`** — integer milliseconds; required for every HTTP request and every job execution.
- **`status`** — one of `success`, `partial`, `failure`, `retry`.

**Banned in logs:** email, phone number, password, TRX ID, receipt URL, MCQ correct-answer index, full request body, full response body of external APIs.

Every SSE endpoint must additionally log: connection open, connection close, disconnect reason, and bytes streamed. Every queue worker must expose a metrics endpoint or emit a heartbeat log every 60 seconds.

---

## 🚀 Deploy & Rollback Safety

Every migration and every deployment-affecting change MUST satisfy the following before being considered complete:

1. **Zero-Downtime Discipline** — Column additions and new indexes ship in release N. Backfills run as queued jobs. Column drops and code removal ship in release N+1 or later. Never combine a drop with the code that stopped using it.
2. **Backfill Jobs Must Be Resumable** — Long-running backfills use `chunkById`, are safe to re-run, and record progress in a `migration_progress` table.
3. **Feature Flags for Risky Changes** — Any new code path touching payment, AI, or subscriptions must be behind a config flag that can be disabled without a redeploy.
4. **Rollback Command Mandatory** — Every migration ships with a `down()` method that actually reverses the change, or an explicit `// irreversible: <reason>` comment. No silent no-op `down()` methods.
5. **Smoke Test After Deploy** — After every production deploy: hit `/up` (health), submit one MCQ on a test account, dispatch one vectorization job, verify one webhook signature against a replay fixture.
6. **Cache Clear Discipline** — After deploy: `php artisan config:clear && php artisan route:clear && php artisan view:clear`. Never `cache:clear` in production without a warm-up plan — it stampedes the DB.

---

## 🧠 Memory Update Contract (Extended Format)

When updating [`.agent/MEMORY.md`](file:///.agent/MEMORY.md), every new rolling entry MUST follow this format:

```
### Entry N — YYYY-MM-DD: <Short Title>
- **Action**: <one sentence describing what changed>
- **Impact**: <one sentence describing the effect on the system, users, or performance>
- **Files Changed**: <comma-separated list, or "multiple" with a glob>
- **Blast Radius**: <affected models, controllers, jobs, or "isolated">
- **Rollback Path**: <explicit command or "no rollback required — additive">
- **Lessons**: <optional — one sentence if this was a bug fix or a near-miss>
```

**Entry numbering:** strictly monotonically increasing. Entries 1–10 are historical and **immutable** — do not edit, reorder, or delete them.

**Rolling window:** once the log exceeds 10 active entries, older entries may be archived to `.agent/MEMORY_ARCHIVE.md` — never deleted, never edited.

---

## 🔗 Cross-File Coherence Rule

When adding a new rule to any governance file:

1. **Check first, write second** — Re-read the target file (and its siblings in `.agent/` and `.agent/rules/`) to confirm the rule doesn't already exist.
2. **Cross-reference, don't duplicate** — If a rule belongs in two places, write it in one and reference it from the other (`see .agent/PATTERNS.md §4`).
3. **No contradictions** — A new rule may not soften, weaken, or contradict an existing rule. If it appears to, either the existing rule is wrong (flag it, don't silently override) or the new rule is wrong.
4. **Additions are append-only at the section level** — You may append subsections under existing headings. You may not renumber, rename, or reorder existing sections.
5. **Every rule must be actionable and falsifiable** — A reader must know exactly what to do and a reviewer must be able to verify compliance. Ban hedging ("might," "could," "consider") and ban filler ("it's important to note").
6. **Tone discipline** — Match the existing voice: authoritative, disciplined, declarative. No motivational filler. No generic SaaS-speak.

If you are unsure whether an addition clashes with existing content, **stop and re-read the source file** before writing. Silent contradictions are the worst possible failure mode for this governance layer.

---

## ✅ Definition of Done

A task is Done when and only when:

- All 4 items of the **Mandatory Pre-Conclusion Checklist** are ✅.
- All 12 items of the **Extended Pre-Conclusion Gate** are ✅ with printed evidence.
- A **Blast Radius** block is present if any critical subsystem was touched.
- The **Regression Reflex** has been honored for every subsystem listed in it.
- The **Idempotency Mandate** is satisfied for every retryable operation added.
- The **Observability Minimum Contract** is satisfied for every new endpoint, job, and webhook.
- The **Deploy & Rollback Safety** rules are satisfied for every migration added.
- [`.agent/MEMORY.md`](file:///.agent/MEMORY.md) has a new rolling entry in the **Extended Format**.
- No governance file was contradicted; any new rule was added via the **Cross-File Coherence Rule**.

Anything less is not Done. Do not conclude your turn before all of the above is true.
