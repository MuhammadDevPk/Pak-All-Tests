#!/usr/bin/env bash
set -euo pipefail

# scripts/harness-check.sh — Production AI Harness Verification Script
# Audits harness presence, single source of truth, memory entry limits, and script permissions.

echo "🛡️  [Harness Check] Auditing AI Harness integrity..."

FAILED=0

# 1. Verify Root Governance & IDE Wrappers
REQUIRED_ROOT_FILES=("AGENTS.md" ".cursorrules" "CLAUDE.md")
for file in "${REQUIRED_ROOT_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        echo "❌ FAIL: Missing root harness file: $file"
        FAILED=1
    fi
done

# Verify wrappers reference @AGENTS.md
for wrapper in ".cursorrules" "CLAUDE.md"; do
    if [ -f "$wrapper" ] && ! grep -q "@AGENTS.md" "$wrapper"; then
        echo "❌ FAIL: $wrapper does not reference @AGENTS.md"
        FAILED=1
    fi
done

# 2. Verify Core .agent/ Hierarchy
REQUIRED_AGENT_FILES=(
    ".agent/AGENT.md"
    ".agent/ARCHITECTURE.md"
    ".agent/PATTERNS.md"
    ".agent/QUIRKS.md"
    ".agent/MEMORY.md"
    ".agent/LIBRARIAN.md"
)
for file in "${REQUIRED_AGENT_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        echo "❌ FAIL: Missing core harness file: $file"
        FAILED=1
    fi
done

# 3. Verify Memory Max 10 Entries Law
if [ -f ".agent/MEMORY.md" ]; then
    ENTRY_COUNT=$(grep -c "^### Entry " ".agent/MEMORY.md" || true)
    if [ "$ENTRY_COUNT" -gt 10 ]; then
        echo "❌ FAIL: .agent/MEMORY.md exceeds the 10-entry limit (Found: $ENTRY_COUNT entries). Move older entries to MEMORY_ARCHIVE.md."
        FAILED=1
    else
        echo "✅ MEMORY.md entry count valid ($ENTRY_COUNT/10 entries)."
    fi
fi

# 4. Verify No Orphan / Legacy Rule Directories
if [ -d ".agent/rules" ]; then
    echo "❌ FAIL: Legacy directory .agent/rules/ still exists. All rules must be consolidated into the core harness."
    FAILED=1
fi

if [ -f "docs/INSTRUCTIONS.md" ]; then
    echo "❌ FAIL: Legacy file docs/INSTRUCTIONS.md still exists."
    FAILED=1
fi

# 5. Verify Executable Scripts
SCRIPTS=(
    "scripts/accept-proposal.sh"
    "scripts/harness-check.sh"
    ".agent/hooks/stop.sh"
)
for script in "${SCRIPTS[@]}"; do
    if [ -f "$script" ] && [ ! -x "$script" ]; then
        echo "❌ FAIL: Script is not executable: $script (run: chmod +x $script)"
        FAILED=1
    fi
done

if [ "$FAILED" -ne 0 ]; then
    echo "💥 Harness check FAILED with errors above." >&2
    exit 1
fi

echo "✨ AI Harness verification PASSED completely. All governance contracts intact."
exit 0
