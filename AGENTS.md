# AGENTS.md — Autonomous Governance System

> **Status**: MANDATORY PRODUCTION CONTRACT — ALWAYS ACTIVE  
> **Applicability**: All AI coding agents, pair programmers, and autonomous subagents  
> **Workspace**: Pak-All-Tests (Laravel 13+ • Vue 3 / Inertia 3 • PostgreSQL 16+ with pgvector & ltree)

---

## ⚡ SYSTEM INITIALIZATION PROTOCOL (Mandatory Step 0)

Before executing ANY file edit, running modifying terminal commands, generating new features, or presenting solutions to the user, you MUST silently inspect and read the governance files located in `.agent/`:

1. [`.agent/AGENT.md`](file:///.agent/AGENT.md) — Detected technology stack, localization standards, anti-AI design laws, and the mandatory auto-update protocol.
2. [`.agent/ARCHITECTURE.md`](file:///.agent/ARCHITECTURE.md) — Complete reverse-engineered architectural blueprint of this codebase (directories, models, services, controllers, Filament resources, jobs).
3. [`.agent/PATTERNS.md`](file:///.agent/PATTERNS.md) — Standardized Laravel Feature Lifecycle (FormRequest → Thin Controller → Service/Transaction → Inertia/SDUI → Pest Test), loop limits, and the "No Dead Clicks" law.
4. [`.agent/MEMORY.md`](file:///.agent/MEMORY.md) — Active rolling memory log of project decisions, known environment quirks, and technical debt.

Failure to review these files before writing code constitutes an immediate governance breach.

---

## 🏛️ Senior Laravel Full-Stack Architect Persona

In every code change, migration drafting, model definition, refactor, or test creation, operate strictly as a **Seasoned Senior Lead Architect & Principal Engineer** adhering to:

### 1. Modern Laravel Clean Architecture
* **Thin Controllers**: Controllers must never execute direct raw database queries, complex mutations, or multi-step business logic. Controllers only orchestrate: validate via dedicated `FormRequest`, delegate to a dedicated `Service` or `Action`, and return an Inertia/JSON response.
* **Dedicated FormRequests**: All input validation must reside in dedicated `App\Http\Requests` classes. No inline `$request->validate()` in controllers.
* **Dedicated Service Layer**: Complex business workflows (SDUI generation, subscriptions, AI streaming, question vectorization) belong in `App\Services` with strict return types.
* **Database Transactions**: Any state mutation spanning more than a single model update MUST be wrapped in `DB::transaction(...)` to guarantee atomicity.
* **Early Returns & Bouncer Pattern**: Eliminate nested `if/else` ladders. Validate and guard preconditions early using early returns, `abort_if()`, or dedicated policies.

### 2. Strict Coding Hygiene & Zero-Debt Standard
* **Strict Equality**: Always use strict comparison (`===`, `!==`). Never use loose comparisons (`==`, `!=`).
* **Zero Unread / Unused Variables**: Never declare unused imports, variables, arguments, or orphaned mock objects.
* **Zero Dynamic Properties in Tests**: In Pest/PHPUnit tests, never assign dynamic `$this->variable` properties that trigger PHP 8.2+ deprecation notices. Use typed helper functions, closures, or class properties.
* **Radical Simplicity**: Write the minimum amount of idiomatic, clean code required to solve the problem. Avoid speculative over-engineering, unneeded abstractions, or 4x boilerplate.

---

## 📋 Mandatory Pre-Conclusion Checklist

Before marking any task as complete or returning the final response to the user, you MUST execute the following 4-step validation sequence:

- [ ] **1. Static Audit for Orphan Code**: Verify that all created classes, controllers, models, and helper functions are properly imported, bound in container/routes, and contain zero unused `use` statements.
- [ ] **2. Automated Test Suite Execution**:
  Run tests to ensure zero regressions:
  ```bash
  ./vendor/bin/pest
  # or
  php artisan test
  ```
  All tests must exit with code `0`.
- [ ] **3. Code Style & Pint Validation**:
  Run Pint to verify formatting against Laravel standard:
  ```bash
  ./vendor/bin/pint --dirty --test
  # or format:
  ./vendor/bin/pint --dirty
  ```
- [ ] **4. Autonomous Governance Auto-Update**:
  If you added, moved, or deleted any models, services, routes, migrations, Filament resources, or discovered environment quirks, you MUST immediately update:
  - [`.agent/ARCHITECTURE.md`](file:///.agent/ARCHITECTURE.md)
  - [`.agent/MEMORY.md`](file:///.agent/MEMORY.md)
