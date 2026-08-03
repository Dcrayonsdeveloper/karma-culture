#!/usr/bin/env bash
#
# Karmaa Kulture — production deploy
#
# Live site : https://palegreen-mouse-158092.hostingersite.com/
# App root  : ~/domains/palegreen-mouse-158092.hostingersite.com/karmaa_culture
# Server    : u322703740@167.88.41.35:65002 (Hostinger, shared account)
#
# This account hosts several unrelated live sites (foreverkids, jikra, gryt,
# spjbeauty, getsetnova, siriusshoppe, justburgers, timbertrust). The project
# directory is therefore located by looking for the Karmaa Kulture app itself
# rather than by a hard-coded path — a wrong path here overwrites someone
# else's production site.
#
# The server has git, composer and php but NO node/npm, and /public/build is
# gitignored. Assets are therefore built here and shipped over ssh.
#
# Usage:  ./deploy.sh [ssh-alias]        (default alias: karmaakulture)
#
set -euo pipefail

SSH_ALIAS="${1:-karmaakulture}"
SITE_URL="https://palegreen-mouse-158092.hostingersite.com/"

# --- local preflight -------------------------------------------------------

for tool in npm git ssh tar; do
    command -v "$tool" >/dev/null 2>&1 || {
        echo "ERROR: '$tool' is not installed locally. Aborting." >&2
        exit 1
    }
done

git fetch origin --quiet
LOCAL_HEAD="$(git rev-parse HEAD)"
ORIGIN_HEAD="$(git rev-parse origin/main)"
if [ "$LOCAL_HEAD" != "$ORIGIN_HEAD" ]; then
    echo "ERROR: local HEAD does not match origin/main." >&2
    echo "       local     $LOCAL_HEAD" >&2
    echo "       origin    $ORIGIN_HEAD" >&2
    echo "       The server deploys origin/main, so assets built here would" >&2
    echo "       not match the deployed code. Push or check out main first." >&2
    exit 1
fi

echo "==> Locating Karmaa Kulture on ${SSH_ALIAS} ..."

# Identify the app root by its .env, which is unique to this project. Nothing
# is written until a single unambiguous match is found.
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

# --- build assets locally --------------------------------------------------

echo "==> Building assets locally ..."
[ -d node_modules ] || npm ci --no-audit --no-fund
npm run build

[ -d public/build ] || {
    echo "ERROR: npm run build produced no public/build directory." >&2
    exit 1
}

# --- server: backup, pull, dependencies, migrate ---------------------------
#
# APP_DIR is passed as a positional argument so the heredoc can stay quoted:
# every variable below is expanded on the server, not here.

ssh "$SSH_ALIAS" "bash -s -- '$APP_DIR'" <<'REMOTE'
set -euo pipefail
APP_DIR="$1"
cd "$APP_DIR"

for tool in git composer php; do
    command -v "$tool" >/dev/null 2>&1 || {
        echo "ERROR: '$tool' not available on the server. Aborting." >&2
        exit 1
    }
done

[ -d .git ] || {
    echo "ERROR: $APP_DIR is not a git repository — this script pulls from" >&2
    echo "       origin/main and cannot deploy a directory uploaded by FTP." >&2
    exit 1
}

mkdir -p ~/backups
STAMP="$(date +%Y%m%d_%H%M%S)"

# Uncommitted server-side edits would be destroyed by the reset below. Keep
# them as a patch rather than discarding them silently.
if [ -n "$(git status --porcelain)" ]; then
    PATCH=~/backups/server_local_changes_$STAMP.patch
    git diff > "$PATCH" || true
    echo "==> NOTE: server had uncommitted changes; saved to $PATCH"
    git status --short | sed 's/^/    /'
fi

# Read a value from .env, stripping surrounding quotes and trailing whitespace.
envval() {
    sed -n "s/^$1=//p" .env | head -n1 \
        | sed -e 's/[[:space:]]*$//' -e 's/^"\(.*\)"$/\1/' -e "s/^'\(.*\)'\$/\1/"
}

echo "==> Backing up database ..."
# mysqldump rejects a process substitution for --defaults-file, so use a real
# file. mktemp creates it 0600; the trap removes it even if the dump fails.
CNF="$(mktemp)"
trap 'rm -f "$CNF"' EXIT
chmod 600 "$CNF"
printf '[client]\nuser=%s\npassword=%s\n' "$(envval DB_USERNAME)" "$(envval DB_PASSWORD)" > "$CNF"

BACKUP=~/backups/karmaa_db_$STAMP.sql.gz
mysqldump --defaults-file="$CNF" --single-transaction --quick --no-tablespaces \
    "$(envval DB_DATABASE)" | gzip > "$BACKUP"
rm -f "$CNF"
trap - EXIT
echo "    saved $BACKUP ($(du -h "$BACKUP" | cut -f1))"

echo "==> Pulling latest code ..."
git fetch origin
git reset --hard origin/main

echo "==> Installing PHP dependencies ..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Running migrations ..."
php artisan migrate --force
REMOTE

# --- ship the built assets -------------------------------------------------

echo "==> Uploading assets ..."
# Extract beside the live directory and swap, so the site is never left
# without a build/ folder while the transfer is in flight.
tar -czf - -C public build | ssh "$SSH_ALIAS" "bash -s -- '$APP_DIR'" <<'REMOTE'
set -euo pipefail
APP_DIR="$1"
cd "$APP_DIR/public"
rm -rf build.incoming
mkdir build.incoming
tar -xzf - -C build.incoming --strip-components=1
rm -rf build.previous
if [ -d build ]; then mv build build.previous; fi
mv build.incoming build
rm -rf build.previous
REMOTE

# --- server: rebuild caches ------------------------------------------------

ssh "$SSH_ALIAS" "bash -s -- '$APP_DIR'" <<'REMOTE'
set -euo pipefail
APP_DIR="$1"
cd "$APP_DIR"

echo "==> Rebuilding caches ..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart

echo "==> Deployed $(git rev-parse --short HEAD)"
REMOTE

echo "==> Verifying site ..."
curl -sS -o /dev/null -m 30 -w "    HTTP %{http_code}\n" "$SITE_URL"
