# ⚠️ Known Architectural Mistakes, Anti-Patterns & Root Causes

This document tracks real-world mistakes, root cause analyses (RCA), and defensive engineering rules to ensure they are **never repeated** across future tasks and agent sessions.

---

## 1. Static Infinite Recursion in Form Schemas (PHP Memory Exhaustion)

### 💥 The Symptom
* `HTTP 500: Allowed memory size of 1073741824 bytes (1 GB) exhausted` when loading a Filament resource edit or create page.

### 🔍 What Happened (Root Cause)
In PHP, when you define an array of form fields statically:
```php
// ❌ WRONG: Infinite self-referential schema construction
public static function contentNodeFields(): array
{
    return [
        Select::make('content_type')->options([...]),
        Repeater::make('tabs')->schema([
            TextInput::make('label'),
            // Calling itself without an exit condition!
            ...static::contentNodeFields(), 
        ]),
    ];
}
```
* **Why did it crash?** PHP executes functions **eagerly** at definition time. When `contentNodeFields()` ran, it called `contentNodeFields()`, which called `contentNodeFields()`, creating an endless loop like two mirrors facing each other.
* PHP attempted to instantiate millions of nested `Repeater` and `Select` objects in memory in less than a second before any HTTP response was sent.

### ✅ The Fix & Prevention Rule
* **Rule**: Never call a form schema method recursively inside a static array definition.
* **Separation of Concerns**: Define distinct schemas for each structural hierarchy level:
  1. `phaseFields()` (Top level: Stepper / TabbedLayout)
  2. `tabFields()` (Level 1: Tab metadata + Tab Content Type)
  3. `contentItemFields()` (Leaf level: Grids, Resources, Module Queries)

```php
// ✅ CORRECT: Explicit, non-recursive hierarchy
private static function phaseFields(): array
{
    return [
        TextInput::make('title')->required(),
        Select::make('content_type')->options([...]),
        Repeater::make('tabs')
            ->schema(static::tabFields()) // Calls distinct tabFields()
            ->hidden(fn (Get $get) => $get('content_type') !== 'TabbedLayout'),
        ...static::contentItemFields(),
    ];
}

private static function tabFields(): array
{
    return [
        TextInput::make('id')->required(),
        TextInput::make('label')->required(),
        Select::make('content_type')->options([...]),
        ...static::contentItemFields(), // Terminates at leaf content fields
    ];
}
```

---

## 2. Incorrect Filament v4 / v3 Package Namespace Imports

### 💥 The Symptom
* `Class "Filament\Forms\Get" not found` or `Class "Filament\Forms\Components\Section" not found`.

### 🔍 What Happened (Root Cause)
Filament v4 / modern modular setups moved utility classes and layout components into `Filament\Schemas`:
* In standard Filament v3: `use Filament\Forms\Get;`
* In this application's architecture: `use Filament\Schemas\Components\Utilities\Get;`
* In this application's layout containers: `use Filament\Schemas\Components\Section;` and `use Filament\Schemas\Components\Grid;`

### ✅ The Fix & Prevention Rule
* **Rule**: Always verify existing class imports in `app/Filament/` using `grep` before introducing new Filament components or utilities.
* Common canonical namespaces in this repository:
  - `Filament\Schemas\Components\Utilities\Get`
  - `Filament\Schemas\Components\Utilities\Set`
  - `Filament\Schemas\Components\Section`
  - `Filament\Schemas\Components\Grid`
  - `Filament\Forms\Components\Repeater`
  - `Filament\Forms\Components\TextInput`
  - `Filament\Forms\Components\Select`
  - `Filament\Forms\Components\Toggle`

---

## 3. Database Schema Drift & Attempting Updates on Non-Existent Columns

### 💥 The Symptom
* `SQLSTATE[42703]: Undefined column: 7 ERROR: column "document_file" of relation "content_modules" does not exist`.

### 🔍 What Happened (Root Cause)
A form component was mapped directly to a field (`document_file`) that only existed as a temporary upload buffer and was meant to be serialized inside the `content_meta` JSONB column, not as a dedicated database column.

### ✅ The Fix & Prevention Rule
* **Rule**: When adding uploaders or custom UI fields that store data in a JSONB bucket (`content_meta`, `ui_schema`, `learning_schema`), always mark temporary form fields as `->dehydrated(false)`.
* Handle extraction and serialization inside `afterStateHydrated()` and `dehydrateStateUsing()` or lifecycle hooks (`mutateFormDataBeforeSave`).

