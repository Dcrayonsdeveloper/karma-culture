# Onboarding — clone to production

Everything a new developer needs, from `git clone` to shipping a change to the
live site. Written against the repository as it actually is, not the generic
Laravel version of these steps.

> **The rest of `doc/` and `ssh/README.md` describe ForeverKids**, a different
> site that happens to share the Hostinger account this project was copied
> from. Their server paths will overwrite that site. For Karmaa Kulture, this
> file and `deploy.sh` are the only authoritative sources.

**Live site:** https://palegreen-mouse-158092.hostingersite.com/

---

## 1. Prerequisites

| Tool | Version | Why this one |
|------|---------|--------------|
| PHP | **8.4+** | `composer.json` pins `config.platform.php` to `8.4.11`, so `composer install` refuses to run on 8.2/8.3 — it is not advisory |
| Composer | 2.x | |
| MySQL / MariaDB | 8.0 / 10.6+ | |
| Node.js | **20+** | Vite 6 + Tailwind 4; Node 20.20 is what the project is built with |
| Git | any recent | |

On Windows, XAMPP ships PHP 8.3 — too old. Install PHP 8.4 separately and use
that binary for `composer` and `artisan` (on the current dev box that is
`D:\tools\php-8.4\php.exe`).

Verify before going further:

```bash
php -v          # must say 8.4.x
node -v         # must say v20+ (or newer)
mysql --version
```

---

## 2. First-time setup

```bash
git clone https://github.com/Dcrayonsdeveloper/karma-culture.git
cd karma-culture
```

**Create the two databases first** — the setup script migrates immediately and
will fail without one, and the test suite needs its own:

```sql
CREATE DATABASE karmaculture   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE dcommerce_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then:

```bash
composer setup
```

That one command runs: `composer install` → copy `.env.example` to `.env` →
`artisan key:generate` → `artisan migrate --force` → `npm install` →
`npm run build`.

Before it can migrate you need the DB credentials in `.env`, so if it fails on
the migrate step, fill these in and re-run `php artisan migrate`:

```
DB_DATABASE=karmaculture
DB_USERNAME=root
DB_PASSWORD=
APP_URL=http://127.0.0.1:8000
```

Three things `composer setup` does **not** do:

```bash
php artisan storage:link   # uploaded media 404s without it
php artisan db:seed        # categories, products, settings, legal pages, admins
cp .env.example .env.testing   # then set DB_DATABASE=dcommerce_test in it
```

`.env.testing` must point at `dcommerce_test` and **never** at your dev
database — the suite uses `RefreshDatabase`, which drops every table it finds.

### Log in

```bash
composer dev     # server + queue worker + log tail + vite, all at once
```

- Storefront: http://127.0.0.1:8000
- Admin: http://127.0.0.1:8000/admin — `admin@example.com` / `password`
- Second seeded account: `manager@example.com` / `password`

(Seeded credentials, dev only. Production admins are real accounts.)

### Why the site looks image-less

`public/images/` is gitignored — roughly 105 MB of binaries that would bloat
every clone forever. A fresh checkout therefore has no product photography, and
broken image frames locally are expected, not a bug. Options: upload a few
images through the admin panel, or copy `public/images/` from someone who has
it. `deploy.sh` syncs that directory to production separately from git.

### Real data (optional)

The seeders give you a working shop with dummy content. If you need production-
shaped data, every deploy writes a database backup on the server:

```bash
ssh karmaakulture 'ls -lh ~/backups/karmaa_db_*.sql.gz | tail -5'
scp karmaakulture:~/backups/karmaa_db_<stamp>.sql.gz .
gunzip -c karmaa_db_<stamp>.sql.gz | mysql -u root karmaculture
```

Requires the SSH access set up in section 5. Treat that dump as customer data.

---

## 3. Day-to-day

```bash
composer dev                    # everything (recommended)
php artisan serve               # or just the web server
npm run dev                     # or just Vite, for hot-reloaded CSS/JS

