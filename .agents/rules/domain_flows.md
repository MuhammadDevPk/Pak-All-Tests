# ⚙️ Domain Flows & SDUI Templates

This standard documents the database schema, business rules, and template structures for **Domain Flows** (Server-Driven UI configurations).

---

## 1. Database Schema
* **Table**: `domain_flows`
* **Primary Key**: `id` (bigint, auto-increment)
* **domain_id**: Constrained foreign key pointing to `domains.id` (one-to-one, cascade on delete).
* **ui_schema**: JSONB column storing the modular component layout structure for candidate dashboards.
* **learning_schema**: JSONB column containing logic rules, pass marks, or time constraints (e.g. `{"time_limit_minutes": 45, "passing_percentage": 60}`).
* **version**: Integer incrementing when the structural configuration changes.
* **is_active**: Boolean flag to enable/disable rendering of the layout.

---

## 2. Server-Driven UI (SDUI) Hydration
The `DomainFlow`'s `ui_schema` holds the layout component configuration. When a candidate requests their domain dashboard:
1. The server loads the active `DomainFlow` and its `ui_schema`.
2. The `SduiPayloadTransformer` scans the layout tree recursively.
3. For any node containing a `moduleId` or `moduleQuery`, the transformer queries the database for corresponding active content modules.
4. The transformer injects the database attributes (title, slug, action labels, PDF file metadata) directly into the layout payload before delivering it to the Vue frontend.

---

## 3. Cache & Performance
* **Cache Key**: Cache logic is hooked using the `CachesTableData` trait to eliminate Supabase database queries during high-concurrency requests.
* **Eager Loading**: The dashboard controller loads only active flows:
```php
$domain = Domain::where('slug', $slug)
    ->where('is_active', true)
    ->with(['flow' => fn ($q) => $q->where('is_active', true)])
    ->firstOrFail();
```
