# .agent/AGENT.md — Technology Stack, Market Standards & Autonomous Loop

> **Status**: ACTIVE CORE DIRECTIVE  
> **Governance Level**: Strict Architectural Mandate

---

## 1. Detected Project Stack & Runtime Specifications

| Layer | Technology | Exact Version / Configuration | Notes |
| :--- | :--- | :--- | :--- |
| **Runtime** | PHP | `^8.3` (Runtime: PHP 8.4.x) | Strict typing, typed properties, match expressions |
| **Framework** | Laravel | `^13.17` (Laravel 12 / 13 monolith) | Modern bootstrap pipeline (`bootstrap/app.php`) |
| **Database** | PostgreSQL | PostgreSQL 16+ (Supabase / RDS) | Extensions: `ltree` (category hierarchy), `vector` (1536-dim HNSW) |
| **Admin Panel**| Filament | `^5.0` (`filament/filament`) | Resources, Repeaters, Custom Schemas & Actions |
| **Frontend** | Vue 3 + Inertia | Vue `^3.5.13` • `@inertiajs/vue3: ^3.0.0` | Pure JavaScript `<script setup>` in Vue components |
| **Styling** | Tailwind CSS 4 | `@tailwindcss/vite: ^4.1.11` | Pure CSS theme variables, zero legacy Tailwind config |
| **UI Primitives**| Reka UI & Lucide | `reka-ui: ^2.9.8` • `@lucide/vue` | Accessible headless components, Radix Vue primitives |
| **Auth** | Fortify & Passkeys | `laravel/fortify: ^1.37.2` • `@laravel/passkeys`| 2FA, WebAuthn Passkeys, Teams multi-tenancy |
| **Testing** | Pest 4 | `pestphp/pest: ^4.7` • `pest-plugin-laravel`| Feature & Unit test suites, `DatabaseTransactions` |
| **Code Style** | Laravel Pint | `laravel/pint: ^1.27` (`preset: "laravel"`) | Zero code style divergence allowed |
| **Static Analysis**| Larastan | `larastan/larastan: ^3.9` | Level 6 PHPStan analysis |

---

## 2. Pakistani Competitive Exam Domain & Regional Standards (PK)

When building candidate portals, assessment engines, domain landing pages, checkout flows, or AI tutoring components, strictly adhere to the following Pakistani regional, linguistic, and domain standards:

### A. Target Audience & Institutional Domain Scope
* **Armed Forces Initial Tests**: PMA Long Course, PAF GD(P), Pak Navy (AS&RC test protocols: Verbal, Non-Verbal Intelligence, and Academic sub-tests).
* **ISSB (Inter-Services Selection Board)**: 4-day psychological and outdoor evaluation (WAT, SRT, TAT, GTO Tasks, Deputy President interview).
* **Civil Services & Provincial Commissions**: CSS Competitive Examination (FPSC), PMS, PPSC, SPSC, KPPSC, BPSC.
* **Medical & Professional Entry**: MDCAT (PMDC curriculum), ECAT.
* **Tone**: *"Academic Rigor & Military Precision"* — Authoritative, disciplined, structured, objective, and distraction-free.

### B. Language & Typography Standards
1. **Bilingual Capability (English & Roman Urdu)**:
   - Primary test questions, official syllabi, and administrative documentation must be presented in clear, formal English.
   - The Socratic AI Tutor must understand and seamlessly communicate in Roman Urdu (e.g., *"Formula ko dubara dekhein, agar mass constant ho tou force barhne par acceleration pe kya asar parega?"*) when candidates converse in Roman Urdu.
2. **5-Second Clarity (Candidate UX Standard)**:
   - Test instructions, timer limits, negative marking warnings, and pricing blocks must be instantly comprehensible to candidates in under 5 seconds.
   - Ban engineering jargon (`payload`, `SDUI schema`, `polymorphic key`) from candidate interfaces.
   - Clearly state scoring schemes (e.g. *"+1 Mark per correct answer, 0.25 Negative marking applies"*).

