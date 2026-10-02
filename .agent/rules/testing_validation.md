# Testing & Validation Guidelines

> **Status**: ACTIVE ARCHITECTURAL STANDARD  
> **Domain**: Pest Test Execution, Transaction Rollbacks & Verification  
> **Location**: `.agent/rules/testing_validation.md`

---

## 1. Test Execution Standards & Pest DatabaseTransactions (Canonical Rule)

Our test suite executes against a configured remote or local PostgreSQL database.

* **DatabaseTransactions Mandate**: We strictly run feature and unit tests inside transactions using `Illuminate\Foundation\Testing\DatabaseTransactions`.
* **Ban on `migrate:fresh` and `RefreshDatabase`**: **Never execute `php artisan migrate:fresh` or use `RefreshDatabase` in tests**. Full DDL drops and recreation over remote connections (Supabase) incur massive WAN latency penalties, taking 10+ minutes and triggering connection timeouts.
* **Transaction Rollbacks**: Each test rolls back all mutations atomically at teardown, ensuring zero persistent test pollution and rapid execution.
* **Environment Integrity**: Ensure tests run on the configured Postgres database without leaking state across parallel runs.
* **Email Uniqueness**: When seeding users inside tests (e.g. `User::factory()->create()`), always use dynamic or unique email addresses (e.g. `test-<uniqid>@example.com`) to prevent unique key constraint violations in shared database tables.
* **Zero Dynamic Properties**: In Pest and PHPUnit tests, never assign dynamic `$this->variable` properties that trigger PHP 8.2+ deprecation notices. Use typed helper functions, closures, or class properties. (See `.agent/SYSTEM_RULES.md § 4`).

---

## 2. Middleware Fallbacks
For team-prefixed routing middleware (like `EnsureTeamMembership`), when URL parameters are not automatically mapped (such as in testing context requests where route segments are skipped), a fallback MUST look up the user's active/personal team:

```php
if ($team === null && $user = $request->user()) {
    $team = $user->currentTeam ?? $user->personalTeam();
}
```

---

## 3. Schema Guide Validation
All components added to the SDUI registry must have their validation constraints implemented in both:
1. **Backend Service** (`SduiSchemaValidator.php`) via recursive checking.
2. **Client-Side Validator** (`sdui-schema-guide.blade.php` Alpine.js controller) via matching logic, ensuring consistent developer feedback in the Live Guide tool.

---

## 4. Forensic QA & The Real-User Verification Law (Canonical Rule)

Passing unit tests are a baseline sanity check, **never proof of correctness**. Automated tests frequently assert on their author's assumptions, use clean factories, and run against mocked or `fake` drivers that silently pass fatal production bugs.

Every engineer and autonomous agent must execute **Forensic Real-User QA** — manually inspecting code line-by-line and testing against unexpected, hostile, and boundary data.

### The 6 Real-User Verification Directives:

1. **Hostile & Malformed Input Defense (Type Juggling)**:
   - Never trust implicit PHP type casting (e.g. `(int) $input === (int) $expected`).
   - In PHP, `(int) "malicious_string"` evaluates to `0`. If an option or status index is `0`, a malicious or malformed string will evaluate as identical to `0` and falsely pass evaluation.
   - **Rule**: Always enforce explicit `is_numeric()` checks or strict comparisons before casting user-supplied array keys or indices:
     ```php
     if ($userChoice !== null && is_numeric($userChoice) && (int) $userChoice === (int) $correctIndex)
     ```

2. **Boundary & Empty Input Resilience (Framework Quirks)**:
   - Test what happens when a student submits zero answers, an empty array `[]`, or when an exam timer expires with blank inputs.
   - In Laravel, the `'required'` validation rule **fails on empty arrays** (`answers: []`).
   - **Rule**: Use `'present', 'array'` for payloads that can legitimately be submitted empty (e.g. blank test submissions, optional multi-selects) so the request scores gracefully as 0% rather than throwing an unhandled HTTP 422 crash.

3. **Untrusted Markup & Text Ingestion Hygiene**:
   - CMS admins and question creators often paste rich text from Word or web portals containing hidden tags (`<p>`, `<span>`, MathJax, HTML entities).
   - In PHP 8.1+, passing `null` to `strip_tags()` triggers deprecation warnings (`strip_tags(): Passing null to parameter #1 of type string is deprecated`).
   - **Rule**: Always coerce nullable inputs before stripping: `trim(strip_tags((string) ($text ?? '')))` across all vectorization, search, and AI prompt formatting pipelines.

4. **Client-to-Server Event & Reactive Lifecycle Verification**:
   - Never assume that because a frontend prop is defined, it is populated.
   - Trace the entire reactive chain:
     ```
     [Child Component emit] ──> [Parent Page Ref] ──> [Consumer Widget Prop] ──> [API Context Payload]
     ```
   - Verify that when a user interacts with a component (e.g. clicking an option in `McqPlayer.vue`), the active selection actually propagates into the consumer widget (`AiTutorWidget.vue`) and reaches the backend ground truth.

5. **Third-Party Provider Contract Auditing**:
   - Mocked drivers (`fake`) accept any input. Production APIs (OpenAI, Google Gemini, EasyPaisa, WhatsApp) have strict, unforgiving schema contracts:
     - **Google Gemini**: Enforces strictly alternating roles (`user`, `model`, `user`, `model`) and turn 0 **must** be `user`. Consecutive identical roles or leading `model` turns trigger immediate HTTP 400 Bad Request rejections.
     - **Gemini Batch Embeddings**: Hard limit of **at most 100 items per batch request** (`batchEmbedContents`).
     - **OpenAI Embeddings**: Rejects empty strings `""` with HTTP 400.
   - **Rule**: Sanitize conversation turns, chunk batches into provider limits (max 100 for Gemini, max 500 for OpenAI), and check `$response->successful()` before consuming streams to prevent silent empty message saves.

6. **Async Error Boundaries & Stream Hygiene**:
   - In Server-Sent Events (`text/event-stream`), HTTP 200 headers are sent immediately before the stream body opens.
   - If an unhandled exception occurs inside a stream closure, PHP dumps an unformatted HTML stack trace into the active SSE connection, causing the browser EventSource / reader to hang indefinitely.
   - **Rule**: Every streaming closure must be wrapped in `try / catch (\Throwable $e)` and emit a structured error event (`event: error\ndata: {"message": "..."}\n\n`) on failure.

