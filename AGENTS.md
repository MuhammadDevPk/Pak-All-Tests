# AGENTS.md — Universal Autonomous Governance System

> **Status**: MANDATORY PRODUCTION HARNESS — ALWAYS ACTIVE  
> **Applicability**: All AI coding agents, pair programmers, and autonomous tools  
> **Workspace**: Pak-All-Tests (Laravel 13 • Inertia 3 • Vue 3.5 • PostgreSQL 16+ Supabase)

---

## ⚡ MANDATORY STEP 0: Consult Core Governance Files

Before planning, editing files, running terminal commands, or generating solutions, you **MUST** load and respect the harness specifications below:

1. [`.agent/AGENT.md`](file:///.agent/AGENT.md) — Staff Systems Architect & Offensive Security persona, threat modeling, SDD plan reviews, and anti-destructive guardrails.
2. [`.agent/PATTERNS.md`](file:///.agent/PATTERNS.md) — The 3-Tier standards:
   - **Mandates**: The Legacy Baseline Rule, Chaos Testing Protocol, Staff Engineer PR Review, 5-Phase Feature Lifecycle.
   - **Conventions**: Eager loading, early returns, Pint (`./vendor/bin/pint --dirty`), PHPStan (`./vendor/bin/phpstan analyse`), pure JavaScript in Vue.
   - **Observations**: SDUI dynamic schemas, role isolation, Socratic AI SSE streaming.
3. [`.agent/QUIRKS.md`](file:///.agent/QUIRKS.md) — Remote Supabase PostgreSQL WAN latency, PgBouncer port 6543 prepared statements, Azure FastCGI buffer sizes, 64MB upload limits, and Filament admin guard isolation.
4. [`.agent/ARCHITECTURE.md`](file:///.agent/ARCHITECTURE.md) — Full reverse-engineered system blueprint, directories, Eloquent models, and services.
5. [`.agent/MEMORY.md`](file:///.agent/MEMORY.md) — Active rolling memory log strictly adhering to the 10-entry maximum rule.
6. [`.agent/LIBRARIAN.md`](file:///.agent/LIBRARIAN.md) — Governance indexer, documentation lifecycle manager, and automated harness auditing.

---

## 🛑 Non-Negotiable Operational Guardrails

1. **The Legacy Baseline Rule**:
   - Never attempt to fix pre-existing lint errors, formatting warnings, or architectural flaws in files you did not touch in this session.
   - Never modify or weaken existing tests to cheat or force a passing build. If an existing test fails, identify if your changes caused a regression.
2. **Destructive Command Guardrail**:
   - `rm -rf`, `migrate:fresh`, `migrate:reset`, and `git push --force` are strictly prohibited.
   - All database tests must use `Illuminate\Foundation\Testing\DatabaseTransactions`. Never reset the remote database.
3. **Offensive Security Mandate**:
   - Scrutinize every endpoint against BOLA/IDOR, race conditions, replay attacks, and clock drift.
   - Never trust client-supplied IDs without server-authoritative policy or `SubscriptionManager` checks.
4. **Chaos Testing Protocol**:
   - Write tests that actively try to break your implementation under unexpected, concurrent, or hostile real-world inputs.
5. **Staff Engineer PR Review**:
   - Conduct a rigorous pre-conclusion self-audit before declaring any task complete.

---

## 📋 Standard Verification Commands

```bash
# Code style formatting (modified files only)
./vendor/bin/pint --dirty

# Code style verification
./vendor/bin/pint --dirty --test

# Static analysis (modified files only)
./vendor/bin/phpstan analyse <files>

# Automated testing suite
./vendor/bin/pest <path/to/Test.php>

# Frontend bundle verification
npm run build
```
