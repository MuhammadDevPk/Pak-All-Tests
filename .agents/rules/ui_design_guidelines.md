# 🎨 Senior UI/UX Design Guidelines & Anti-AI Fingerprint Standards

This document establishes the mandatory visual, architectural, and responsive design guidelines for all frontend code, Vue 3 components, and Server-Driven UI (SDUI) templates in **Pak-All-Tests**.

---

## 1. 🚫 Eradicate the "AI Visual Fingerprint" (Known AI UI Symptoms)

AI models have developed a recognizable, generic visual fingerprint due to training on average web templates. **Every UI component in this repository must actively reject these tropes:**

| AI Symptom | What AI Generates (BANNED ❌) | Senior Designer Standard (ENFORCED ✅) |
|---|---|---|
| **The "Eyebrow Pill"** | A generic capsule/pill with 10% opacity tint and 100% saturated text above every title. | Use semantic typography hierarchy, crisp micro-labels (`text-xs font-mono uppercase tracking-wider text-muted-foreground`), or subtle border accents without colored pill clutter. |
| **The Violet/Blue 135° Gradient** | Low-opacity violet-to-blue background halos and hero gradient blobs. | Use deliberate physical lighting, crisp background tones, clean slate surfaces, subtle grid meshes, or high-contrast crisp card boundaries. |
| **"Cardification" of Everything** | Every single paragraph, stat, testimonial, and text block wrapped in floating cards with light borders & dropshadows. | Structure pages using intentional negative space, typography scale, horizontal rule dividers (`border-b border-border/40`), and background tint variation. Cards are reserved strictly for discrete interactive entities. |
| **Indecisive CTAs** | Equal-weight side-by-side buttons (e.g., solid button next to outline button of identical width/weight). | Strict visual hierarchy: **One primary solid CTA per viewport**. Secondary actions use ghost buttons, subtle text links, or smaller icon triggers. |
| **The 3-Card Lucide Icon Grid** | Exactly 3 feature boxes with 2px Lucide icons centered in soft pastel squares. | Organic, data-dense layouts. Icons integrated directly alongside text without childish colored background squares. Vary stroke weight and opacity. |
| **Uniform Border Radius** | Blindly applying `rounded-xl` (`12px` / `16px`) to cards, nested buttons, inner inputs, and badges alike. | **Mathematical Nested Radius Rule**: Inner radii must scale proportionally to outer container radius and padding: $R_{\text{inner}} = \max(0, R_{\text{outer}} - \text{Padding})$. |
| **"Dead" Dark Mode** | Pure `#000000` or `#0A0A0A` pitch black with harsh 10% white borders. | **Rich Atmospheric Dark Mode**: Deep slate/navy undertones (e.g. `hsl(222, 47%, 7%)`), layered elevation surfaces, and glowing accent borders (`border-sidebar-border/60`). |
| **Generic Sparkles / Trust Badges** | Floating stars, sparkle emoji/icons, or vague "AI Powered" marketing badges. | Concrete capability metrics (e.g. "84% Accuracy", "Timed: 20 Mins", "Sub-second verification"). |

---

## 2. 🏛️ Domain Aesthetic Brief: "Academic Rigor & Military Precision"

**Pak-All-Tests** is an exam preparation platform for high-stakes Pakistani examinations (PMA Long Course, ISSB, CSS Competitive Exams, MDCAT, Armed Forces Initial Tests).

### The Aesthetic Identity:
* **Tone**: Authoritative, focused, distraction-free, structured, and institutional.
* **Data Density**: High efficiency. Candidates need to see timer countdowns, question maps, mark schemes, and status indicators without unnecessary scrolling or fluffy whitespace.
* **Tactile Authenticity**: Interactive elements must look and feel responsive. Physical elevation rules: buttons depress (`active:scale-[0.98]`), active navigation tabs show clear bottom indicators, active stepper nodes glow with primary borders.

---

## 3. 📐 Mathematical Design System Rules

### A. Nested Border Radius Formula
When placing elements inside containers, never use the same border-radius. Follow the concentric curvature formula:
$$\text{Inner Radius} = \max(0, \text{Outer Radius} - \text{Padding})$$

* If container has `rounded-2xl` (16px) with `p-4` (16px padding) $\rightarrow$ Inner element must have `rounded-none` (0px) or `rounded-sm` (2px–4px).
* If container has `rounded-xl` (12px) with `p-2` (8px padding) $\rightarrow$ Inner button must have `rounded-sm` (4px).

### B. Typography & Font Hierarchy
* **Display / Headings**: Crisp font weights (`font-bold`, `tracking-tight`), strict heading sizes (`h1` = `text-2xl md:text-3xl`, `h2` = `text-lg md:text-xl`, `h3` = `text-base font-semibold`).
* **Technical & Metrics**: Monospace fonts (`font-mono`) for Timers, Question Codes (`Q-VERB-001`), Word Counts, Percentages, and Math formulas.
* **Body / Prompts**: High-legibility sans-serif with comfortable line-height (`leading-relaxed`, `text-sm text-foreground/90`).

### C. Color Architecture (HSL Token System)
* **Background Canvas**: Tailored slate/zinc undertones in light mode; rich deep navy-slate in dark mode (`dark:bg-background dark:border-sidebar-border`).
* **Primary Brand**: Deep Emerald/Teal (military/academic heritage) or Sharp Indigo/Slate.
* **Success/Pass States**: Emerald (`text-emerald-500`, `bg-emerald-500/10`, `border-emerald-500/30`).
* **Danger/Low Time Alerts**: Ruby/Rose (`text-destructive`, `bg-destructive/10`, animated pulse on `<120s`).

---

## 4. 📱 Full Multi-Device Responsive Design Mandate

