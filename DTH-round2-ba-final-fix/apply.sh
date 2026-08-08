#!/usr/bin/env bash
set -euo pipefail

SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TARGET="${1:-marketing-email-laravel-v12}"

if [ ! -f "$TARGET/artisan" ]; then
  echo "ERROR: Target does not look like Laravel project: $TARGET" >&2
  echo "Usage: bash DTH-round2-ba-final-fix/apply.sh marketing-email-laravel-v12" >&2
  exit 1
fi

for dir in app database docs lang resources routes tests; do
  if [ -d "$SELF_DIR/$dir" ]; then
    mkdir -p "$TARGET/$dir"
    cp -R "$SELF_DIR/$dir/." "$TARGET/$dir/"
  fi
done

echo "Applied BA Round 2 overlay to: $TARGET"
echo "Next: cd $TARGET && php artisan migrate && php artisan optimize:clear"
