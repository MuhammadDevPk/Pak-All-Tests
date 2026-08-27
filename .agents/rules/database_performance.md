# Database Engineering & Performance Optimization Standards

## 1. Indexing Strategy (PostgreSQL)
We maintain a strict zero-redundancy index policy.
* **Compound Indexes**: When queries filter by category/parent first and sort next, compound indexes must cover the query filter and sorting columns in order. Example: `domain_id` + `module_type` + `sort_order` is indexed to optimize dynamic grid population.
* **Zero Redundancy**: Do not add B-tree indexes to columns that are already covered by unique constraints (e.g. `unique()` in Laravel migrations automatically generates a primary B-tree index in Postgres).
* **Positional Parameters**: Raw database statements MUST always use parameterized bindings (`?` or named keys) to protect against SQL injections and optimize database execution plan caching.

---

## 2. Dynamic Query Performance
* All queries pulling modules for dashboard hydration must sort on the database layer (`orderBy('sort_order', 'asc')`) rather than sorting PHP Collections in memory.
* Database columns containing paths (such as nested domain category paths) use PostgreSQL’s `ltree` extension. The GiST index type covers the `<@` ancestor/descendant operator for sub-millisecond hierarchical lookups.

---

## 3. Query Caching
To bypass expensive TCP roundtrips to remote data targets (like Supabase pools) during high-concurrency requests, static tables (`domains`, `domain_flows`, `question_banks`) are cached locally in Redis.
* **TTL Policy**: Cached data uses a standard 5-minute TTL or remains cached indefinitely.
* **Invalidation Hooks**: Any CRUD action (Create, Update, Delete) on these models MUST fire an invalidation event to forget or flush the cache key instantly, ensuring candidates receive fresh data immediately upon next page load.
