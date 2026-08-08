#!/usr/bin/env bash
set -euo pipefail
SRC_DIR="$(cd "$(dirname "$0")" && pwd)"
TARGET_DIR="${1:-$(pwd)}"
if [ ! -d "$TARGET_DIR/marketing-email-laravel-v12" ]; then
    echo "Target must be the DTH-business-core repository root." >&2
    exit 1
fi
cp -R "$SRC_DIR/marketing-email-laravel-v12/." "$TARGET_DIR/marketing-email-laravel-v12/"
echo "Configuration fix files copied to: $TARGET_DIR"
echo "Next: cd marketing-email-laravel-v12 && php artisan migrate && php artisan optimize:clear"
