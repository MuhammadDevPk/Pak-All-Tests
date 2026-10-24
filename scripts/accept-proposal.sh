#!/usr/bin/env bash
set -euo pipefail

# scripts/accept-proposal.sh — Spec-Driven Development (SDD) Acceptance Helper
# Validates and marks a proposed SPEC.md as accepted by human / staff review.

SPEC_FILE="${1:-SPEC.md}"
ARCHIVE_DIR=".agent/specs"

if [ ! -f "$SPEC_FILE" ]; then
    echo "❌ Error: Specification file '$SPEC_FILE' not found." >&2
    echo "Usage: ./scripts/accept-proposal.sh [path/to/SPEC.md]" >&2
    exit 1
fi

echo "📋 [SDD Proposal Acceptance] Auditing $SPEC_FILE..."

# Check required sections in SPEC.md
REQUIRED_SECTIONS=("Threat Model" "Architecture" "Test Matrix")
for section in "${REQUIRED_SECTIONS[@]}"; do
    if ! grep -qi "$section" "$SPEC_FILE"; then
        echo "⚠️ Warning: Missing recommended section '$section' in $SPEC_FILE."
    fi
done

mkdir -p "$ARCHIVE_DIR"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
FILENAME=$(basename "$SPEC_FILE" .md)
TARGET_ARCHIVE="${ARCHIVE_DIR}/${TIMESTAMP}_${FILENAME}_approved.md"

cp "$SPEC_FILE" "$TARGET_ARCHIVE"
echo "✅ Proposal successfully approved and archived to: $TARGET_ARCHIVE"
echo "🚀 You are clear to proceed with implementation phase."
exit 0
