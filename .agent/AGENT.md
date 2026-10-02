# .agent/AGENT.md — Technology Stack, Runtime Specifications & Autonomous Loop

> **Notice**: This is NOT the root `AGENTS.md`. See [`/AGENTS.md`](file:///AGENTS.md) for the mandatory governance entry point.  
> **Status**: ACTIVE CORE DIRECTIVE  
> **Governance Level**: Strict Architectural Mandate  
> **Location**: `.agent/AGENT.md`

---

## 1. Detected Project Stack & Runtime Specifications

| Layer | Technology | Exact Version / Configuration | Notes |
| :--- | :--- | :--- | :--- |
| **Runtime** | PHP | `^8.3` (Runtime: PHP 8.4.x) | Strict typing, typed properties, match expressions |
| **Framework** | Laravel | `^13.17` (Laravel 13 monolith) | Modern bootstrap pipeline (`bootstrap/app.php`) |
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

## 2. Pakistani Competitive Exam Domain Standards

> Canonical domain specifications, institutional syllabus breakdowns (PMA, ISSB, CSS, MDCAT), Roman Urdu Socratic tutoring standards, and regional formatting — see [`.agent/rules/ai_tutor.md § 9`](file:///.agent/rules/ai_tutor.md).

---

## 3. Strict Anti-AI Design Guidelines

> Canonical visual anti-patterns, the 4 Anti-AI Visual Laws, no dead clicks, and mathematical nested radius — see [`.agent/rules/ui_design.md § 1`](file:///.agent/rules/ui_design.md) and [`.agent/UI_REFERENCE.md`](file:///.agent/UI_REFERENCE.md).

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
2. [`.agent/MEMORY.md`](file:///.agent/MEMORY.md) — Append a new entry to the rolling memory log in the mandated extended format.

---

## 5. Production Failure-Mode Awareness & Mitigation Matrix

Every engineer and autonomous agent operating on this codebase must know the 10 canonical failure modes of our deployment stack and their mandated remediations:

| # | Production Failure Mode | Root Mechanism on This Stack | Mandated Detection & Monitoring | Mandated Architectural Mitigation |
| :- | :--- | :--- | :--- | :--- |
| **1** | **PgBouncer Deallocated Prepared Statements** | Supabase port 6543 pooler reassigns backend connections mid-session; PDO crashes on missing prepared statements (`SQLSTATE[42P05]`). | Sentry error alerts on `42P05` or `08P01`; health check DB probe. | Enforce `PDO::ATTR_EMULATE_PREPARES => true` in `config/database.php`. Never disable emulated prepares on pooled connections. |
| **2** | **Nginx FastCGI Buffer Overflow** | Inertia SSR or large SDUI page payloads exceed default 4KB/8KB FastCGI buffer; Nginx returns HTTP 502 Bad Gateway. | Nginx `error.log` matching `"upstream sent too big header while reading response header from upstream"`. | Enforce `fastcgi_buffers 4 256k; fastcgi_buffer_size 128k;` in `default`. Remove `AddLinkHeadersForPreloadedAssets` middleware. |
| **3** | **Azure Container Daemon Stale Lifecycle** | Editing `uploads.ini` or Nginx templates inside container without reloading running daemons leaves active PHP-FPM workers un-updated. | Discrepancy between `ini_get('upload_max_filesize')` output and container template. | Azure startup script must copy templates and reload daemons: `service nginx reload && kill -USR2 $(pidof php-fpm)`. |
| **4** | **Supabase WAN Latency Spikes** | Remote DB in foreign region adds 50–150ms WAN latency per query. Loops of single queries exhaust HTTP worker pool. | AppServiceProvider slow query logger: Log warning on queries exceeding 100ms or transactions $>200\text{ms}$. | Enforce Sub-200ms transaction law (see `.agent/rules/database_performance.md § 2`). Ban queries in loops. Execute multi-row bulk `insert` and `insertOrIgnore`. |
| **5** | **Filament Livewire Payload Corruption** | Passing collections or arrays with $>20$ items into modal component properties exceeds Livewire snapshot limits. | Livewire client-side crash: `CorruptComponentPayloadException` or browser freeze during exam authoring. | **Cache-Pointer Pattern**: Write dataset to Redis under UUID key (`import_batch_{uuid}`). Store ONLY the 36-byte UUID in Livewire state (see `.agent/rules/database_performance.md § 4`). |
| **6** | **EasyPaisa / JazzCash Webhook Replay** | Gateway retries IPN webhook on transient timeout; un-guarded handler activates subscription or increments credit twice. | Sentry alerts on duplicate subscription activation attempts for identical `trx_id`. | Persist `idempotency_key` via `webhook_events` table with unique constraint. Check presence inside transaction before mutations. |
| **7** | **WhatsApp Cloud API Outbound Timeouts** | Meta Graph API experiences latency spikes; synchronous notification dispatch blocks candidate HTTP request thread. | Guzzle timeout exception during candidate checkout or exam completion. | Never send WhatsApp notifications synchronously. Dispatch `SendWhatsAppNotificationJob` to isolated `notifications` queue with 3s timeout. |
| **8** | **SSE Connection Drops on Mobile Networks** | Candidate taking test or chatting with Socratic AI on 3G network suffers silent TCP drop; client hangs waiting for stream chunks. | Client-side SSE timeout listener triggering after 15s of zero packet transmission. | Server transmits `: heartbeat\n\n` comment every 15s. Client implements auto-reconnect with `Last-Event-ID` tracking. |
| **9** | **Mid-Exam Clock Drift (Client vs Server)** | Candidate device clock is desynchronized or manipulated to bypass timed test restrictions. | Client-submitted `time_taken` conflicting with server session creation timestamp. | **Server is the clock. Client is a hint.** Test session start and deadline stored in UTC on server. Expiry enforced server-side. |
| **10**| **Urdu / RTL Text Truncation & Stripping** | Ingestion regexes stripping non-ASCII characters or standard string methods truncating multibyte Urdu characters mid-glyph. | Urdu questions rendering as `???` or crashing deduplication hashes into empty strings (`""`). | Use `mb_strtolower()`, `mb_substr()`, and Unicode regex flags (`\s+/u`). Database tables must strictly maintain UTF-8 collation. |

---

## 6. Observability Discipline & Monitoring Contract

### A. Structured Logging Contract
All application logs MUST be emitted as single-line JSON with standardized context keys:
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
