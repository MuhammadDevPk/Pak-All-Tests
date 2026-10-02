# UI_REFERENCE.md — Persistent Visual Memory & Design Token Reference

> **Status**: ACTIVE DESIGN REFERENCE  
> **Applicability**: All candidate-facing portals, test players, landing pages, and administrative interfaces  
> **Workspace**: Pak-All-Tests (Vue 3.5 • Tailwind CSS 4 • Reka UI)

---

## 1. Visual Identity & Brand Philosophy

- **Core Aesthetic**: *"Academic Rigor & Military Precision"* — disciplined, distraction-free, high data density, authoritative, and institutional.
- **Brand Palette (Provisional)**:
  - **Light Mode Canvas**: Crisp `#ffffff` and `#fafafa` background surfaces.
  - **Dark Mode Canvas**: Deep navy-slate (`hsl(222, 47%, 7%)`) with rich atmospheric undertones. Never pure `#000000`.
  - **Primary Brand Accent**: Deep Emerald (`#059669` / `hsl(160, 84%, 39%)`) representing academic and military heritage, paired with Sharp Indigo (`hsl(226, 70%, 55%)`) for secondary emphasis.
  - **Borders & Dividers**: Soft structural gray borders (`border-border/60` / `#e2e8f0` in light mode, `hsl(217, 33%, 17%)` in dark mode).
  - **Status Accents**:
    - *Success / Correct*: Emerald (`#10b981`)
    - *Destructive / Danger / Urgent Timer (<120s)*: Ruby Rose (`#f43f5e`)
    - *Warning / Review*: Amber (`#f59e0b`)

---

## 2. Typography Hierarchy

- **Primary Sans**: Modern high-legibility sans-serif (Inter, Outfit, or system stack: `-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif`).
- **Technical & Metric Monospace**: `font-mono` (`ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace`) strictly for:
  - Exam timers and countdown clocks (`00:19:45`)
  - Question codes (`Q-VERB-001`)
  - Marks, percentages, and accuracy scores (`84.5%`, `+1.00 / -0.25`)
  - Mathematical formulas and equation notations
- **Scale & Weight**:
  - Headings: `font-bold tracking-tight text-foreground` (`h1: text-2xl md:text-3xl`, `h2: text-lg md:text-xl`)
  - Body: `text-sm leading-relaxed text-foreground/90`
  - Micro-Labels: `text-xs font-mono uppercase tracking-wider text-muted-foreground`

---

## 3. Elevation, Radius & Micro-Interactions

- **Curvature Hierarchy (Nested Radius)**:
  - Outer Cards / Modals: `rounded-xl` (12px) to `rounded-2xl` (16px)
  - Inner Buttons / Inputs / Badges: Scaled using concentric formula $R_{\text{inner}} = \max(0, R_{\text{outer}} - \text{Padding})$ (`rounded-md` or `rounded-sm`).
- **Shadows**: Subtle, physical elevation (`shadow-xs`, `shadow-sm`, and `shadow-md` for floating toolbars). Banned: heavy, blurry, saturated drop shadows.
- **Button Micro-Interactions**:
  - Depress on click: `active:scale-[0.98]`
  - Smooth state transitions: `transition-all duration-150`
  - Focus accessibility: `focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none`

---

## 4. Reference Screenshots & Artifact Tracker

### ## TODO: Required Visual Screenshots
The user needs to supply and attach high-fidelity design screenshots for persistent visual alignment:

- [ ] **1. Domain Landing & Dashboard**: Candidate view showing subject grids, category stepper, and active course progress.
- [ ] **2. Interactive MCQ Player (`McqPlayer.vue`)**: Active question card, option selection states (A/B/C/D), urgent countdown timer, and bottom/top sticky action bar.
- [ ] **3. Socratic AI Tutor Drawer (`AiTutorWidget.vue`)**: Slide-over drawer, streaming chat bubbles, tool indicator ("Searching domain knowledge..."), and Roman Urdu pedagogical guidance.
- [ ] **4. Payment Proof Upload & Verification View (`PendingOrInactive.vue`)**: EasyPaisa / JazzCash instruction card, TRX input, receipt preview container.
- [ ] **5. Filament Administrative Review Queue**: Admin receipt triage table with embedded slip image zoom and approve/reject actions.

*When attaching new screenshots, save to `.agent/assets/` or document artifact paths here with corresponding design notes.*

---

## 5. Changelog

- **2026-10-03**: Established canonical `UI_REFERENCE.md` visual memory file. Codified provisional tokens (emerald/indigo, light `#fafafa`/dark navy-slate, Inter/system font, nested border radii, and 5 required screenshot tracking items).