### C. Formatting & Regional Conventions
* **Phone Masking**: Pakistani mobile number format: `+92 (3XX) XXXXXXX` or `03XX-XXXXXXX`, cleanly normalized to E.164 (`923XXXXXXXXX`) on database persistence.
* **Date & Time**: Date formatting `DD.MM.YYYY` or `DD/MM/YYYY` (e.g. `19.09.2026`) and Pakistan Standard Time (PKT, UTC+5) for exam deadlines and live sessions.
* **Currency Formatting**: Format Pakistani Rupees with official standard symbol or prefix: `PKR 2,500` or `₨ 2,500` (comma thousands separator, never bare ambiguous symbols).
* **Regional Payment Gateways**:
  - *Current Active Flow*: Candidate manual payment via **EasyPaisa**, **JazzCash**, or **Direct Bank Transfer** with TRX ID and receipt screenshot upload.
  - *Verification State Machine*: Transitions via `SubscriptionManager` (`pending` $\rightarrow$ `active` / `rejected` / `expired`) reviewed in Filament admin queue.
  - *Post-MVP Merchant Webhooks*: Direct EasyPaisa / JazzCash IPN webhook integration (`POST /api/webhooks/easypaisa` with HMAC-SHA256 signature verification).
  - *Candidate Messaging*: Automated notifications via **Meta WhatsApp Cloud API** to candidate's Pakistani mobile number (`+92...`) for subscription confirmations, exam reminders, and test status alerts.


---

## 3. Strict Anti-AI Design Guidelines

1. **No Generic Violet Gradients**: Ban saturated violet-to-cyan mesh gradients (`bg-gradient-to-r from-purple-600 to-indigo-600`) that scream generic template code. Use deliberate, high-contrast, purpose-built palettes (emerald, deep zinc, slate, amber, military olive).
2. **No Cardification of Plain Text**: Do NOT wrap every isolated paragraph or sentence in an elevated white card with heavy drop shadows. Group related functional items into disciplined semantic containers with subtle border strokes (`border border-border/60`).
3. **Exactly One Solid Action CTA per View**: Every screen, section, or modal must have exactly **ONE** primary solid button. Secondary actions must be ghost, outline, or text links. Prevent visual decision paralysis.
4. **Zero Dead Clicks**: Every button, navigation item, tab, and card must execute a concrete action, modal, or route. Never ship placeholder buttons with `href="#"` or unhandled `@click=""`.

---

## 4. Mandatory Auto-Update Loop

Whenever you perform work that introduces, refactors, or removes:
- Eloquent Models, Migrations, or Table Columns
- HTTP Routes, Middleware, or Controllers
- Services, AI Engines, or Tool Callers
- Filament Admin Resources or Pages
- SDUI Components or Vue Pages

