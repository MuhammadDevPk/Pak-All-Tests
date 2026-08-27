# 📂 Domains & Categories Reference

This standard documents the database schema, business rules, and hierarchical logic for **Domains** in the application.

---

## 1. Database Schema
* **Table**: `domains`
* **Primary Key**: `id` (bigint, auto-increment)
* **parent_id**: Links to self (`domains.id`) for sub-domain mapping. Restructured to set null on parent deletion.
* **name**: Human-readable title (e.g., *Armed Forces*, *PMA Long Course Initial*).
* **slug**: Unique string identifier used for clean routing (e.g. `pma-initial-course`).
* **code**: Unique shorthand developer identifier (e.g. `PMA_INITIAL`).
* **is_active**: Boolean flag to enable/disable candidate visibility (default: `true`, indexed).
* **path**: PostgreSQL `ltree` column representing the full ancestral hierarchy (e.g. `1.12.124`).

---

## 2. Hierarchical Pathing (`ltree`)
To avoid recursive database queries or slow loop queries when fetching domain trees, we use PostgreSQL's native `ltree` extension.
* **Format**: Dot-separated IDs from the root category down to the current node (e.g. `RootID.SubCategoryID.LeafID`).
* **Index**: An index named `domains_path_gist_idx` of type **GiST** covers the `path` column.
* **Query Patterns**: The `<@` (is descendant of) and `@>` (is ancestor of) operators are used for lightning-fast sub-tree lookups.
* **Trigger Updates**: Triggers in the `Domain` Eloquent model boot hook automatically generate and update child paths when parents change, using raw SQL parameterization to shift trees in sub-milliseconds:
```sql
UPDATE domains
SET    path       = ?::ltree || subpath(path, nlevel(?::ltree)),
       updated_at = NOW()
WHERE  path <@ ?::ltree
AND    id != ?
```

---

## 3. Cache & Performance Optimization
* **CachesTableData Trait**: The `Domain` model implements local Redis caching. 
* **Invalidation Policy**: Any CRUD operation (`created`, `updated`, `deleted`, `saved`) automatically flushes the cache key to guarantee subsequent request payloads receive fresh data immediately.
* **Avoid N+1 Queries**: Eager-load or batch queries when rendering hierarchies in admin lists.
