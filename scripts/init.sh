#!/usr/bin/env bash
# Create local configuration once; never alter existing credentials or data.
source "$(dirname -- "${BASH_SOURCE[0]}")/docker-common.sh"
if [[ -e "$ENV_FILE" ]]; then
    printf '%s\n' 'Environment file already exists; left unchanged.'
    exit 0
fi
command -v openssl >/dev/null || fail 'Install openssl to generate the local database password.'
password="$(openssl rand -hex 24)"
umask 077
# noclobber also protects against concurrent initialization.
(set -o noclobber; sed "s/^DB_DOCKER_PASSWORD=.*/DB_DOCKER_PASSWORD=$password/" "$PROJECT_ROOT/.env.example" > "$ENV_FILE")
printf '%s\n' 'Created local environment configuration with a generated database password.'
