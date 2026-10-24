# QUIRKS.md — Known Environment Quirks & Infrastructure Constraints

> **Status**: ACTIVE ARCHITECTURAL REFERENCE  
> **Applicability**: All backend feature development, queries, deployments, and security architecture  
> **Workspace**: Pak-All-Tests (Laravel 13 • Inertia 3 • Vue 3.5 • PostgreSQL 16+ with pgvector & ltree)

---

## 1. Database Connection & Remote PostgreSQL Pooler (Supabase)

### A. PgBouncer Deallocated Prepared Statements (`SQLSTATE[42P05]`)
- **Root Mechanism**: Supabase port `6543` operates in transaction pooling mode. The pooler reassigns backend PostgreSQL connections between transactions. Standard PDO prepared statements deallocate upon connection reassignment, triggering fatal `SQLSTATE[42P05]: prepared statement already exists` or deallocation errors.
- **Mandated Mitigation**: Enforce `PDO::ATTR_EMULATE_PREPARES => true` in `config/database.php` under the `pgsql` driver. Never disable emulated prepares on pooled connections.

### B. Remote WAN Latency & Sub-200ms Transaction Law
- **Root Mechanism**: The database is hosted on remote Supabase infrastructure, adding 50–150ms WAN latency per query. Loops of single queries or sequential operations inside a transaction hold database row/table locks across the internet, leading to worker exhaustion and lock contention.
- **Mandated Mitigation**:
  - Enforce the **Sub-200ms Transaction Law**: transactions must never hold open locks for >200ms.
  - Ban single-row query execution inside loops.
  - Use multi-row bulk operations (`insert`, `insertOrIgnore`, `upsert`, `update` via collection keys).
  - Heavy batch processing must use `chunkById(100)`.

### C. Testing Database Transactions
- **Root Mechanism**: `migrate:fresh` or database resetting drops the remote schema and extensions (`ltree`, `vector`).
- **Mandated Mitigation**: All Pest tests must use `Illuminate\Foundation\Testing\DatabaseTransactions`. Never call `RefreshDatabase` or execute `migrate:fresh` in test environments.

---

## 2. Web Server, Buffers & Container Daemons (Azure App Service)

### A. Nginx FastCGI Buffer Overflow (HTTP 502 Bad Gateway)
- **Root Mechanism**: Inertia SSR or large Server-Driven UI (SDUI) page payloads exceed the default 4KB/8KB FastCGI buffer; Nginx returns HTTP 502 Bad Gateway.
- **Mandated Mitigation**:
  - Enforce `fastcgi_buffers 4 256k; fastcgi_buffer_size 128k;` in Nginx site config.
  - Ensure `AddLinkHeadersForPreloadedAssets` middleware is removed to avoid inflating header size beyond proxy limits.

### B. Azure Container Daemon Stale Lifecycle
- **Root Mechanism**: Editing `uploads.ini` or Nginx templates inside Linux containers without reloading running daemons leaves active PHP-FPM workers with stale limits.
- **Mandated Mitigation**: Azure startup scripts must re-copy templates and signal daemons:
  ```bash
  service nginx reload && kill -USR2 $(pidof php-fpm)
  ```

### C. 64MB Upload Limit Synchronization
- **Root Mechanism**: Large past-paper PDFs and syllabus resources trigger HTTP 413 `Request Entity Too Large` if Nginx and PHP-FPM limits diverge.
- **Mandated Mitigation**: Synchronize `client_max_body_size 64M;` in Nginx with `upload_max_filesize = 64M`, `post_max_size = 64M`, and `memory_limit = 256M` in PHP configuration.

---

## 3. Authentication, Guard Isolation & Session Boundaries

### A. Filament Admin Panel Guard Isolation
- **Root Mechanism**: Filament defaults to the `'web'` guard if unspecified. If ephemeral public guest users are authenticated on `'web'`, navigating to `/admin` causes Filament's `canAccessPanel()` to return `false` and trigger an HTTP 403 Forbidden abort, which traps the browser in an unrecoverable redirect loop.
- **Mandated Mitigation**:
  - Filament panel is bound to the isolated `'admin'` guard: `$panel->authGuard('admin')` in `AdminPanelProvider.php`.
  - Custom `App\Http\Middleware\Filament\Authenticate` intercepts guest accounts, logs them out of the panel guard, and cleanly redirects to `/admin/login`.
  - Single Sign-On bridge seamlessly logs in verified non-guest administrators from the `web` guard.

### B. Ephemeral Guest Session Lifecycle
- **Root Mechanism**: Setting `remember: true` on guest log-ins drops persistent 5-year cookies that survive browser restarts.
- **Mandated Mitigation**: Guests must strictly be authenticated with `remember: false`. The `AutoAuthenticateGuest` middleware must explicitly bypass all `/admin*` routes.

---

## 4. UI & Livewire Component Architecture

### A. Filament Livewire Cache-Pointer Pattern
- **Root Mechanism**: Passing collections or arrays with >20 items into Livewire modal properties bloats the component DOM state payload, causing `CorruptComponentPayloadException` or browser freezing during bulk operations.
- **Mandated Mitigation**: Write bulk datasets to Redis/Cache under a UUID key (`import_batch_{uuid}`). Store **only** the 36-byte UUID in Livewire state.

### B. Frontend Language Contract: Pure JavaScript in Vue
- **Root Mechanism**: The frontend build uses Vite with `@inertiajs/vue3` and pure JavaScript `<script setup>` syntax. Introducing TypeScript syntax into `.vue` components breaks Vite compilation.
- **Mandated Mitigation**: All Vue components in `resources/js/` must use pure JavaScript `<script setup>` without TypeScript annotations.

---

## 5. Security, Payments & Real-Time Communications

### A. Webhook Replay & Idempotency Mandate
- **Root Mechanism**: EasyPaisa, JazzCash, and payment gateways retry IPN webhooks upon network timeouts, causing double-crediting or duplicate activations.
- **Mandated Mitigation**:
  - Check gateway `transaction_id` against the `webhook_events` table (with a unique index constraint).
  - Verify HMAC signatures before transaction mutations.

### B. Server-Authoritative Clock & Expirations
- **Root Mechanism**: Candidate client devices may suffer from desynchronization or clock tampering to cheat timed tests.
- **Mandated Mitigation**: **Server is the clock. Client is a hint.** Exam session deadlines and subscriptions are calculated in UTC on the server. Client countdowns are presentation-only.

### C. Multibyte Urdu & RTL String Truncation
- **Root Mechanism**: Single-byte string methods (`substr`, `strlen`) or ASCII-only regexes corrupt Urdu characters mid-glyph or strip valid content.
- **Mandated Mitigation**: Always use multibyte functions (`mb_substr`, `mb_strlen`, `mb_strtolower`) and the `/u` Unicode regex flag. Ensure UTF-8 collation is maintained.
