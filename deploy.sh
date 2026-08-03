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

# The default CLI php is 8.3 while the web server runs 8.4, and composer.lock
# pins platform php to 8.4 — so `composer install` under the default binary
# fails outright. Select an 8.4+ interpreter explicitly.
is84() { [ -x "$1" ] && "$1" -r 'exit(PHP_VERSION_ID >= 80400 ? 0 : 1);' 2>/dev/null; }

PHP_BIN="$(command -v php)"
if ! is84 "$PHP_BIN"; then
    PHP_BIN=""
    for cand in /opt/alt/php84/usr/bin/php /opt/alt/php85/usr/bin/php \
                /usr/bin/php8.4 /usr/bin/php84; do
        if is84 "$cand"; then PHP_BIN="$cand"; break; fi
    done
fi
[ -n "$PHP_BIN" ] || {
    echo "ERROR: no PHP 8.4+ interpreter found; composer.lock requires it." >&2
    exit 1
}
COMPOSER_BIN="$(command -v composer)"
echo "==> PHP: $("$PHP_BIN" -r 'echo PHP_VERSION;') ($PHP_BIN)"

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
DB_NAME="$(envval DB_DATABASE)"

# Hand-parsing .env for the password proved unreliable — it differs subtly from
# what the app actually uses, and mysql rejected it while PDO connected fine.
# Let Laravel's own dotenv supply the values instead, and let PHP write the
# option file so quoting is exact: values are quoted because this password
# contains '#', which MySQL's parser would otherwise read as a comment.
CNF="$(mktemp)"
trap 'rm -f "$CNF"' EXIT
chmod 600 "$CNF"

CNF_PATH="$CNF" "$PHP_BIN" -r 'require "vendor/autoload.php"; $d = Dotenv\Dotenv::createImmutable(__DIR__); $d->safeLoad(); $e = function ($v) { return addcslashes((string) $v, "\\\""); }; file_put_contents(getenv("CNF_PATH"), "[client]\nhost=\"" . $e($_ENV["DB_HOST"] ?? "127.0.0.1") . "\"\nport=\"" . $e($_ENV["DB_PORT"] ?? "3306") . "\"\nuser=\"" . $e($_ENV["DB_USERNAME"] ?? "") . "\"\npassword=\"" . $e($_ENV["DB_PASSWORD"] ?? "") . "\"\n");'

if ! MYSQL_ERR="$(mysql --defaults-file="$CNF" -e 'SELECT 1;' "$DB_NAME" 2>&1 >/dev/null)"; then
    echo "ERROR: cannot connect to database '$DB_NAME'." >&2
    echo "       mysql said: $MYSQL_ERR" >&2
    exit 1
fi

BACKUP=~/backups/karmaa_db_$STAMP.sql.gz
mysqldump --defaults-file="$CNF" \
    --single-transaction --quick --no-tablespaces \
    "$DB_NAME" | gzip > "$BACKUP"

# gzip sits at the end of a pipeline, so check mysqldump's own status.
[ "${PIPESTATUS[0]}" -eq 0 ] || { echo "ERROR: mysqldump failed." >&2; exit 1; }
[ -s "$BACKUP" ] || { echo "ERROR: backup file is empty." >&2; exit 1; }
echo "    saved $BACKUP ($(du -h "$BACKUP" | cut -f1))"

echo "==> Pulling latest code ..."
git fetch origin
git reset --hard origin/main

echo "==> Installing PHP dependencies ..."
"$PHP_BIN" "$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction

echo "==> Running migrations ..."
"$PHP_BIN" artisan migrate --force
REMOTE

# --- ship the built assets -------------------------------------------------

echo "==> Uploading media (images, video, favicons) ..."
# Media is gitignored — large binaries would bloat every clone forever — so it
# cannot arrive via git pull and must be shipped here. Additive by design: no
# --delete, so files uploaded through the admin panel are never destroyed.
tar -czf - -C public \
    $( [ -d public/images ] && echo images ) \
    $(cd public 2>/dev/null && ls *.png *.ico *.jpg *.jpeg *.svg *.webp 2>/dev/null) \
    2>/dev/null | ssh "$SSH_ALIAS" "
set -eu
WEBROOT=\"\$(dirname '$APP_DIR')/public_html\"
[ -f \"\$WEBROOT/index.php\" ] || WEBROOT='$APP_DIR/public'
cd \"\$WEBROOT\"
tar -xzf -
" && echo "    media synced" || echo "    (no media to sync)"

echo "==> Uploading assets ..."
# Hostinger serves the site from public_html, a real directory beside the app
# dir whose index.php bootstraps the checkout and calls usePublicPath(): PHP
# comes from the git checkout, but assets — including the Vite manifest — are
# resolved from public_html/build. Shipping assets to the checkout's public/
# leaves the live site on the old manifest, so target the real webroot.
#
# The remote script is passed as an argument, not a heredoc: a heredoc would
# occupy ssh's stdin, leaving the tar stream with nowhere to go (the remote
# tar then reads the script text and reports "not in gzip format").
#
# Extract beside the live directory and swap, so the site is never left
# without a build/ folder while the transfer is in flight.
tar -czf - -C public build | ssh "$SSH_ALIAS" "
set -eu
WEBROOT=\"\$(dirname '$APP_DIR')/public_html\"
if [ -f \"\$WEBROOT/index.php\" ] && grep -q \"\$(basename '$APP_DIR')\" \"\$WEBROOT/index.php\"; then
    :  # public_html wraps this app — deploy assets there
else
    WEBROOT='$APP_DIR/public'
fi
echo \"    webroot: \$WEBROOT\"
cd \"\$WEBROOT\"
rm -rf build.incoming build.previous
mkdir build.incoming
tar -xzf - -C build.incoming --strip-components=1
if [ -d build ]; then mv build build.previous; fi
mv build.incoming build
rm -rf build.previous
"

# --- server: rebuild caches ------------------------------------------------

ssh "$SSH_ALIAS" "bash -s -- '$APP_DIR'" <<'REMOTE'
set -euo pipefail
APP_DIR="$1"
cd "$APP_DIR"

is84() { [ -x "$1" ] && "$1" -r 'exit(PHP_VERSION_ID >= 80400 ? 0 : 1);' 2>/dev/null; }
PHP_BIN="$(command -v php)"
if ! is84 "$PHP_BIN"; then
    for cand in /opt/alt/php84/usr/bin/php /opt/alt/php85/usr/bin/php \
                /usr/bin/php8.4 /usr/bin/php84; do
        if is84 "$cand"; then PHP_BIN="$cand"; break; fi
    done
fi

echo "==> Rebuilding caches ..."
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan queue:restart

echo "==> Deployed $(git rev-parse --short HEAD)"
REMOTE

echo "==> Verifying site ..."
curl -sS -o /dev/null -m 30 -w "    HTTP %{http_code}\n" "$SITE_URL"