Every layout, SDUI renderer, player, and administrative page **MUST be completely responsive** across all device viewports:

```
┌─────────────────┬───────────────────┬──────────────────────────────────────────┐
│ Device Category │ Breakpoint Range  │ Layout Strategy                          │
├─────────────────┼───────────────────┼──────────────────────────────────────────┤
│ Mobile Portrait │ 320px – 480px     │ 1-column stack, bottom action bar,       │
│                 │ (base / max-sm)   │ min 44px tap targets, sticky headers     │
├─────────────────┼───────────────────┼──────────────────────────────────────────┤
│ Mobile Landscape│ 481px – 767px     │ Compact 2-column grids, horizontal tabs  │
│ / Phablet       │ (sm:)             │ with touch scrolling (`overflow-x-auto`) │
├─────────────────┼───────────────────┼──────────────────────────────────────────┤
│ Tablet          │ 768px – 1023px    │ 2-to-3 column grids, split MCQ navigator │
│                 │ (md:)             │ and sticky action sidebars               │
├─────────────────┼───────────────────┼──────────────────────────────────────────┤
│ Laptop/Desktop  │ 1024px – 1439px   │ Multi-column steppers, full PDF iframe   │
│                 │ (lg: / xl:)       │ split panes, rich data tables            │
├─────────────────┼───────────────────┼──────────────────────────────────────────┤
│ Ultrawide / 4K  │ 1440px+ (2xl:)    │ Constrained max-width (`max-w-7xl mx-auto│
│                 │                   │ to prevent unreadable long text lines)   │
└─────────────────┴───────────────────┴──────────────────────────────────────────┘
```

### Critical Mobile & Responsive Rules:
1. **Touch Targets**: All buttons, question navigation pills, radio choices, and tab selectors must maintain a minimum hit area of **$44\times44\text{px}$** or adequate spacing (`gap-2` to `gap-3`) on touch devices.
2. **Horizontal Overflow Ergonomics**: When rendering multi-phase tabs or steppers on small screens, enable horizontal smooth scrolling with hidden ugly scrollbars:
   ```html
   <div class="flex overflow-x-auto scrollbar-none pb-2 -mx-4 px-4 sm:mx-0 sm:px-0">
   ```
3. **Sticky Action Bars on Mobile**: In test execution mode (`McqPlayer`), place the timer and submit button in a sticky top/bottom viewport anchor so candidates never have to scroll 1000px to submit or check time.
4. **No Content Clipping / Zero Horizontal Body Scroll**: Every container must enforce `w-full max-w-full overflow-hidden` or `break-words` on long question prompts.
5. **Fluid Typography & Spacing**: Use responsive Tailwind utilities (`p-4 md:p-6 lg:p-8`, `text-lg md:text-xl`).

---

## 5. 🛠️ Interactive SDUI Component Implementation Patterns

### A. MCQ Test Player (`McqPlayer.vue`)
* **Option Buttons**: Full-width selectable cards with keyboard letter badges (`A`, `B`, `C`, `D`). Clear radio indicator circle on the right.
* **Question Navigator Bar**: Wrap numeric pills in a responsive flex grid (`flex flex-wrap gap-1.5`) with distinct color codes:
  * *Unvisited*: `bg-sidebar-accent text-muted-foreground`
  * *Answered*: `bg-primary/20 text-primary font-semibold`
  * *Current Active*: `ring-2 ring-primary ring-offset-2 bg-primary text-primary-foreground font-bold`
* **Urgent Timer**: When `remainingSeconds <= 120`, apply `animate-pulse text-destructive bg-destructive/10`.

### B. PDF & Resource Viewer (`PdfViewer.vue` / `ResourceList.vue`)
* **Responsive Height**: Iframe container height must be dynamic (`h-[450px] sm:h-[600px] lg:h-[750px]`).
* **Fullscreen Portals**: Fullscreen toggle must use fixed inset positioning (`fixed inset-2 sm:inset-4 z-50 shadow-2xl bg-card`) with a clean exit button.

### C. Subjective & Essay Uploader (`SubjectiveUpload.vue`)
* **Drag-and-Drop Area**: Clear state transitions (`isDragging` $\rightarrow$ primary dashed border, `selectedFile` $\rightarrow$ emerald border with file size and remove action).
* **Word Count Progress**: Interactive word count meter with visual feedback against target limits.

### D. Analytics & Progress Rings (`ProgressCard.vue`)
* **SVG Circle Calculations**: Dynamic SVG dashoffset calculation with explicit `viewBox="0 0 80 80"` for retina sharpness across high-DPI and mobile screens.

---

## 6. 🔄 The 3-Pass Refinement Strategy for New UI Code

When writing or reviewing any frontend component, follow this sequential 3-pass process:

1. **Pass 1: Semantic Structure (Layout & Data Density)**
   - Lay out semantic HTML5 elements (`<header>`, `<main>`, `<section>`, `<nav>`, `<button>`).
   - Establish responsive grid columns (`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4`).
   - Forbid arbitrary cardification; use clean horizontal borders where appropriate.

2. **Pass 2: Visual Style & Brand Typography**
   - Inject the tailored HSL palette tokens (`text-foreground`, `bg-card`, `border-sidebar-border`).
   - Verify that there is only **ONE primary solid CTA** in the view.
   - Enforce proportional border radius math.

3. **Pass 3: Micro-Interactions & Responsive Ergonomics**
   - Test mobile viewport (375px) and ultra-wide viewport (1920px).
   - Add hover, focus-visible ring, and active scale states (`transition-all duration-150 active:scale-[0.99]`).
   - Verify keyboard accessibility and clear visual focus rings (`focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none`).
