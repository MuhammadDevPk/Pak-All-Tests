# .agent/MEMORY_ARCHIVE.md — Archived Rolling Entries

Older rolling entries moved here from `.agent/MEMORY.md` once the active log exceeded 10 entries. Archived entries are immutable — never edited, never deleted.

### Entry 02 — 2026-09-10: Supabase PostgreSQL PgBouncer & Emulated Prepares
- **Action**: Configured `PDO::ATTR_EMULATE_PREPARES => true` in `config/database.php` on port 6543 (transaction pooling) and created `CachingUserProvider` with Redis caching.
- **Impact**: Prevented fatal deallocated prepared statement exceptions during PgBouncer pooler connection switches and eliminated a ~1,700ms remote database latency penalty on authenticated requests.

### Entry 01 — 2026-09-08: Multi-Tenant Filament v5 Administration Foundation
- **Action**: Established administrative panels for domains, hierarchical nodes, question banks, candidate management, and team-scoped resources.
- **Impact**: Separated system administrative capabilities from public candidate interfaces with granular Spatie role and permission enforcement.
