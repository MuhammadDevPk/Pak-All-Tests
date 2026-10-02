# .agent/PATTERNS.md — Engineering Patterns & Architectural Standards

> **Status**: STRICT IMPLEMENTATION CONTRACT  
> **Applicability**: All backend feature development, frontend components, and refactors

---

## 1. The Standardized Laravel Feature Lifecycle

Every new feature, mutation, or API endpoint must strictly traverse the following 5-phase lifecycle:

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

### Phase 1: Dedicated FormRequest
* **Rule**: Never call `$request->validate()` inside a controller. Always create a dedicated `FormRequest` class under `app/Http/Requests`.
* **Standard**:
  ```php
  namespace App\Http\Requests;

  use Illuminate\Foundation\Http\FormRequest;

  class SubmitAnswerRequest extends FormRequest
  {
      public function authorize(): bool
      {
          return $this->user() !== null;
      }

      public function rules(): array
      {
          return [
              'question_id' => ['required', 'integer', 'exists:question_bank,id'],
              'selected_option_index' => ['required', 'integer', 'min:0', 'max:5'],
              'time_spent_seconds' => ['nullable', 'integer', 'min:0'],
          ];
      }
  }
  ```

### Phase 2: Thin Controller
* **Rule**: Controllers must contain **zero business logic, zero raw SQL, and zero complex transformations**.
* Controllers only:
  1. Receive the validated `FormRequest`.
  2. Invoke the corresponding `Service` or `Action`.
  3. Return an Inertia page render, redirect, or typed `JsonResponse`.
* **Standard**:
  ```php
  namespace App\Http\Controllers;

  use App\Http\Requests\SubmitAnswerRequest;
  use App\Models\ContentModule;
  use App\Services\ExamGradingService;
  use Illuminate\Http\JsonResponse;

  class McqExecutionController extends Controller
  {
      public function __invoke(
          SubmitAnswerRequest $request,
          ContentModule $module,
          ExamGradingService $gradingService
      ): JsonResponse {
          $result = $gradingService->gradeSubmission(
              user: $request->user(),
              module: $module,
              data: $request->validated()
          );

          return response()->json([
              'status' => 'success',
              'result' => $result,
          ]);
      }
  }
  ```

### Phase 3: Service Layer & Atomic Transactions
* **Rule**: All mutations spanning multiple tables, status updates, or third-party interactions must be encapsulated in a dedicated service method wrapped inside `DB::transaction(...)`.
* **Standard**:
  ```php
  namespace App\Services;

  use App\Models\ContentModule;
  use App\Models\User;
  use Illuminate\Support\Facades\DB;

  class ExamGradingService
  {
      public function gradeSubmission(User $user, ContentModule $module, array $data): array
      {
          return DB::transaction(function () use ($user, $module, $data) {
              // 1. Calculate score & evaluation
              // 2. Persist exam attempt record
              // 3. Update candidate domain progress
              // 4. Return clean result array
              return [
                  'score' => 100,
                  'is_correct' => true,
              ];
          });
      }
  }
  ```

### Phase 4: Clean Inertia / SDUI / Resource Response
* **Rule**: Data passed to the frontend must be strictly shaped into clean DTOs, resources, or valid SDUI schemas. Never expose raw password hashes, secret keys, or unneeded database columns to the client.

### Phase 5: Pest Automated Feature Test
* **Rule**: A feature is NOT done until a Pest test covers:
  1. Happy path (200 OK / redirect).
  2. Unauthorized / unauthenticated path (401 / 403 Forbidden).
  3. Validation error path (422 Unprocessable Entity).
  4. Accurate database state assertions (`assertDatabaseHas`).

---

## 2. The "No Dead Clicks" E2E Verification Rule

1. **Zero Fake Affordances**: Every interactive visual element (button, icon, tab, link, card action) MUST be backed by a functional implementation.
2. **Banned Patterns**:
   - `href="#"` or `href="javascript:void(0)"`
   - Empty click handlers: `@click=""` or `@click="() => {}"`
   - Unhandled toast placeholders: `@click="alert('Coming soon')"`
3. **Completeness Standard**:
   - If a feature is scheduled for a later sprint, **do not render the button**.
   - If a button exists, it must either trigger a functional modal, initiate a real API call, or route to an active Inertia view.