---

## 4. Query Slot & Dynamic Merge Deduplication Bugs

### 💥 The Symptom
* Duplicate test cards appearing on the candidate dashboard when merging manual items with dynamic database query results (`moduleQuery`).

### 🔍 What Happened (Root Cause)
Combining manual JSON items with active `ContentModule` queries without deterministic deduplication allowed the same module to render twice (e.g. once from manual array, once from `preparation` query).

### ✅ The Fix & Prevention Rule
* **Rule**: Always enforce single-pass linear deduplication by `moduleId` in `SduiPayloadTransformer`:
```php
$seenModuleIds = [];
$deduped = [];
foreach ($allItems as $item) {
    $mId = $item['moduleId'] ?? null;
    if ($mId !== null) {
        if (isset($seenModuleIds[$mId])) {
            continue; // Skip duplicate
        }
        $seenModuleIds[$mId] = true;
    }
    $deduped[] = $item;
}
```

---

## 5. Uncached Select Queries on Foreign Keys in Filament

### 💥 The Symptom
* Slow form render times (700ms+ database queries per page load) when rendering dropdown options for domains or modules.

### 🔍 What Happened (Root Cause)
Form select components querying the database on every HTTP request and Livewire lifecycle cycle instead of utilizing short-lived Redis caching.

### ✅ The Fix & Prevention Rule
* **Rule**: Wrap dropdown option queries in `Cache::remember`:
```php
Select::make('domain_id')
    ->options(fn (): array => Cache::remember(
        'domains_select_options',
        3600,
        fn () => Domain::query()->orderBy('name')->pluck('name', 'id')->all()
    ))
```

---

## 6. Filament FileUpload Component State Must Always Be an Array

### 💥 The Symptom
* `ErrorException: foreach() argument must be of type array|object, string given in BaseFileUpload.php:923` or `Argument #2 ($value) must be of type array, string given`.

### 🔍 What Happened (Root Cause)
Filament's internal `FileUpload` state representation in Livewire requires an `array` (`[(string) Str::uuid() => $filePath]` or `[]`). Passing a raw string path directly during `afterStateHydrated()` causes Filament's validation and file lookup loops to crash because PHP cannot run `foreach()` on a string.

### ✅ The Fix & Prevention Rule
* **Rule**: When hydrating any `FileUpload` component state, always provide an array:
```php
// ✅ CORRECT: Always wrap file path in an array keyed by UUID or empty array
$filePath = $isUpload ? str_replace('/storage/', '', $url) : null;
'document_file' => $filePath ? [(string) Str::uuid() => $filePath] : [],
```

---

## 7. Remote Database / PgBouncer WAN Transaction Starvation

### 💥 The Symptom
* `SQLSTATE[08006]: [7] could not connect to server: Connection timed out` or connection pool exhaustion on Supabase port 6543 during bulk imports or multi-record creation loops.

### 🔍 What Happened (Root Cause)
Wrapping a `foreach` loop of 50–100 individual Eloquent model creates and pivot `attach()` calls inside `DB::transaction(...)` across a remote WAN database connection. At 60ms latency per query, 200 sequential queries hold the database transaction open for **12–15 seconds**, starving the PgBouncer transaction pool and blocking concurrent candidate test requests.

### ✅ The Fix & Prevention Rule
* **Rule**: NEVER execute individual sequential insert queries inside a loop on remote databases.
* **Bulk Multi-Row SQL Insert**: Prepare datasets in memory and execute a single multi-row `insert` or `upsert`:
```php
// ✅ CORRECT: Multi-row bulk insert in a single round-trip (<150ms)
DB::transaction(function () use ($validatedRows, $targetModuleIds) {
    QuestionBank::insert($validatedRows); // 1 single round-trip
    
    // Bulk insert pivot records with ON CONFLICT DO NOTHING
    $pivotData = [];
    foreach ($newQuestionIds as $qId) {
        foreach ($targetModuleIds as $modId) {
            $pivotData[] = ['module_id' => $modId, 'question_id' => $qId, 'sort_order' => 0, 'marks' => 1.00];
        }
    }
    DB::table('module_questions')->insertOrIgnore($pivotData); // 1 single round-trip
});
```

---

