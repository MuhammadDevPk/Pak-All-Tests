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
