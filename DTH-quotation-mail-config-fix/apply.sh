#!/usr/bin/env bash
set -euo pipefail

if [ "$#" -ne 1 ]; then
  echo "Usage: bash DTH-quotation-mail-config-fix/apply.sh /path/to/marketing-email-laravel-v12"
  exit 1
fi

TARGET="$1"
SOURCE="$(cd "$(dirname "$0")" && pwd)"

if [ ! -f "$TARGET/artisan" ]; then
  echo "Target does not look like Laravel project: $TARGET"
  exit 1
fi

for dir in app config database; do
  if [ -d "$SOURCE/$dir" ]; then
    cp -R "$SOURCE/$dir/." "$TARGET/$dir/"
  fi
done

echo "Quotation mail configuration fix applied to: $TARGET"
echo "Next: cd $TARGET && php artisan migrate && php artisan optimize:clear"
