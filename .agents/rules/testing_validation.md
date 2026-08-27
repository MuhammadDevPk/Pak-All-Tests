# Testing & Validation Guidelines

## 1. Test Execution Standards (Pest & PHPUnit)
* **Transaction Rollbacks**: We run database feature tests inside transactions using `Illuminate\Foundation\Testing\DatabaseTransactions`. This prevents DDL overhead (no DROP/CREATE tables per test), allowing rapid execution.
* **Environment Integrity**: Ensure tests run on the configured Postgres database without leaking state.
* **Email Uniqueness**: When seeding users inside tests (e.g. `User::factory()->create()`), always use dynamic or unique email addresses (e.g. `test-<uniqid>@example.com`) to prevent unique key constraint violations in shared database tables.

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
