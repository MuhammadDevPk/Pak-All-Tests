# Database Engineering & Performance Optimization Standards

> **Status**: ACTIVE ARCHITECTURAL STANDARD  
> **Domain**: PostgreSQL Indexing, Sub-Millisecond Execution & Transaction Safety  
> **Location**: `.agent/rules/database_performance.md`

---

## 1. Indexing Strategy & Zero N+1 Eager-Loading Discipline (Canonical Rule)

We maintain a strict zero-redundancy index policy and zero-tolerance for N+1 queries:

### A. Zero N+1 Database Queries
* **Eager-Load by Default**: Always eager-load relations (`with([...])`) when querying collections destined for presentation, Inertia props, or API responses.
* **Filament Resources**: Never permit table columns or repeaters to lazily evaluate foreign relations. Always configure `$table->modifyQueryUsing(fn ($q) => $q->with([...]))`.
* **Verification in Tests**: Feature tests must inspect or assert query counts on collection endpoints to prevent silent N+1 regressions.

### B. PostgreSQL Indexing Strategy
* **Compound Indexes**: When queries filter by category/parent first and sort next, compound indexes must cover the query filter and sorting columns in order. Example: `domain_id` + `module_type` + `sort_order` is indexed to optimize dynamic grid population.
* **Zero Redundancy**: Do not add B-tree indexes to columns that are already covered by unique constraints (e.g. `unique()` in Laravel migrations automatically generates a primary B-tree index in Postgres).
* **Positional Parameters**: Raw database statements MUST always use parameterized bindings (`?` or named keys) to protect against SQL injections and optimize database execution plan caching.

---

## 2. Dynamic Query Performance & The Sub-200ms Transaction Law (Canonical Rule)

Our application connects to remote PostgreSQL infrastructure (Supabase PgBouncer on port `6543`) with 50–150ms WAN query latency.

### A. The Sub-200ms Transaction Law
* **Strict Lock Duration**: Database transactions (`DB::transaction(...)`) must NEVER hold locks for longer than 200 milliseconds.
* **Ban Sequential Loop Inserts**: Never execute `$model->save()` or `$relation->attach()` inside a `foreach` loop within a transaction. Sequential loop executions across a remote WAN connection keep locks open for 10–15+ seconds, starving PgBouncer connection pools and exhausting workers.
* **Multi-Row Bulk Execution**:
  1. Collect and validate all rows in memory as PHP arrays.
  2. Execute a single multi-row `Model::insert($data)` or `DB::table(...)->insertOrIgnore($pivotData)` to collapse 100 queries into 1 round-trip.
  3. For mass relationship association, use `syncWithoutDetaching()` or `insertOrIgnore` to prevent primary/foreign key conflict exceptions.

### B. Hierarchical & Dynamic Sorting
* All queries pulling modules for dashboard hydration must sort on the database layer (`orderBy('sort_order', 'asc')`) rather than sorting PHP Collections in memory.
* Database columns containing paths (such as nested domain category paths) use PostgreSQL’s `ltree` extension. The GiST index type covers the `<@` ancestor/descendant operator for sub-millisecond hierarchical lookups.

---

## 3. Query Caching & Stampede Defense
To bypass expensive TCP roundtrips to remote data targets (like Supabase pools) during high-concurrency requests, static tables (`domains`, `domain_flows`, `question_bank`) are cached locally in Redis.
* **TTL Policy**: Cached data uses a standard 5-minute to 60-minute TTL or remains cached indefinitely with event-driven invalidation.
* **Invalidation Hooks**: Any CRUD action (Create, Update, Delete) on these models MUST fire an invalidation event via the `CachesTableData` trait to forget or flush the cache key instantly.
* **Thundering Herd Defense**: High-traffic cached layouts use Redis atomic locks (`Cache::lock("lock:pak:sdui:{$domain}", 10)`) when recompiling heavy trees.

---

## 4. The Cache-Pointer Pattern (Canonical Rule)

Livewire serializes its entire component state into JSON on every network round-trip. Passing collections or large arrays (>20 items) into Livewire/Filament component properties causes severe payload bloat (100KB–250KB), 2–4 second UI input lag, and fatal `Livewire\Exceptions\CorruptComponentPayloadException` crashes.

### The Mandatory Cache-Pointer Protocol:
When previewing, generating, or processing multi-record datasets (such as bulk question imports, CSV parsing, or AI distractor generation):

1. **Store Dataset in Redis**: Write the heavy array/collection to temporary cache under a UUID key:
   ```php
   $batchId = (string) Str::uuid();
   Cache::put("import_batch_{$batchId}", $heavyDataset, now()->addMinutes(30));
   ```
2. **Store ONLY the Pointer in State**: In Livewire / Filament component form state, store **ONLY the 36-byte UUID `$batchId`**:
   ```php
   $this->batchId = $batchId; // Payload over the wire is only 36 bytes!
   ```
3. **Read on Demand**: Retrieve, display, or paginate records from cache on demand. Destroy the cache key upon final database transaction commit or modal cancel.
4. **Modal Memory Discipline**: Modals must never render more than 10–15 items in a single view window without pagination or virtual scrolling.
