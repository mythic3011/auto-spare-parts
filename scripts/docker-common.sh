#!/usr/bin/env bash
# Shared path resolution; never source .env as shell code.
set -euo pipefail
PROJECT_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ENV_FILE:-$PROJECT_ROOT/.env}"
compose() {
    docker compose --project-directory "$PROJECT_ROOT" --env-file "$ENV_FILE" -f "$PROJECT_ROOT/docker-compose.yml" "$@"
}
fail() { printf '%s\n' "$*" >&2; exit 1; }