## 8. Livewire Modal State Bloat & Payload Serialization Lag

### 💥 The Symptom
* Severe 2–4 second UI lag on every keystroke, input freezing in Filament modals, or `Livewire\Exceptions\CorruptComponentPayloadException` when previewing or editing bulk data sets (>20 items).

### 🔍 What Happened (Root Cause)
Livewire serializes its entire component state on every single network round-trip. Storing 50 full question objects (each containing statement text, 4 options, explanations, and radio states) causes Livewire's JSON payload to exceed **100KB–250KB**. Every micro-interaction transmits this massive payload over the wire, causing lag and state corruption.

### ✅ The Fix & Prevention Rule
* **Rule**: Never store large multi-record datasets directly in Livewire form/modal state.
* **The Cache-Pointer Pattern**: Store the heavy payload in a short-lived cache (Redis/Database), passing only a 36-character UUID `batch_id` to the Livewire component:
```php
// ✅ CORRECT: Store in cache, pass pointer to Livewire
$batchId = (string) Str::uuid();
Cache::put("import_batch_{$batchId}", $parsedQuestions, 1800);

// In Livewire modal:
$this->batchId = $batchId; // Payload is only 36 bytes!
```

---

## 9. PostgreSQL Empty String `""` vs `NULL` in Unique Nullable Columns

### 💥 The Symptom
* `SQLSTATE[23505]: Unique violation: 7 ERROR: duplicate key value violates unique constraint "question_bank_code_unique"`.

### 🔍 What Happened (Root Cause)
In PostgreSQL, multiple `NULL` values are permitted in a column with a `UNIQUE` constraint. However, if an import parser passes an empty string `""` for records lacking a unique code, the second record with `""` immediately triggers a unique key constraint violation because `"" == ""` in PostgreSQL!

### ✅ The Fix & Prevention Rule
* **Rule**: In nullable unique columns (`code`, `slug`, `sku`), strictly sanitize empty or whitespace strings to `null`:
```php
// ❌ WRONG: Empty string violates unique constraint on 2nd row
$row['code'] = trim($rawCode); // If empty, becomes ""

// ✅ CORRECT: Strict null sanitization
$row['code'] = filled($rawCode) ? trim($rawCode) : null;
```

---

## 10. Rigid Regex Failure on Real-World Past Papers

### 💥 The Symptom
* Silent dropping of questions or 0 questions parsed when uploading real past papers, PDFs, or educational website notes.

### 🔍 What Happened (Root Cause)
A single naive regex (e.g. `/^([A-D])\)\s+(.*)/`) assumed every past paper has neat line-breaks between options. In reality, past papers frequently format options inline on the same line (`1. Question? a) Lahore b) Karachi c) Islamabad d) Peshawar`), use parentheses `(A)` or brackets `[A]`, or group answer keys at the bottom of the document.

### ✅ The Fix & Prevention Rule
* **Rule**:
  1. Multi-pattern tokenizer: Normalize space-separated inline options (`a) ... b) ... c) ... d)`) into standard line-breaks before parsing.
  2. Unparsed Line Diagnostics: Never drop text silently. Output an array of `unparsed_lines` to alert the admin: *"Warning: Line 14 could not be recognized as an option or question."*

---

## 11. Vectorization Race-Condition on Model `saved` Hook

### 💥 The Symptom
* AI Socratic Tutor says *"No relevant domain knowledge found"* despite questions existing in the database. `domain_knowledge_embeddings` table remains empty for newly created questions.

### 🔍 What Happened (Root Cause)
`QuestionBank::booted()` dispatches `VectorizeDomainKnowledgeJob` inside the Eloquent `saved` hook. When a question is created (`QuestionBank::create(...)`), `saved` fires immediately. But the pivot relationship in `module_questions` has not been attached yet! The vectorization job runs, inspects `$question->modules()`, finds 0 domains, and returns empty without generating any embeddings.

### ✅ The Fix & Prevention Rule
* **Rule**: In bulk ingestion actions, use `createQuietly()` to suppress premature event dispatching, attach all domain pivot links first, and then explicitly dispatch vectorization:
```php
// ✅ CORRECT: Pivot attached before vectorization
$question = QuestionBank::createQuietly($data);
$question->modules()->attach($moduleIds);
VectorizeDomainKnowledgeJob::dispatch(questionId: $question->id);
```

