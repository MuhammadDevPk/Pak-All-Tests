# 🧠 Senior Architect Principles & Engineering Mandates (30+ Years Experience)

This document defines the foundational engineering philosophy that **MUST be strictly adhered to in every chat, code generation, code review, architectural design, and response**.

---

## 1. 🎯 Write Less Code, Do More (Radical Simplicity & Zero Bloat)

> *"Simplicity is prerequisite for reliability."* — Edsger W. Dijkstra  
> *"Junior engineers write complex code for simple problems. Senior architects write simple, elegant code for complex problems."*

### Core Rules:
1. **Never write 3x–4x lines of boilerplate** when 5–10 concise, idiomatic lines achieve the exact same result with higher readability and lower maintenance cost.
2. **Leverage Framework Primitives First**: Always utilize native Laravel, Eloquent, Inertia, Vue 3, and PostgreSQL features rather than hand-rolling custom utility layers or bloated classes.
3. **Strict YAGNI (You Aren't Gonna Need It)**:
   - Build **only** what is explicitly required.
   - Do **NOT** add speculative parameters, unused helper methods, hypothetical future abstractions, or dead fallback branches.
4. **Minimal Surface Area**: Every added line of code is a liability, potential bug vector, and maintenance burden. Strive for the smallest possible diff that cleanly and completely solves the problem.

---

## 2. 🔍 Zero Assumptions & Fact-Checked Ground Truth

### Core Rules:
1. **Never Guess or Assume**:
   - Never assume how a database column is named, how a component is structured, or how a service functions without verifying.
   - Never tell the user *"the code works like X"* or *"we have Y in the codebase"* based on speculation.
2. **Inspect the Code First**:
   - Before answering any question or modifying any file, always inspect the active code (`view_file`, `grep_search`, `list_dir`) and examine the actual implementation.
3. **Ground-Truth Explanations**:
   - When explaining bugs, performance metrics, or system flows, provide exact file paths, line numbers, and verified execution traces.
   - If something does not exist in the code, state it clearly based on evidence, not guesswork.

---

## 3. ⚡ Senior Craftsmanship & Clean Code Standards

### Core Rules:
1. **Readable & Self-Documenting**: Code should read like well-written prose. Variable names must be precise and intention-revealing. Avoid cryptic abbreviations or unnecessary indirection.
2. **Single Responsibility**: Every class, method, and component must have one well-defined responsibility. If a method does 3 different things, split it or simplify it.
3. **Deterministic Edge Case Handling**:
   - Handle genuine edge cases (empty collections, `null` keys, boundary numbers, missing foreign relations) gracefully.
   - Do not bloat code with redundant ternary operators or nested `if/else` ladders when PHP 8.4 null-safe operators (`?->`, `??`, `match`) do it cleanly.
4. **Memory & Performance First**:
   - Avoid creating large intermediate arrays in memory when working with datasets (use Eloquent lazy collections / chunking / DB aggregates where appropriate).
   - Zero N+1 database queries: Always eager-load relations when rendering collections.
   - Zero infinite recursions or circular static references.

---

## 4. 🧪 Automated Validation Mandate
* Every non-trivial change must be accompanied by or validated against automated tests (Pest / PHPUnit).
* Run the test suite before declaring work complete to ensure 100% regression-free code.

---

## 5. 🎨 Frontend Tech Stack: Pure JavaScript Vue (No TypeScript in Vue)
* **Standard**: Write all Vue components using **clean, pure JavaScript** (`<script setup>`), NOT TypeScript (`lang="ts"`).
* **Simplicity & Velocity**: Keep component state, props, and logic in idiomatic modern JS (ESNext, Vue 3 Composition API).
* Do not introduce TypeScript complexity or type definitions into `.vue` components.