### A. Production Anti-Pattern Catalog (10 Cardinal Sins)
1. **Unread Source Additions**: Adding or altering rules in any governance file without reading the entire file first.
2. **Silent Catch-and-Log**: Catching an exception and logging it without re-throwing, degrading gracefully, or presenting explicit feedback.
3. **`env()` Outside `config/`**: Calling `env()` in controllers, models, or services; breaks silently whenever `config:cache` is compiled.
4. **DDL Drops in Tests**: Running `migrate:fresh` or DDL drops against remote database connections; strictly enforce `DatabaseTransactions`.
5. **Database Queries in Presentation**: Querying models or relations inside a Blade directive or Vue computed property.
6. **Filament N+1 Lazy Loading**: Defining table columns that traverse relations without `$table->modifyQueryUsing(fn ($q) => $q->with([...]))`.
7. **Unparameterized `DB::raw()`**: Concatenating variables into raw SQL strings instead of using parameter bindings (`?` or `:binding`).
8. **Livewire Mass Hydration**: Passing collections or arrays with $>20$ items directly to Livewire component state; use the Cache-Pointer pattern (see §5).
9. **Client-Trusted Time**: Trusting client timestamps for exam timers or session expiry; server UTC is the sole clock.
10. **File Extension Naivety**: Validating uploaded documents solely by client extension (`$file->getClientOriginalExtension()`) without verifying MIME magic bytes (`finfo`).

---

## 3. Computational Limits & Loop Architecture

To guarantee sub-millisecond execution and memory-light operation across thousands of concurrent test takers:

### A. Maximum 2 Indentation Levels
Nested code creates cognitive overload and hides performance bugs. Refactor using early returns (guard clauses), helper methods, or collection pipelines:

```php
// ❌ REJECTED: 4 levels of indentation
foreach ($domains as $domain) {
    if ($domain->is_active) {
        foreach ($domain->modules as $module) {
            if ($module->module_type === 'mock_test') {
                $results[] = $module;
            }
        }
    }
}

// ✅ ACCEPTED: Clean pipeline / max 2 levels
return $domains
    ->where('is_active', true)
    ->flatMap->modules
    ->where('module_type', 'mock_test');
```

### B. Ban Inner $O(N^2)$ Scans
* **Never execute database queries inside a loop** (the classic $N+1$ problem).
* **Never execute in-memory collection searches inside a loop** (e.g. `$items->firstWhere('id', $id)` inside a `foreach`).
* **The Associative Map Lookup Standard ($O(1)$)**:
  Always index collections by key before looping:
  ```php
  // ❌ BANNED: O(N * M) quadratic scan
  foreach ($questions as $question) {
      $userAnswer = $answers->firstWhere('question_id', $question->id);
  }

  // ✅ ENFORCED: O(N + M) linear execution via hash-map
  $answersByQuestionId = $answers->keyBy('question_id'); // O(M)
  foreach ($questions as $question) {
      $userAnswer = $answersByQuestionId[$question->id] ?? null; // O(1)
  }
  ```

---

## 4. Remote Database & Connection Pooling Transaction Laws (Sub-200ms Mandate)

This application connects to remote PostgreSQL infrastructure (Supabase PgBouncer on port `6543`). WAN query latency averages 50–80ms per round-trip.

1. **The Sub-200ms Transaction Rule**: Database transactions (`DB::transaction(...)`) must NEVER hold locks for longer than 200 milliseconds.
2. **Ban Sequential Loop Inserts**: Never execute `$model->save()` or `$relation->attach()` inside a `foreach` loop within a transaction.
3. **Multi-Row Bulk Execution**:
   - Collect rows into an array in memory.
   - Execute a single multi-row `Model::insert($data)` or `DB::table(...)->insertOrIgnore($pivotData)` to collapse 100 queries into 1 round-trip.
   - For mass association, use `syncWithoutDetaching()` or `insertOrIgnore` to prevent primary/foreign key conflict crashes.

---

## 5. Filament & Livewire State Hydration Protection (Cache-Pointer Pattern)

Livewire serializes its entire component state into JSON on every network round-trip. Storing collections or large arrays (>20 items) inside component properties causes payload bloat (100KB+), UI freezes, and `CorruptComponentPayloadException`.

