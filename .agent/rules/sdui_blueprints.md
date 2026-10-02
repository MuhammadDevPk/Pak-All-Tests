# Server-Driven UI (SDUI) & Dynamic Blueprint Standards

> **Status**: ACTIVE ARCHITECTURAL STANDARD  
> **Domain**: Dynamic Schema Blueprinting & Component Synthesis  
> **Location**: `.agent/rules/sdui_blueprints.md`

---

## 1. Core Architecture
We use a **hybrid SDUI layout system**. The visual layout, structure, tabs, and columns of the candidate dashboard are defined as a JSON schema in the `domain_flows` database table, while the individual interactive items (tests, practice sets, documents) are fetched dynamically from the `content_modules` table.

---

## 2. Dynamic Slots (`moduleQuery`)
To ensure unlimited horizontal scaling without requiring manual schema updates whenever new content modules are created, structural layout nodes (`SubjectGrid` and `ResourceList`) MUST support dynamic auto-population via a `moduleQuery` property.

### Schema Blueprint Contract
```json
{
  "type": "SubjectGrid",
  "props": {
    "columns": 2,
    "moduleQuery": {
      "module_type": "preparation",
      "slug_contains": "verbal",
      "merge": "append",
      "active": true
    }
  }
}
```

### Merging Contracts
The payload hydrator MUST support three distinct merging strategies when combining hardcoded layout items with dynamic query results:
1. **`replace`** (Default): Discards any manual items and populates only dynamically matched modules.
2. **`append`**: Renders manual items first, followed by dynamic query results.
3. **`prepend`**: Renders dynamic query results first, followed by manual items.

### Strict Deduplication
To prevent rendering duplicate dashboard cards, the hydrator must perform a single-pass linear scan to filter out duplicate modules based on their unique `moduleId`, preserving the first encountered instance while retaining presentation-only helper cards (which do not have a `moduleId`).
