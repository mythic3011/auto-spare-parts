#!/usr/bin/env bash
source "$(dirname -- "${BASH_SOURCE[0]}")/docker-common.sh"
[[ $# -eq 0 ]] || fail 'Usage: bootstrap.sh'
bash "$PROJECT_ROOT/scripts/init.sh"
bash "$PROJECT_ROOT/scripts/preflight.sh"
compose build
# Refresh dependencies before serving traffic; named volumes retain old packages.
compose run --rm --no-deps php composer install --no-interaction --prefer-dist --optimize-autoloader
compose up -d --no-build --wait --wait-timeout "${BOOTSTRAP_TIMEOUT:-180}"
compose exec -T nginx nginx -t
printf '%s\n' 'Bootstrap complete. Use docker compose ps to inspect the published web address.'