You MUST immediately update:
1. [`.agent/ARCHITECTURE.md`](file:///.agent/ARCHITECTURE.md) — Document the updated system structure.
2. [`.agent/MEMORY.md`](file:///.agent/MEMORY.md) — Append a new entry to the rolling 10-entry memory log.

### A. Pre-Conclusion Gate (12-Point Self-Audit)
Before declaring ANY task, feature, bug fix, or refactor complete, you MUST audit against every item in this 12-point gate. If any check fails, do NOT exit — continue working until all 12 pass:

1. **Compilation**: Every touched file passes static type & lint checks (`vue-tsc --noEmit`, `./vendor/bin/pint --parallel --test`, `./vendor/bin/phpstan analyse`).
2. **Test Suite**: `./vendor/bin/pest` passes fully (exit code 0, zero regressions).
3. **Frontend Build**: `npm run build` (and `npm run build:ssr` when SSR is touched) exits with code 0.
4. **Zero New N+1s**: Eager-load relations (`with([...])`) on all new/modified endpoints; verify query count under test.
5. **Indexing Discipline**: Every new `WHERE`, `ORDER BY`, or foreign key column possesses an explicit, intentional database index.
6. **Zero Client Trust**: All entity IDs (`domain_id`, `module_id`, `question_id`) re-validated server-side via `SubscriptionManager` or dedicated Policies.
7. **Secrets & Config**: Zero raw `env()` calls outside `config/` files.
8. **Branch Coverage**: Automated tests verify happy path, unauthenticated path (401), unauthorized path (403), validation failures (422), and edge/boundary conditions.
9. **Log Hygiene**: Zero PII (passwords, phone numbers, CNIC/TRX IDs) and zero MCQ correct answer keys leaked into logs or frontend payloads.
10. **Queue Safety**: Every queued Job implements `public function failed(?Throwable $e): void` and targets an isolated, prioritized queue.
11. **External Resilience**: Every outbound HTTP call (`Http::...`) enforces an explicit timeout ($\le 5\text{s}$), retry policy, and graceful degradation fallback.
12. **Rolling Memory Synchronization**: `.agent/MEMORY.md` updated with the mandated structured entry format.

### B. The Regression Reflex
If an edit touches ANY file or concept documented in [`.agent/MEMORY.md`](file:///.agent/MEMORY.md) entries 1–10 (PgBouncer config, Nginx buffers, Azure container files, Fortify redirects, Socratic SSE streaming, SDUI transformers, Livewire modals, bulk insert transactions):
1. Immediately re-run the specific Pest feature test suite for that subsystem.
2. If the edit touches database queries, migrations, queue jobs, or webhook handlers, execute the complete validation sequence: `php artisan config:clear && ./vendor/bin/pest && npm run build`.

### C. Blast-Radius Declaration Protocol
Before creating any migration, modifying queue schemas, changing webhook signatures, or altering shared middleware, output an explicit **Blast Radius** block listing every affected artifact:
```markdown
> **Blast Radius**:
> - Models: [...]
> - Controllers / Actions: [...]
> - Queued Jobs / Listeners: [...]
> - Inertia Props / Vue Components: [...]
> - External Integrations (EasyPaisa / WhatsApp / OpenAI): [...]
```

### D. Idempotency Requirement
Every write that can be retried MUST be idempotent:
- Any task that dispatches a job, executes an external payment/notification API, or writes to a webhook table must define and persist an idempotency key (e.g. `hash('sha256', $trxId . $provider)`) before side-effects occur.
- If a webhook or job re-executes with the same key, it must return HTTP 200 / success immediately without re-executing mutations.

### E. Rolling Memory Update Contract
All new entries appended to [`.agent/MEMORY.md`](file:///.agent/MEMORY.md) MUST adhere strictly to this schema:
- **Action**: Architectural, infrastructure, or schema decision executed.
- **Impact**: Quantifiable performance, security, or resilience delta.
- **Files Changed**: Exact file paths modified.
- **Blast Radius**: Upstream and downstream components impacted.
- **Rollback Path**: Concrete terminal command or Git reversal procedure.
- **Lessons**: Hard-won heuristics or anti-patterns identified.

---

## 5. Production Failure-Mode Awareness & Mitigation Matrix

Every engineer and autonomous agent operating on this codebase must know the 10 canonical failure modes of our deployment stack and their mandated remediations:

| # | Production Failure Mode | Root Mechanism on This Stack | Mandated Detection & Monitoring | Mandated Architectural Mitigation |
| :- | :--- | :--- | :--- | :--- |
| **1** | **PgBouncer Deallocated Prepared Statements** | Supabase port 6543 pooler reassigns backend connections mid-session; PDO crashes on missing prepared statements (`SQLSTATE[42P05]`). | Sentry error alerts on `42P05` or `08P01`; health check DB probe. | Enforce `PDO::ATTR_EMULATE_PREPARES => true` in `config/database.php`. Never disable emulated prepares on pooled connections. |
| **2** | **Nginx FastCGI Buffer Overflow** | Inertia SSR or large SDUI page payloads exceed default 4KB/8KB FastCGI buffer; Nginx returns HTTP 502 Bad Gateway. | Nginx `error.log` matching `"upstream sent too big header while reading response header from upstream"`. | Enforce `fastcgi_buffers 4 256k; fastcgi_buffer_size 128k;` in `default`. Remove `AddLinkHeadersForPreloadedAssets` middleware. |
| **3** | **Azure Container Daemon Stale Lifecycle** | Editing `uploads.ini` or Nginx templates inside container without reloading running daemons leaves active PHP-FPM workers un-updated. | Discrepancy between `ini_get('upload_max_filesize')` output and container template. | Azure startup script must copy templates and reload daemons: `service nginx reload && kill -USR2 $(pidof php-fpm)`. |
| **4** | **Supabase WAN Latency Spikes** | Remote DB in foreign region adds 50–150ms WAN latency per query. Loops of single queries exhaust HTTP worker pool. | AppServiceProvider slow query logger: Log warning on queries exceeding 100ms or transactions $>200\text{ms}$. | Ban queries in loops. Execute multi-row bulk `insert` and `insertOrIgnore`. Batch relationship lookups into single queries. |
| **5** | **Filament Livewire Payload Corruption** | Passing collections or arrays with $>20$ items into modal component properties exceeds Livewire snapshot limits. | Livewire client-side crash: `CorruptComponentPayloadException` or browser freeze during exam authoring. | **Cache-Pointer Pattern**: Write dataset to Redis under UUID key (`import_batch_{uuid}`). Store ONLY the 36-byte UUID in Livewire state. |
| **6** | **EasyPaisa / JazzCash Webhook Replay** | Gateway retries IPN webhook on transient timeout; un-guarded handler activates subscription or increments credit twice. | Sentry alerts on duplicate subscription activation attempts for identical `trx_id`. | Persist `idempotency_key` via `webhook_events` table with unique constraint. Check presence inside transaction before mutations. |
| **7** | **WhatsApp Cloud API Outbound Timeouts** | Meta Graph API experiences latency spikes; synchronous notification dispatch blocks candidate HTTP request thread. | Guzzle timeout exception during candidate checkout or exam completion. | Never send WhatsApp notifications synchronously. Dispatch `SendWhatsAppNotificationJob` to isolated `notifications` queue with 3s timeout. |
| **8** | **SSE Connection Drops on Mobile Networks** | Candidate taking test or chatting with Socratic AI on 3G network suffers silent TCP drop; client hangs waiting for stream chunks. | Client-side SSE timeout listener triggering after 15s of zero packet transmission. | Server transmits `: heartbeat\n\n` comment every 15s. Client implements auto-reconnect with `Last-Event-ID` tracking. |
| **9** | **Mid-Exam Clock Drift (Client vs Server)** | Candidate device clock is desynchronized or manipulated to bypass timed test restrictions. | Client-submitted `time_taken` conflicting with server session creation timestamp. | **Server is the clock. Client is a hint.** Test session start and deadline stored in UTC on server. Expiry enforced server-side. |
| **10**| **Urdu / RTL Text Truncation & Stripping** | Ingestion regexes stripping non-ASCII characters or standard string methods truncating multibyte Urdu characters mid-glyph. | Urdu questions rendering as `???` or crashing deduplication hashes into empty strings (`""`). | Use `mb_strtolower()`, `mb_substr()`, and Unicode regex flags (`\s+/u`). Database tables must strictly maintain UTF-8 collation. |

---

## 6. Observability Discipline & Monitoring Contract

### A. Structured Logging Contract
All application logs MUST be emitted as single-line JSON with standardized context keys. Raw string logs (`Log::info("Something happened")`) are banned.

```php
Log::info('Exam submission graded', [
    'correlation_id' => request()->header('X-Correlation-ID', (string) Str::uuid()),
    'event' => 'exam.graded',
    'user_id' => $user->id,
    'domain_slug' => $domain->slug,
    'module_id' => $module->id,
    'duration_ms' => $gradingDurationMs,
    'status' => 'success',
]);
```

**Banned in Logs**:
- Passwords, passkey credentials, session tokens.
- Candidate CNIC, mobile numbers, payment transaction IDs (TRX).
- **MCQ Answer Keys**: Never log correct answer indices or letters.

### B. Health-Check Endpoint Contract
The application exposes a lightweight, unauthenticated health probe at `GET /api/health` returning JSON:
- `database`: Executes `SELECT 1` with a 2-second timeout.
- `redis`: Executes `Redis::ping()` with a 1-second timeout.
- `storage`: Verifies `Storage::disk('public')->exists(...)`.
- `status`: Returns HTTP 200 `{"status": "healthy"}` if all pass, or HTTP 503 `{"status": "unhealthy", "failing": [...]}` if any fail.

### C. Queue & Endpoint Observability Minimums
- **Queue Workers**: Monitor queue lag, failure count, and job processing duration. Trigger alert if `default` queue latency $>60\text{s}$ or `notifications` queue latency $>30\text{s}$.
- **SSE Endpoints**: Track open stream connections, average time-to-first-token (TTFT $\le 1200\text{ms}$), and mid-stream disconnect rate.
- **Payment Webhooks**: Track incoming volume, signature verification failure rate ($>1\%$ triggers high-priority security alert), and duplicate transaction rate.

---

## 7. Deploy & Rollback Safety Protocols

### A. Zero-Downtime Migration Standards
1. **Additive Schema Changes Only**: New columns MUST be `nullable` or have a safe default value.
2. **Never Drop in the Same Release**: Renaming or dropping columns requires a 3-step release lifecycle:
   - *Phase 1*: Add new column, update code to write to both old and new columns.
   - *Phase 2*: Run background backfill job (`chunkById(100)`) to sync legacy rows.
   - *Phase 3*: Update code to read/write only new column; drop old column in subsequent sprint.
3. **Index Creation Safety**: Add indexes during low-traffic windows. For large tables (>100k rows), verify index creation does not cause lock contention.

### B. Feature Flags for Risky Deployments
- Wrap major algorithmic changes (new grading strategies, AI prompt upgrades, payment gateway webhooks) in feature flags:
  ```php
  if (Feature::active('easypaisa-ipn-v2')) { ... }
  ```
- Flags must be togglable instantly via Filament admin or Redis key without requiring redeployment.

### C. Mandatory Rollback Plan
Every deployment containing a database migration, queue structure change, or external integration modification must document an explicit rollback plan:
```markdown
> **Rollback Plan**:
> - Migration Rollback: `php artisan migrate:rollback --step=1`
> - Queue Drain: `php artisan queue:restart`
> - Feature Flag Reversal: `php artisan feature:disable <flag-name>`
```

