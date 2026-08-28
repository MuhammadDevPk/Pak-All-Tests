# 📦 Content Modules & Course Resources

This standard documents the database schema, business rules, and API integrations for **Content Modules** (lessons, tests, downloads).

---

## 1. Database Schema
* **Table**: `content_modules`
* **Primary Key**: `id` (bigint, auto-increment)
* **domain_id**: Constrained link to `domains.id` (cascade on delete).
* **title**: Human-readable title (e.g. *Verbal Intelligence Test 1*).
* **slug**: Unique string identifier used for module routes.
* **module_type**: Category grouping string (e.g., `preparation`, `mock_test`, `pdf_repo`, `subjective`).
* **sort_order**: Integer order weight index.
* **is_active**: Boolean visibility flag.
* **content_meta**: JSONB column containing specific parameters (descriptions, MCQ timing limits, file details, etc.).

---

## 2. Type Configuration & Use Cases
* **`preparation`** (Preparation Module): Standard timed multiple-choice practice tests. Renders an `McqPlayer` interactive card.
* **`pdf_repo`** (PDF / Document Repository): Handouts, syllabus guides, or notes. Renders a `PdfViewer` card or download action. Contains `file_url`, `file_type`, and `file_size` keys inside `content_meta`.
* **`subjective`**: Descriptive or word association tests (e.g., WAT, SRT, Interview prep). Renders written responses forms.

---

## 3. Upload & Dehydration Standards
To support file uploads for document repository modules without database schema bloating:
* **Admin Form (`ContentModuleForm.php`)**: Upload fields like `document_file` are marked `->dehydrated(false)` to prevent Filament from attempting direct SQL column inserts.
* **Metadata Sync**: Custom save/dehydrate hooks pack file paths and metadata into the model's `content_meta['file_url']` JSON attributes dynamically:
```php
$record->content_meta = array_merge($record->content_meta ?? [], [
    'file_url' => $path,
    'file_type' => 'PDF Document',
    'file_size' => $size,
]);
```

---

## 4. API Gating Security
All content module endpoints MUST enforce strict security gates to block access to drafts or unauthorized content:
```php
if (!$module->is_active || !$module->domain()->where('is_active', true)->exists()) {
    abort(404, 'This module is not currently available.');
}
```
* **Subscription Gating**: Rejects attempts if the authenticated candidate does not hold an active, non-expired subscription for the module's parent domain.

---

## 5. Dynamic Sort Order & Sequential Re-Indexing
To ensure seamless curriculum management and avoid manual numbering:
* **Auto-Default on Creation**: The `ContentModule` model and Filament form automatically calculate the next available sort order per domain:
  `sort_order = (ContentModule::where('domain_id', $domainId)->max('sort_order') ?? 0) + 1`
* **Automatic Gap Compaction on Deletion**: When any content module is deleted, Eloquent lifecycle hooks automatically re-compact and re-index all remaining modules in that domain (`1, 2, 3, 4...`) without leaving holes or skipped numbers (`ContentModule::reorderDomainModules($domainId)`).
* **Table Ordering**: Filament content modules table lists records ordered by `domain_id` and `sort_order` ascending by default.
