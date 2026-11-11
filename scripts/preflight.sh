#!/usr/bin/env bash
source "$(dirname -- "${BASH_SOURCE[0]}")/docker-common.sh"
[[ $# -eq 0 ]] || fail 'Usage: preflight.sh'
command -v docker >/dev/null || fail 'Docker is required.'
docker compose version >/dev/null || fail 'Docker Compose v2 or newer is required.'
docker compose up --help | grep -q -- --wait || fail 'Docker Compose with up --wait support is required.'
[[ -r "$ENV_FILE" ]] || fail 'Missing environment file. Run ./scripts/init.sh first.'
for path in Dockerfile composer.lock public/index.php resources/docker/nginx/nginx.conf sql-scripts/setup.sql resources/py/Dockerfile; do
    [[ -r "$PROJECT_ROOT/$path" ]] || fail "Missing required source: $path"
done
compose config --quiet
docker info >/dev/null || fail 'Docker daemon is unavailable.'
printf '%s\n' 'Preflight passed: source files, environment, Compose configuration and Docker daemon.'