composer test                   # config:clear + full suite
php artisan test --filter=SomeTest
```

Blade/PHP changes are picked up on refresh. CSS/JS changes need `npm run dev`
running, or `npm run build` for a one-off.

---

## 4. Making a change

```bash
git checkout -b fix/short-description
# ... edit, then:
php artisan test --filter=WhateverCovers   # relevant tests, not the whole suite
git commit -m "fix(scope): what changed and why"
git push -u origin fix/short-description
```

Conventions the repo follows: `type(scope): summary` (`fix(storefront):`,
`feat(admin/products):`, `chore:`), and a body that explains *why*, since the
diff already shows the what.

Merge to `main` when reviewed. `main` is what production runs.

---

## 5. Deploying to production

### One-time SSH access

Deployment goes over SSH to Hostinger, so your key has to be on that account.

```bash
ssh-keygen -t ed25519 -C "your-name@karmaa"     # if you have no key
cat ~/.ssh/id_ed25519.pub                        # send this to the account owner
```

Once the key is authorised, add this to `~/.ssh/config`:

```
Host karmaakulture
    HostName 167.88.41.35
    Port 65002
    User u322703740
    IdentityFile ~/.ssh/id_ed25519
    IdentitiesOnly yes
```

Confirm it works — the deploy script will not run without it:

```bash
ssh karmaakulture 'echo connected'
```

### Deploy

```bash
git checkout main
git pull
./deploy.sh karmaakulture       # answer y at the confirmation prompt
```

The script refuses to start unless **local `HEAD` equals `origin/main`**, so
commit and push before deploying. Roughly five minutes; most of it is the asset
build.

### What it actually does

1. Verifies `HEAD == origin/main` and pins the deploy to that SHA
2. Finds the app on the server by looking for a `.env` mentioning "karmaa" —
   never by hard-coded path, because the account hosts several other live sites
3. Backs up the production database to `~/backups/karmaa_db_<stamp>.sql.gz`
4. Builds assets **locally** (`npm run build`) and ships `public/build`
5. Server: `git reset --hard <deployed SHA>`, `composer install --no-dev`,
   `php artisan migrate --force`
6. Syncs `public/images` and the webroot `.htaccess`
7. Rebuilds config/route/view caches, restarts the queue
8. Curls the site and reports the HTTP status

Note step 4: assets come from **your working tree**, while PHP comes from git.
Deploy from a clean tree, or you ship JS/CSS built from unfinished local edits
against a backend that does not have them.

### After deploying

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://palegreen-mouse-158092.hostingersite.com/
ssh karmaakulture 'cd ~/domains/palegreen-mouse-158092.hostingersite.com/karmaa_culture && git rev-parse --short HEAD'
```

The second command should print the commit you just deployed.

### If something breaks

```bash
# what the server logged (daily files, not laravel.log)
ssh karmaakulture 'cd ~/domains/palegreen-mouse-158092.hostingersite.com/karmaa_culture \
    && tail -c 4000 storage/logs/laravel-$(date +%Y-%m-%d).log'
```

To roll back: check out the last good commit on `main`, push it, and deploy
again. Database backups from every deploy are in `~/backups/` on the server.

---

## 6. Things that will bite you

- **`doc/` and `ssh/README.md` are stale.** They document ForeverKids paths.
  Deploying by following them writes to the wrong site.
- **`composer install` on PHP 8.3 fails** with a platform error. It is the
  `config.platform` pin, not a missing extension — install 8.4.
- **Tests drop tables.** `.env.testing` must name a throwaway schema.
- **PayU and Shiprocket credentials are not in `.env`.** They live in the
  Settings table and are edited in the admin panel. Same for the AI assistant's
  API key, which can come from either `ANTHROPIC_API_KEY` or admin settings —
  the chat widget only renders once one of them is set.
- **Never pipe `artisan` output through `head`/`tail`** on Windows; the PHP
  process is left running detached and races whatever you run next. Redirect to
  a file instead.
- **`public/build` is gitignored.** If the live site 500s with "Vite manifest
  not found", an asset upload was interrupted — re-run the deploy.
