#!/usr/bin/env bash
set -euo pipefail
TARGET="${1:-marketing-email-laravel-v12}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cp -R "$SCRIPT_DIR/app/." "$TARGET/app/"
echo "Applied admin Marketing/Email navigation fix to $TARGET"
