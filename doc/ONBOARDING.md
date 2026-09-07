# Onboarding — clone to production

Everything a new developer needs, from `git clone` to shipping a change to the
live site. Written against the repository as it actually is, not the generic
Laravel version of these steps.

> The ForeverKids deployment documents this project was forked with - `deploy.sh`,
> `doc/deployment-guide.md`, `doc/hostinger-deployment.md`, `ssh/DEPLOYMENT.md`
> and `ssh/README.md` - have been removed. They described a Hostinger account
> that no longer serves this site, and they carried a live database password in
> plain text. This file is the authoritative source.

**Live site:** https://karma.dcrayons.app
**Repository:** AWS CodeCommit, `ap-south-1` (§2). GitHub is no longer used.

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
git clone https://git-codecommit.ap-south-1.amazonaws.com/v1/repos/karma-kulture
cd karma-kulture
```

Authenticate with the CodeCommit HTTPS Git credentials issued to you in IAM -
a username ending `-at-<account-id>` and its own password, separate from your
console login. The GitHub repository this project used to live in is no longer
maintained; do not push to it.

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
    HostName 15.207.133.144
    User ubuntu
    IdentityFile ~/.ssh/your-karmaa-key.pem
    IdentitiesOnly yes
```

Confirm it works:

```bash
ssh karmaakulture 'echo connected'
```

### Deploy

There is no deploy script. `deploy.sh` was written for the Hostinger account and
has been removed; it could not find the app under `/var/www` and failed before
doing anything. Deploy by hand:

```bash
git push origin HEAD:main
ssh karmaakulture 'cd /var/www/karmaakulture \
    && git pull --ff-only \
    && npm run build \
    && php artisan migrate --force \
    && php artisan optimize:clear'
```

Notes on each part:

- **Assets build on the server.** Node 20 and `node_modules` are already there,
  so nothing is uploaded — which also means you cannot ship JS built from
  unfinished local edits.
- **`composer install` only when `composer.lock` changed**, and it needs
  `--ignore-platform-reqs`: `composer.json` pins `platform.php` to 8.4.11 while
  the box runs PHP **8.3**. `php artisan` itself is fine on 8.3.
- **Take a database backup first** if the deploy carries migrations. Nothing
  does it for you any more:
  `mysqldump -u <user> -p<pass> --single-transaction karmaakulture_db | gzip > ~/backup.sql.gz`
- **Check the server tree is clean before pulling.** Code has historically been
  rsynced onto this box by hand, so `git status` there is not always empty. A
  pull onto a dirty tree conflicts; a `reset --hard` destroys whatever was
  loose. Look first.

### After deploying

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://karma.dcrayons.app/
ssh karmaakulture 'cd /var/www/karmaakulture && git rev-parse --short HEAD'
```

The second command should print the commit you just deployed.

### If something breaks

```bash
# what the server logged (daily files, not laravel.log)
ssh karmaakulture 'cd /var/www/karmaakulture \
    && tail -c 4000 storage/logs/laravel-$(date +%Y-%m-%d).log'

# nginx, for 502s and missing assets
ssh karmaakulture 'sudo tail -50 /var/log/nginx/karma.dcrayons.app.error.log'
```

To roll back: check out the last good commit on `main`, push it, and deploy
again.

> The box also hosts roughly twenty other sites on two vCPUs, sharing MySQL,
> PostgreSQL and Redis. It is not yours alone — do not restart shared services.

---

## 6. Things that will bite you

- **Most of `doc/` predates the move to AWS.** The deployment documents have
  been removed, but the rest still describes the Hostinger layout in places.
  Treat this file as authoritative for anything about servers or deploying.
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