1. **The Cache-Pointer Standard**:
   When previewing, generating, or processing multi-record datasets:
   - Store the heavy array in temporary cache:
     ```php
     $batchId = (string) Str::uuid();
     Cache::put("import_batch_{$batchId}", $heavyData, now()->addMinutes(30));
     ```
   - In Livewire/Filament form state, store **ONLY the 36-byte UUID `$batchId`**.
   - Read or paginate from the cache on demand.
2. **Modal Memory Discipline**: Modals must never render more than 10–15 items in a single view window without pagination or virtual windowing.

---

## 6. PostgreSQL Constraint Integrity & Nullable Column Safety

PostgreSQL enforces strict standard SQL constraint evaluations.

1. **The Empty String vs NULL Law**:
   - In PostgreSQL, multiple `NULL` values in a `UNIQUE` column are allowed.
   - Empty string `""` is a distinct character value. Passing `""` to two rows violates the unique constraint!
   - **Rule**: Any nullable unique column (`code`, `slug`, `sku`, `phone`) must strictly sanitize empty strings to `null`:
     ```php
     $data['code'] = filled($input) ? trim($input) : null;
     ```
2. **Pivot Primary Key Safety**:
   - Composite keys (e.g. `module_questions: [module_id, question_id]`) must be guarded using `ON CONFLICT DO NOTHING` via `DB::table(...)->insertOrIgnore(...)` or Eloquent `syncWithoutDetaching()`.

---

## 7. Resilient Text Ingestion, Deduplication & Tokenization

When accepting user-pasted text, past papers, or documents:

1. **Ban Rigid Single Regexes**:
   - Real-world text formats vary widely (inline options `a) b) c) d)` on one line, bracketed `(A)` / `[A]`, uppercase/lowercase, trailing answer keys).
   - Use multi-pattern tokenizers that normalize inline options and brackets into standard tokens before extraction.
2. **Unparsed Line Diagnostics**:
   - Ingestion parsers must NEVER silently discard unrecognized lines.
   - Always return an `unparsed_lines` diagnostic collection to surface yellow alerts in the UI so the user can review skipped content.
3. **Idempotency & Normalized Deduplication (SHA-256)**:
   - Calculate normalized content hashes:
     ```php
     $hash = hash('sha256', Str::lower(preg_replace('/[^a-z0-9]/', '', $questionText)));
     ```
   - If an identical question already exists in `question_bank`, reuse the existing record ID and link it to the newly requested domain/module without duplicating database rows or polluting vector search.

---

## 8. Zero-Token Architecture (Pure Code vs AI Cost Separation)

Never spend AI tokens on tasks that pure deterministic code can solve in milliseconds for $0.

| Scenario | Input Data | Architecture Strategy | Token Cost |
| :--- | :--- | :--- | :--- |
| **Complete Questions** | Question text + Options A–D + Answer key | **Pure PHP Tokenizer** (0.01s regex parsing) | **0 Tokens ($0.00)** |
| **Missing Options** | Question text + Correct Answer only | **AI Distractor Engine** (LLM generates 3 wrong choices + rationale) | **Minimal (~75 tokens/Q)** |
| **Topic / Notes** | Chapter text, syllabus, or raw notes | **Full AI Generator** (LLM drafts questions, choices, and explanations) | **Standard (~150 tokens/Q)** |

* **The 10-Question Chunking Rule**: When invoking LLMs, strictly chunk requests into micro-batches of $\le$ 10 questions. Never ask for 50+ questions in a single prompt to eliminate output token truncation and JSON parse failures.

---

## 9. Correctness & Concurrency Patterns

### A. Optimistic vs Pessimistic Locking
1. **Optimistic Locking (`lock_version`)**:
   - Mandatory for candidate exam session state and subscription records.
   - Models maintain an integer `lock_version` column:
     ```php
     $affected = UserSubscription::where('id', $sub->id)
         ->where('lock_version', $sub->lock_version)
         ->update([
             'status' => 'active',
             'lock_version' => $sub->lock_version + 1,
         ]);
     if ($affected === 0) {
         throw new ConcurrencyException("Subscription was modified concurrently.");
     }
     ```
2. **Pessimistic Locking (`lockForUpdate`) & PgBouncer Caveat**:
   - Use `lockForUpdate()` ONLY for high-contention financial balance or coupon allocations.
   - **PgBouncer Law**: When running in transaction-pooling mode (Supabase port 6543), row locks MUST be released in $<200\text{ms}$. Never execute third-party HTTP calls (WhatsApp, EasyPaisa) while holding a `lockForUpdate()`.

