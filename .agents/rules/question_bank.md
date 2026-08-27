# 🧠 Question Bank & Test Engines

This standard documents the database schema, query patterns, and security filters for **Question Bank** items and interactive test runners.

---

## 1. Database Schemas

### Question Bank Table (`question_bank`)
* **Table**: `question_bank`
* **question_type**: Renders layout style (e.g., `mcq`, `short_answer`, `descriptive`).
* **code**: Import/Export lookup reference (e.g. `Q-VERB-001`).
* **question_text**: The prompt text displayed to the candidate.
* **payload**: JSONB column containing specific details:
  * For `mcq`: An options array `[{"text": "A", "is_correct": true}, ...]`
  * For `descriptive`: Character limits, instructions, or guide criteria.
* **difficulty_level**: Easy, medium, or hard.
* **explanation**: Detailed solution displayed during review.
* **is_active**: Boolean flag.

### Module Questions Pivot (`module_questions`)
* **Table**: `module_questions`
* **module_id** / **question_id**: Composite primary key connecting questions to content modules (cascade on delete).
* **sort_order**: Presentation order inside the test player.
* **marks**: Decimal weight (default `1.00`).

---

## 2. API Security & Correct Answer Scrubbing
To prevent candidates from exploiting client-side payloads (like viewing source JSON in the browser console to identify correct answers):
* **Security Filter (`McqExecutionController.php`)**: When fetching question sets for student practice runs, the backend MUST scrub the correct answer flags (`is_correct`) from the options arrays before sending the JSON response.
* **Payload Scrubbing Pattern**:
```php
$questions = $module->questions()
    ->get()
    ->map(function ($question) {
        $payload = $question->payload;
        if (isset($payload['options']) && is_array($payload['options'])) {
            $payload['options'] = array_map(function ($option) {
                unset($option['is_correct']);
                return $option;
            }, $payload['options']);
        }
        $question->payload = $payload;
        return $question;
    });
```
* **Score Computation**: Option submission evaluations and grading computations MUST occur strictly on the server layer using database-side checks.
