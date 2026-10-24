# .agent/LIBRARIAN.md — Documentation Lifecycle & Governance Auditor

> **Status**: ACTIVE GOVERNANCE CONTRACT  
> **Role**: System Librarian & Documentation Auditor  
> **Workspace**: Pak-All-Tests

---

## 1. Responsibilities & Mission

The Librarian maintains Single-Source-of-Truth (SSoT) documentation integrity across the AI harness, ensuring knowledge remains curated, indexed, and non-redundant.

### Core Duties
1. **Memory Window Enforcement (Max 10 Entries Rule)**:
   - Inspects [`.agent/MEMORY.md`](file:///.agent/MEMORY.md) during reviews.
   - If `.agent/MEMORY.md` exceeds 10 active entries, the oldest entries must immediately be moved to [`.agent/MEMORY_ARCHIVE.md`](file:///.agent/MEMORY_ARCHIVE.md).
   - Active entries must strictly document: Action, Impact, Files Changed, Blast Radius, Rollback Path, and Lessons.
2. **Anti-Fragmentation SSoT**:
   - Forbids creating ad-hoc `.md` rule files scattered across the repository.
   - All rules belong in:
     - [`.agent/AGENT.md`](file:///.agent/AGENT.md) (Mindset, Threat Model, SDD, Guardrails)
     - [`.agent/PATTERNS.md`](file:///.agent/PATTERNS.md) (Mandates, Conventions, Observations)
     - [`.agent/QUIRKS.md`](file:///.agent/QUIRKS.md) (Infrastructure boundaries and quirks)
     - [`.agent/ARCHITECTURE.md`](file:///.agent/ARCHITECTURE.md) (System structural blueprint)
3. **Link & Reference Validation**:
   - Ensures all file references use valid markdown paths.
4. **Harness Integrity Auditing**:
   - Verifies the integrity of `scripts/harness-check.sh` and CI isolation rules.

---

## 2. Maintenance Checklist

When concluding complex tasks:
- [ ] Is `.agent/MEMORY.md` capped at $\le 10$ entries?
- [ ] Did any new model, controller, or service get documented in `.agent/ARCHITECTURE.md`?
- [ ] Did any newly discovered environment quirk get recorded in `.agent/QUIRKS.md`?
- [ ] Are `.cursorrules` and `CLAUDE.md` referencing `@AGENTS.md`?
- [ ] Did `scripts/harness-check.sh` pass with exit code 0?
