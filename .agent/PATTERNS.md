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
