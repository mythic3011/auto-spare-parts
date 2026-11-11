#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"

required_dirs=(
  "apps/api"
  "apps/web"
  "docker"
  "storage/media"
  "docs/architecture"
  "docs/schema"
  "docs/api"
  "docs/testing"
  "docs/archive"
  "tests/manual"
)

missing=0
for dir in "${required_dirs[@]}"; do
  if [[ ! -d "$PROJECT_ROOT/$dir" ]]; then
    echo "Missing required directory: $dir" >&2
    missing=1
  fi
done

for file in project README.md public/index.php apps/api/public/index.php resources/php/api/index.php sql-scripts/setup.sql; do
  if [[ ! -f "$PROJECT_ROOT/$file" ]]; then
    echo "Missing required entrypoint: $file" >&2
    missing=1
  fi
done

if [[ "$missing" -ne 0 ]]; then
  exit 1
fi

echo "Monorepo structure check passed."
