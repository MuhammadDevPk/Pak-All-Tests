# .agent/AGENT.md — Technology Stack, Market Standards & Autonomous Loop

> **Status**: ACTIVE CORE DIRECTIVE  
> **Governance Level**: Strict Architectural Mandate

---

## 1. Detected Project Stack & Runtime Specifications

| Layer | Technology | Exact Version / Configuration | Notes |
| :--- | :--- | :--- | :--- |
| **Runtime** | PHP | `^8.3` (Runtime: PHP 8.4.x) | Strict typing, typed properties, match expressions |
| **Framework** | Laravel | `^13.17` (Laravel 12 / 13 monolith) | Modern bootstrap pipeline (`bootstrap/app.php`) |
| **Database** | PostgreSQL | PostgreSQL 16+ (Supabase / RDS) | Extensions: `ltree` (category hierarchy), `vector` (1536-dim HNSW) |
| **Admin Panel**| Filament | `^5.0` (`filament/filament`) | Resources, Repeaters, Custom Schemas & Actions |
| **Frontend** | Vue 3 + Inertia | Vue `^3.5.13` • `@inertiajs/vue3: ^3.0.0` | Pure JavaScript `<script setup>` in Vue components |
| **Styling** | Tailwind CSS 4 | `@tailwindcss/vite: ^4.1.11` | Pure CSS theme variables, zero legacy Tailwind config |
| **UI Primitives**| Reka UI & Lucide | `reka-ui: ^2.9.8` • `@lucide/vue` | Accessible headless components, Radix Vue primitives |
| **Auth** | Fortify & Passkeys | `laravel/fortify: ^1.37.2` • `@laravel/passkeys`| 2FA, WebAuthn Passkeys, Teams multi-tenancy |
| **Testing** | Pest 4 | `pestphp/pest: ^4.7` • `pest-plugin-laravel`| Feature & Unit test suites, `DatabaseTransactions` |
| **Code Style** | Laravel Pint | `laravel/pint: ^1.27` (`preset: "laravel"`) | Zero code style divergence allowed |
| **Static Analysis**| Larastan | `larastan/larastan: ^3.9` | Level 6 PHPStan analysis |

---

## 2. Pakistani Competitive Exam Domain & Regional Standards (PK)

When building candidate portals, assessment engines, domain landing pages, checkout flows, or AI tutoring components, strictly adhere to the following Pakistani regional, linguistic, and domain standards:

### A. Target Audience & Institutional Domain Scope
* **Armed Forces Initial Tests**: PMA Long Course, PAF GD(P), Pak Navy (AS&RC test protocols: Verbal, Non-Verbal Intelligence, and Academic sub-tests).
* **ISSB (Inter-Services Selection Board)**: 4-day psychological and outdoor evaluation (WAT, SRT, TAT, GTO Tasks, Deputy President interview).
* **Civil Services & Provincial Commissions**: CSS Competitive Examination (FPSC), PMS, PPSC, SPSC, KPPSC, BPSC.
* **Medical & Professional Entry**: MDCAT (PMDC curriculum), ECAT.
* **Tone**: *"Academic Rigor & Military Precision"* — Authoritative, disciplined, structured, objective, and distraction-free.

### B. Language & Typography Standards
1. **Bilingual Capability (English & Roman Urdu)**:
   - Primary test questions, official syllabi, and administrative documentation must be presented in clear, formal English.
   - The Socratic AI Tutor must understand and seamlessly communicate in Roman Urdu (e.g., *"Formula ko dubara dekhein, agar mass constant ho tou force barhne par acceleration pe kya asar parega?"*) when candidates converse in Roman Urdu.
2. **5-Second Clarity (Candidate UX Standard)**:
   - Test instructions, timer limits, negative marking warnings, and pricing blocks must be instantly comprehensible to candidates in under 5 seconds.
   - Ban engineering jargon (`payload`, `SDUI schema`, `polymorphic key`) from candidate interfaces.
   - Clearly state scoring schemes (e.g. *"+1 Mark per correct answer, 0.25 Negative marking applies"*).

### C. Formatting & Regional Conventions
* **Phone Masking**: Pakistani mobile number format: `+92 (3XX) XXXXXXX` or `03XX-XXXXXXX`, cleanly normalized to E.164 (`923XXXXXXXXX`) on database persistence.
* **Date & Time**: Date formatting `DD.MM.YYYY` or `DD/MM/YYYY` (e.g. `19.09.2026`) and Pakistan Standard Time (PKT, UTC+5) for exam deadlines and live sessions.
* **Currency Formatting**: Format Pakistani Rupees with official standard symbol or prefix: `PKR 2,500` or `₨ 2,500` (comma thousands separator, never bare ambiguous symbols).
* **Regional Payment Gateways**:
  - *Current Active Flow*: Candidate manual payment via **EasyPaisa**, **JazzCash**, or **Direct Bank Transfer** with TRX ID and receipt screenshot upload.
  - *Verification State Machine*: Transitions via `SubscriptionManager` (`pending` $\rightarrow$ `active` / `rejected` / `expired`) reviewed in Filament admin queue.
  - *Post-MVP Merchant Webhooks*: Direct EasyPaisa / JazzCash IPN webhook integration (`POST /api/webhooks/easypaisa` with HMAC-SHA256 signature verification).
  - *Candidate Messaging*: Automated notifications via **Meta WhatsApp Cloud API** to candidate's Pakistani mobile number (`+92...`) for subscription confirmations, exam reminders, and test status alerts.


---

## 3. Strict Anti-AI Design Guidelines

1. **No Generic Violet Gradients**: Ban saturated violet-to-cyan mesh gradients (`bg-gradient-to-r from-purple-600 to-indigo-600`) that scream generic template code. Use deliberate, high-contrast, purpose-built palettes (emerald, deep zinc, slate, amber, military olive).
2. **No Cardification of Plain Text**: Do NOT wrap every isolated paragraph or sentence in an elevated white card with heavy drop shadows. Group related functional items into disciplined semantic containers with subtle border strokes (`border border-border/60`).
3. **Exactly One Solid Action CTA per View**: Every screen, section, or modal must have exactly **ONE** primary solid button. Secondary actions must be ghost, outline, or text links. Prevent visual decision paralysis.
4. **Zero Dead Clicks**: Every button, navigation item, tab, and card must execute a concrete action, modal, or route. Never ship placeholder buttons with `href="#"` or unhandled `@click=""`.

---

## 4. Mandatory Auto-Update Loop

Whenever you perform work that introduces, refactors, or removes:
- Eloquent Models, Migrations, or Table Columns
- HTTP Routes, Middleware, or Controllers
- Services, AI Engines, or Tool Callers
- Filament Admin Resources or Pages
- SDUI Components or Vue Pages

You MUST immediately update:
1. [`.agent/ARCHITECTURE.md`](file:///.agent/ARCHITECTURE.md) — Document the updated system structure.
2. [`.agent/MEMORY.md`](file:///.agent/MEMORY.md) — Append a new entry to the rolling 10-entry memory log.