### B. Race Condition Prevention Catalog
- **Double-Submit Answers**: Guarded by a unique composite index on `(exam_attempt_id, question_id)` in the grading database.
- **Double-Webhook Delivery**: Guaranteed idempotent by writing `(provider, trx_id)` to `webhook_events` with an immediate `insertOrIgnore` before executing transitions.
- **Duplicate WhatsApp Notification**: Check Redis key `lock:notification:{type}:{user_id}:{entity_id}` before queue dispatch.
- **Double Subscription Activation**: Guarded by atomic database state machine transitions (`WHERE status = 'pending'`).

### C. Time-Zone Contract
- **Storage**: All database timestamps (`created_at`, `starts_at`, `expires_at`) MUST be stored in UTC (`timestamp with time zone` or standard UTC).
- **Presentation**: All candidate-facing dates, countdowns, and exam schedules are converted to Pakistan Standard Time (PKT, UTC+5) strictly at the presentation layer.
- **Server-Authoritative Clock**: Exam timers calculate remaining duration as:
  $$\text{Remaining} = \text{session\_started\_at} + \text{duration\_seconds} - \text{NOW()}_{\text{UTC}}$$
  Client time is an untrusted hint used only for visual CSS animations.

---

## 10. Security Patterns (OWASP Top 10 for Pak-All-Tests)

### A. IDOR Defense & Server-Side Re-Resolution
- **Never trust client-supplied entity IDs**: A candidate passing `/api/modules/{module}/questions` must have their active domain access verified against `SubscriptionManager::hasActiveAccess($user, $module->domain)`.
- If a candidate accesses a module or question outside their active subscription, return HTTP 404 (never 403) to prevent resource ID enumeration.

### B. Mass Assignment & Property Whitelisting
- **Ban `$guarded = []`**: Every model MUST explicitly declare `$fillable = [...]`.
- Form requests must validate every incoming property with strict types (`'integer'`, `'string'`, `'boolean'`).

### C. Output Escaping, `v-html` Ban & AI Markdown Sanitization
- **Strict Ban on Raw `v-html`**: In Vue components, rendering user input via `v-html` is strictly prohibited.
- **AI Markdown Rendering**: Responses from `AiTutorService` must be parsed through a strict Markdown sanitizer (stripping `<script>`, `<iframe>`, `javascript:` URIs) before DOM injection.

### D. File Upload Validation & Storage Isolation
- When handling past-paper PDFs or payment receipts:
  ```php
  $request->validate([
      'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
  ]);
  ```
- Verify MIME magic bytes via `finfo_file()` on the temporary file before moving to permanent disk.
- Store uploaded receipts in an unindexed, non-executable storage bucket with randomized UUID filenames (`Str::uuid() . '.' . $ext`).

### E. Rate Limiting Hierarchy
Every public and candidate endpoint must enforce rate limiting at 3 distinct boundaries:
1. **Per-User**: `throttle:60,1` (keyed by `$request->user()->id`).
2. **Per-IP**: `throttle:120,1` (for public endpoints).
3. **Per-Domain / Resource**: `throttle:15,1` for resource-intensive AI tutoring SSE requests (`/api/domains/{domainSlug}/ai-tutor/ask`).

### F. HMAC Verification & Timing-Safe Comparison
All incoming webhooks (EasyPaisa, JazzCash) must verify signatures using `hash_equals`:
```php
$expected = hash_hmac('sha256', $request->getContent(), config('services.easypaisa.secret'));
if (! hash_equals($expected, $request->header('X-Signature', ''))) {
    abort(401, 'Invalid webhook signature.');
}
```

### G. SSRF Defense & Secrets Hygiene
- External fetch operations (e.g. PDF previews from Google Drive) must validate target URLs against an explicit domain whitelist. Never fetch arbitrary user-supplied URLs.
- **Secrets Hygiene**: Never call `env()` outside `config/` files. Never pass `config('services.openai.api_key')` to Inertia frontend properties.

---

## 11. Performance & Scalability Patterns

