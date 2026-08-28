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
