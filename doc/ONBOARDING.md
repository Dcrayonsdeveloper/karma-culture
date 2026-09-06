# Onboarding — clone to production

Everything a new developer needs, from `git clone` to shipping a change to the
live site. Written against the repository as it actually is, not the generic
Laravel version of these steps.

> **The rest of `doc/` was written for ForeverKids**, a different site this
> project was copied from. Treat its server paths and hosting details as
> belonging to that site, not this one. For Karmaa Kulture, this file is the
> authoritative source.

**Live site:** see `APP_URL` in the production `.env`. Deliberately not named
here - this repository is public, and where the shop runs is not something a
clone should advertise.

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
it. It has to reach the server by some route other than git.

### Real data (optional)

The seeders give you a working shop with dummy content. If you need production-
shaped data, ask whoever administers the live server for a dump, then:

```bash
gunzip -c karmaa_db_<stamp>.sql.gz | mysql -u root karmaculture
```

Treat that dump as customer data: it carries real names, addresses and orders.

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

**There is no deployment path in this repository.**

Nothing in this repository points at the production server: no deploy script,
no CI workflow, no host, no credentials. That is on purpose - the repository is
public, and hostnames, addresses and account names in a public repo are a map
for anyone looking for one. Whoever administers the server holds those details.

A deploy script and an `ssh/` directory used to live here. They pointed at a
shared account belonging to a *different* site, which a stray deploy could have
damaged, so they were removed rather than left as a trap. They remain in git
history if anyone needs to read them.

To deploy, ask the server administrator. What you need from them:

- how the instance receives code (SSH from a workstation, a pull on the server,
  or a pipeline held elsewhere),
- where the application lives on that instance,
- which PHP binary and which database it uses.

Keep those out of this repository. `main` is the source of truth for code.

### What a deploy has to do, whatever runs it

1. Build assets: `npm run build`. `public/build` is gitignored, so it never
   arrives via git and the site 500s with "Vite manifest not found" without it.
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan migrate --force`
4. Rebuild the caches: `config:cache`, `route:cache`, `view:cache`
5. Restart the queue worker
6. Sync `public/images`, also gitignored

---

## 6. Things that will bite you

- **The rest of `doc/` is stale.** It documents ForeverKids paths and hosting.
  Do not follow its server instructions for this site.
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
  not found", the built assets never reached the server — they do not travel
  with git.
