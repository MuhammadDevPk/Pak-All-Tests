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

## 2. Russian Market & Localization Standards (RU / CIS)

When building public portals, enterprise SaaS modules, marketing pages, or client integration endpoints targeting the Russian / CIS market, strictly enforce the following localization standards:

### A. Clean Cyrillic Typography Standards
1. **Typographic Quotation Marks**: Never use ASCII straight quotes (`"..."`). Always use Russian typographic quotation marks (chevrons): `«...»`. For quotes inside quotes, use paws: `«... „...“ ...»`.
2. **Non-Breaking Spaces Before Prepositions**: Never allow short prepositions and conjunctions (в, на, с, по, о, к, из, от, у, за, и, а, но) to hang at the end of a line. Always bind them to the next word with a non-breaking space (`&nbsp;` in HTML / Blade, `\u00A0` in strings).
3. **Russian Pluralization Rules**: Never concatenate words like `5 дней(я)`. Always apply proper 3-form Slavic pluralization:
   ```php
   // 1 день (one), 2-4 дня (few), 5-20 дней (many)
   function pluralize_ru(int $number, string $one, string $few, string $many): string
   {
       $n = abs($number) % 100;
       $n1 = $n % 10;
       if ($n > 10 && $n < 20) {
           return $many;
       }
       if ($n1 > 1 && $n1 < 5) {
           return $few;
       }
       if ($n1 === 1) {
           return $one;
       }
       return $many;
   }
   ```

### B. 5-Second Clarity (Business Copy Standard)
* Any landing page, feature description, pricing block, or checkout flow must be understandable by a non-technical business owner or operations manager in **under 5 seconds**.
* Ban engineering jargon (`payload`, `endpoint`, `SDUI schema`, `entity`) from user-facing copy.
* Use authoritative, concise, disciplined Russian business terminology:
  - *Bad*: «Инновационный омниканальный пайплайн для автоматизации рутины»
  - *Good*: «Приём платежей и мгновенная выдача доступов к тестам»

### C. Formatting & Regional Conventions
* **Phone Masking**: Standard Russian phone mask: `+7 (XXX) XXX-XX-XX` with clean numeric unmasking (`79XXXXXXXXX`) on backend persistence.
* **Date & Time**: European date formatting: `DD.MM.YYYY` (e.g. `19.09.2026`) and 24-hour time (`HH:mm`).
* **Currency Formatting**: Format Russian Rubles with a non-breaking space between thousands and the official symbol: `1 000 ₽` (never `1000rub` or `1000 р.`).
* **Regional Gateways & Ecosystems**: When integrating regional services, follow standard contracts:
  - *Payment Gateways*: Yookassa (ЮKassa), Tinkoff (T-Bank), Robokassa (webhook signature validation, idempotent callbacks).
  - *Address & Company Validation*: DaData API (clean INN, KPP, OGRN, Bik, dadata address suggestions).
  - *Enterprise Sync*: 1C:Enterprise (CommerceML, XML/REST asynchronous queuing).

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
