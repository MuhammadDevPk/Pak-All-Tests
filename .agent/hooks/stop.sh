#!/usr/bin/env bash
set -euo pipefail

# .agent/hooks/stop.sh — Pre-Conclusion Verification Hook
# Validates repository state before concluding an agent session.

echo "🔍 [AI Harness] Executing stop hook pre-conclusion checks..."

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

# 1. Check for blocked destructive command artifacts
if git diff --cached --name-only | grep -E "(\.env|\.pem|\.key)$" >/dev/null 2>&1; then
    echo "❌ ERROR: Sensitive secrets or key files detected in staged git changes!" >&2
    exit 1
fi

# 2. Check harness integrity
if [ -f "scripts/harness-check.sh" ]; then
    bash "scripts/harness-check.sh"
else
    echo "⚠️ Warning: scripts/harness-check.sh not found."
fi

echo "✅ [AI Harness] Stop hook checks passed cleanly."
exit 0
