#!/usr/bin/env bash
#
# Karmaa Kulture — production deploy
#
# Live site : https://palegreen-mouse-158092.hostingersite.com/
# Server    : u322703740@167.88.41.35:65002 (Hostinger, shared account)
#
# This account hosts several unrelated live sites (foreverkids, jikra, gryt,
# spjbeauty, getsetnova, siriusshoppe, justburgers, timbertrust). The project
# directory is therefore located by looking for the Karmaa Kulture app itself
# rather than by a hard-coded path — a wrong path here overwrites someone
# else's production site.
#
# Usage:  ./deploy.sh [ssh-alias]        (default alias: karmaakulture)
#
set -euo pipefail

SSH_ALIAS="${1:-karmaakulture}"

echo "==> Locating Karmaa Kulture on ${SSH_ALIAS} ..."

# Identify the app root by its session cookie name, which is unique to this
# project. Nothing is written until a single unambiguous match is found.
APP_DIR="$(ssh "$SSH_ALIAS" 'bash -s' <<'REMOTE'
set -euo pipefail
shopt -s nullglob
found=()
for artisan in "$HOME"/domains/*/*/artisan "$HOME"/domains/*/artisan "$HOME"/*/artisan; do
    dir="$(dirname "$artisan")"
    [ -f "$dir/.env" ] || continue
    if grep -qi 'karmaa' "$dir/.env" 2>/dev/null; then
        found+=("$dir")
    fi
done
printf '%s\n' "${found[@]}" | sort -u
REMOTE
)"

if [ -z "$APP_DIR" ]; then
    echo "ERROR: no Karmaa Kulture install found. Inspect manually:" >&2
    echo "  ssh $SSH_ALIAS \"ls ~/domains/\"" >&2
    exit 1
fi

if [ "$(printf '%s\n' "$APP_DIR" | wc -l)" -gt 1 ]; then
    echo "ERROR: multiple candidates found — refusing to guess:" >&2
    printf '  %s\n' $APP_DIR >&2
    exit 1
fi

echo "==> Found: $APP_DIR"
read -rp "Deploy origin/main here? [y/N] " confirm
[ "$confirm" = "y" ] || { echo "Aborted."; exit 1; }

ssh "$SSH_ALIAS" "bash -s" <<REMOTE
set -euo pipefail
cd "$APP_DIR"

echo "==> Backing up database ..."
mkdir -p ~/backups
php artisan db:backup 2>/dev/null || \
  mysqldump --defaults-file=<(printf '[client]\nuser=%s\npassword=%s\n' \
    "\$(grep -m1 '^DB_USERNAME=' .env | cut -d= -f2-)" \
    "\$(grep -m1 '^DB_PASSWORD=' .env | cut -d= -f2-)") \
    "\$(grep -m1 '^DB_DATABASE=' .env | cut -d= -f2-)" \
    | gzip > ~/backups/karmaa_\$(date +%Y%m%d_%H%M%S).sql.gz

echo "==> Pulling latest code ..."
git fetch origin
git reset --hard origin/main

echo "==> Installing dependencies ..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Building assets ..."
npm ci && npm run build

echo "==> Running migrations ..."
php artisan migrate --force

echo "==> Rebuilding caches ..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart

echo "==> Done."
REMOTE

echo "==> Verifying site ..."
curl -sS -o /dev/null -w "HTTP %{http_code}\n" \
  https://palegreen-mouse-158092.hostingersite.com/
