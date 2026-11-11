#!/usr/bin/env bash
set -euo pipefail

echo "Checking for hardcoded API PHP paths outside shared endpoint registry..."

hardcoded_api_hits="$(
  rg -n "resources/php/[A-Za-z0-9_./-]+\\.php" resources/js page \
    --glob "*.js" \
    --glob "*.html" \
    --glob "!resources/js/module/config/endpoints.js" \
    --glob "!resources/js/module/config/endpoints.global.js" \
    --glob "!resources/js/module/cartApi.js" \
    || true
)"

fallback_literal_hits="$(
  rg -n "\\|\\|\\s*['\"][^'\"]*(resources/php|/api/)[^'\"]*['\"]" resources/js page \
    --glob "*.js" \
    --glob "*.html" \
    || true
)"

if [[ -n "$hardcoded_api_hits" ]]; then
  echo "Found hardcoded API PHP path usage:" >&2
  echo "$hardcoded_api_hits" >&2
fi

if [[ -n "$fallback_literal_hits" ]]; then
  echo "Found endpoint fallback literals that bypass shared endpoint registry:" >&2
  echo "$fallback_literal_hits" >&2
fi

if [[ -n "$hardcoded_api_hits" || -n "$fallback_literal_hits" ]]; then
  exit 1
fi

echo "Hardcoded API path check passed."
