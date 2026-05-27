#!/usr/bin/env bash
# bin/build-dist.sh — Build distributable tgz for harper.agency
#
# Usage:
#   ./bin/build-dist.sh              # uses version from plugin header
#   ./bin/build-dist.sh 1.4.0        # override version
#
# Output: dist/harper-wc-order-customer-tagger-{version}.tgz
#
# The archive extracts as:
#   wc-order-customer-tagger/        ← drop this folder into wp-content/plugins/

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN_SLUG="wc-order-customer-tagger"

# ── Resolve version ────────────────────────────────────────────────────────────
if [[ -n "${1:-}" ]]; then
    VERSION="$1"
else
    VERSION=$(grep -m1 'Version:' "$REPO_ROOT/${PLUGIN_SLUG}.php" | sed 's/.*Version: *//')
fi

if [[ -z "$VERSION" ]]; then
    echo "ERROR: could not determine version. Pass it as an argument: $0 1.4.0" >&2
    exit 1
fi

ARCHIVE_NAME="harper-wc-order-customer-tagger-${VERSION}.tgz"
DIST_DIR="$REPO_ROOT/dist"
BUILD_DIR="$(mktemp -d)"
trap 'rm -rf "$BUILD_DIR"' EXIT

echo "Building WC tagger v${VERSION} → dist/${ARCHIVE_NAME}"

# ── Copy plugin files ──────────────────────────────────────────────────────────
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"

rsync -a \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='dist/' \
    --exclude='bin/' \
    --exclude='tests/' \
    --exclude='vendor/phpunit*' \
    --exclude='vendor/phpspec*' \
    --exclude='vendor/sebastian*' \
    --exclude='vendor/myclabs*' \
    --exclude='vendor/phar-io*' \
    --exclude='vendor/nikic*' \
    --exclude='vendor/theseer*' \
    --exclude='*.md' \
    --exclude='*.lock' \
    --exclude='.gitignore' \
    --exclude='.editorconfig' \
    --exclude='phpunit*' \
    --exclude='phpcs*' \
    "$REPO_ROOT/" "$BUILD_DIR/$PLUGIN_SLUG/"

# ── Package ────────────────────────────────────────────────────────────────────
mkdir -p "$DIST_DIR"
tar -czf "$DIST_DIR/$ARCHIVE_NAME" -C "$BUILD_DIR" "$PLUGIN_SLUG"

echo "Done: $DIST_DIR/$ARCHIVE_NAME ($(du -sh "$DIST_DIR/$ARCHIVE_NAME" | cut -f1))"

# ── Verify contents ────────────────────────────────────────────────────────────
echo ""
echo "Top-level contents:"
tar -tzf "$DIST_DIR/$ARCHIVE_NAME" | grep -v '/$' | sed "s|^$PLUGIN_SLUG/||" | grep -v '^vendor/' | head -30
echo "..."
echo "Vendor packages included:"
tar -tzf "$DIST_DIR/$ARCHIVE_NAME" | grep '^wc-order-customer-tagger/vendor/[^/]*/[^/]*/$' | \
    sed "s|^$PLUGIN_SLUG/vendor/||;s|/$||" | sort