### A. Indexing Discipline
1. **Composite Index Left-Column Rule**: Place the highest cardinality or equality-filtered column first (`domain_id, is_active, created_at`).
2. **Partial Indexes for Active Records**:
   ```sql
   CREATE INDEX idx_active_modules ON content_modules (domain_id, sort_order) WHERE is_active = true;
   ```
3. **PostgreSQL Extensions**:
   - `ltree`: Indexed using GiST (`USING gist (path)`).
   - `pgvector`: Indexed using HNSW cosine distance (`USING hnsw (embedding vector_cosine_ops)`).

### B. Pagination & Mutation Memory Limits
- Candidate-visible lists exceeding 100 rows must use `cursorPaginate()` to prevent PostgreSQL offset scanning degradation.
- When mutating bulk datasets, always use `chunkById(100)` instead of `chunk(100)` to prevent duplicate or skipped records during primary key re-indexing.

### C. Redis Namespacing & Thundering Herd Defense
- All Redis cache keys must follow the structured namespace:
  `pak:{domainSlug}:{entity}:{id}`
- Set randomized jittered TTLs (e.g. `3600 + rand(0, 300)`) on high-traffic keys to prevent simultaneous cache expiry.
- Use `Cache::lock("lock:pak:sdui:{$domain}", 10)->get(...)` when re-compiling heavy SDUI page trees.

### D. Preloading Filament Relationships
In Filament table resources, never let repeaters or columns execute lazy-loaded queries. Always eager-load:
```php
public static function table(Table $table): Table
{
    return $table->modifyQueryUsing(fn ($query) => $query->with(['domain', 'questions']));
}
```

---

## 12. Reliability Patterns

### A. Outbound HTTP Retries & Exponential Backoff
All outbound HTTP client requests to AI providers or payment gateways must specify retry logic with jitter:
```php
Http::timeout(5)
    ->retry(3, 100, throw: false)
    ->post($endpoint, $payload);
```

### B. Dead-Letter Queue & Mandatory `failed()` Hook
Every queued Job in `app/Jobs` MUST implement the `failed()` callback to record telemetry and avoid silent data loss:
```php
public function failed(?Throwable $exception): void
{
    Log::error('Queue job failed permanently', [
        'job' => static::class,
        'user_id' => $this->userId,
        'error' => $exception?->getMessage(),
    ]);
}
```

### C. Outbox Pattern for Transactional Notifications
Never invoke external notification APIs directly inside database transactions. Write a record to a `notification_outbox` table within the transaction, and let an asynchronous queue worker read and dispatch notifications.

---

## 13. Testing Rigor & Verification Standards

### A. Boundary Condition Testing
Every feature test for timed tests or grading must test 3 critical points:
1. **0 Seconds**: Immediately after start.
2. **Exactly $N$ Seconds**: The exact deadline boundary.
3. **$N + 1$ Seconds**: 1 second past deadline (must be rejected or auto-submitted).

### B. Concurrency & Security Testing
- Write tests simulating concurrent double-submissions to verify transaction locks and idempotency.
- Write tests verifying cross-domain access attempts return 404/403.
- Use `Carbon::setTestNow()` or Pest `travel()` to verify subscription expiration and exam timeout edge cases.

### C. Faked Externals
- In test environments, never make real network calls. All tests must verify `Http::fake()` setups and assert mock payloads:
  ```php
  Http::fake([
      'api.openai.com/*' => Http::response(['choices' => [...]], 200),
  ]);
  ```

---

## 14. Code Hygiene, Maintainability & Craftsmanship

### A. Strict Typing & Class Sizing
- `declare(strict_types=1);` must head every newly created PHP file.
- **Method Length Target**: Maximum 20 lines. Refactor longer methods into dedicated private helper functions or Action classes.
- **Class Length Target**: Maximum 200 lines.
- **TypeScript Hygiene**: In any client-side JavaScript/TypeScript code, `any` is strictly prohibited without an inline architectural justification comment.

### B. Clean Naming & Comments
- Explicit model naming: `Domain` (never `Dom`), `UserSubscription` (never `Sub`), `QuestionBank` (never `QB`).
- Collections must always be plural (`$questions`, `$modules`).
- **Comments**: Write comments explaining **WHY** an unconventional decision was made, never describing **WHAT** obvious code does.
- Zero dead code: Zero commented-out blocks, zero `dump()`, zero `dd()`, and zero untracked `TODO`s.

