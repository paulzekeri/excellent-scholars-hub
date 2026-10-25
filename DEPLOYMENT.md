# Deployment Guide — ESH LMS (cPanel / UAT / Production)

The production-like target is a **cPanel** account on `lit-grp.com` (server `147.124.220.162`,
panel at <https://cpanel.lit-grp.com>, SSH/FTP/cPanel ports open). Everything below is written for
that host but applies to any cPanel + MySQL + Apache environment.

| Environment | URL | App path (suggested) | Database | Demo accounts |
|---|---|---|---|---|
| Staging / UAT | `https://uat-esh.lit-grp.com` | `/home/CPANELUSER/esh-uat` | `CPANELUSER_esh_uat` | yes (`SEED_DEMO_ACCOUNTS=true`) |
| Production | `https://lms.lit-grp.com` | `/home/CPANELUSER/esh-live` | `CPANELUSER_esh_live` | **never** (`SEED_DEMO_ACCOUNTS=false`) |

> **Hostname note:** `uat_esh.lit-grp.com` (with an underscore) is **not a valid DNS hostname** and
> cPanel rejects underscores when creating subdomains. Use **`uat-esh.lit-grp.com`**. At the time of
> writing it does not resolve yet, so create it in *cPanel → Domains → Create A New Domain* first.

## 0. What the app needs

* **PHP 8.0 or 8.1** (Laravel 8 does not support 8.2+). Select it per-domain in
  *MultiPHP Manager*. Required extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`,
  `tokenizer`, `xml`, `ctype` (all standard on cPanel).
* **MySQL 5.7+/MariaDB 10.3+** database + user, created in *cPanel → MySQL® Databases*
  (grant ALL on the app database only).
* **Composer 2** on the server (`composer --version`). cPanel ships it; it is also bundled with
  the terminal. If it is missing, use the release-zip path in §4.
* **`upload_max_filesize` and `post_max_size` ≥ 100M** (*MultiPHP INI Editor*) because materials and
  submissions validate at `max:102400` (100 MB).
* **Free SSL** via *cPanel → SSL/TLS Status → Run AutoSSL* so `https://` works before you set
  `SESSION_SECURE_COOKIE=true`.
* No queue worker and no cron job are required: `QUEUE_CONNECTION=sync`, notifications are in-app,
  and password reset / email verification are disabled in `routes/web.php`.
* The UI loads Tailwind and Font Awesome from **CDNs** — visitors' browsers need outbound HTTPS.

## 1. Create the subdomain and database (cPanel UI)

1. **Domains → Create A New Domain** → `uat-esh.lit-grp.com`, **uncheck** "Share document root",
   document root `esh-uat/public`. If cPanel insists on `public_html/uat-esh`, accept it for now and
   use the symlink fallback in §3.
2. **MultiPHP Manager** → assign **PHP 8.1** (or 8.0) to `uat-esh.lit-grp.com`.
3. **MultiPHP INI Editor** → set `upload_max_filesize=100M`, `post_max_size=104M`,
   `max_execution_time=300`, `memory_limit=256M` for that domain.
4. **MySQL® Databases** → create database `esh_uat` and user `esh_uat` with a strong password; add
   the user to the database with **ALL PRIVILEGES**. cPanel prefixes both with your account name
   (e.g. `cpuser_esh_uat`) — note the full names for `.env`.
5. **SSL/TLS Status** → Run AutoSSL for `uat-esh.lit-grp.com` (needs DNS to resolve first).

## 2. Deploy over SSH (recommended)

The repository is <https://github.com/tcntiu-debug/excellent-scholars-hub> (branch `main`). The
server needs read access; use a **deploy key** or an HTTPS personal access token if the repo stays
private.

```bash
# 1. Get the code (first deploy)
cd ~
git clone https://github.com/LIT-GRP/excellent-scholars-hub.git esh-uat
cd esh-uat

# 2. PHP dependencies (vendor/ is git-ignored)
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Environment file — copy the UAT template and fill in the blanks
cp .env.uat.example .env
chmod 600 .env
#    edit .env: DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL, MAIL_FROM_ADDRESS
php artisan key:generate --force

# 4. Database — choose ONE of these:
#    a) fresh schema + demo data (typical UAT)
php artisan migrate --force --seed
#    b) reuse the local phpMyAdmin dump (import esh_lms.sql in phpMyAdmin first, then)
php artisan migrate --force

# 5. Public uploads (materials, submissions) + writable folders
php artisan storage:link
chmod -R ug+rwX storage bootstrap/cache

# 6. Caches (built for the current .env — rebuild after every .env change)
php artisan config:cache
php artisan view:cache
#    Do NOT run `php artisan route:cache` or `php artisan optimize`:
#    routes/web.php contains closures ('/' and '/help/user-guide') which cannot be cached.
```

### Later deploys (same server)

```bash
cd ~/esh-uat
git pull --ff-only origin main
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan storage:link        # no-op if the link exists
php artisan view:clear && php artisan view:cache
php artisan config:cache
php artisan esh:health
```

### Document root

The web root **must** be the `public/` folder. Two supported layouts:

* Preferred: the subdomain's document root is `/home/CPANELUSER/esh-uat/public`.
* If cPanel forces `public_html/uat-esh`, remove that folder and point it at the app instead:

```bash
rm -rf ~/public_html/uat-esh
ln -s ~/esh-uat/public ~/public_html/uat-esh
```

`.env`, `storage/`, `vendor/` and the database dump then stay outside `public_html` — never upload
`.env` into a web-served folder.

## 3. Deploy through the cPanel Git pipeline (`.cpanel.yml`)

The repository ships `.cpanel.yml`, which cPanel's **Git™ Version Control** runs on *Deploy HEAD
Commit*. Use it when you prefer "push → click deploy" over SSH.

1. *cPanel → Git™ Version Control → Create* → clone
   `https://github.com/tcntiu-debug/excellent-scholars-hub.git`, branch `main` (a private repo needs
   credentials in the clone URL).
2. Edit `.cpanel.yml` in your local clone: replace `CPANELUSER`, `PHP_BIN` and `COMPOSER_BIN`
   with the real values, commit and push.
3. Create `/home/CPANELUSER/esh-uat/.env` once (from `.env.uat.example`) and run
   `php artisan key:generate --force` over SSH — the pipeline never overwrites `.env`.
4. Point the `uat-esh.lit-grp.com` document root at `/home/CPANELUSER/esh-uat/public`
   (*Domains → Manage*), then run *Deploy HEAD Commit*.

The pipeline copies `app bootstrap config database public resources routes` + `artisan` +
`composer.json`/`composer.lock`, runs `composer install --no-dev`, `migrate --force`,
`storage:link`, rebuilds the config/view caches and ends with `esh:health`. Because it does not copy
`storage/`, learner uploads survive redeploys.

> `route:cache` / `optimize` are intentionally **not** in the pipeline: `routes/web.php` defines
> closures (`/` and `/help/user-guide`), which cannot be serialised.

## 4. Deploy without SSH (release zip via File Manager)

Use this if the host has no terminal access (Composer can also be run locally).

```powershell
# On Windows, from the repository root:
powershell -ExecutionPolicy Bypass -File deploy\build-release.ps1
# -> deploy\esh-uat-release.zip (~13 MB, vendor/ included, no .env and no esh_lms.sql)
```

1. *cPanel → File Manager* → create `esh-uat` **outside** `public_html` → upload the zip → *Extract*.
2. Create `.env` from `.env.uat.example` (File Manager → New File), fill in DB credentials and
   `APP_URL=https://uat-esh.lit-grp.com`.
3. Generate the key without a terminal — run this locally (PHP installed) and paste the result into
   `APP_KEY=`:
   ```powershell
   php -r "echo 'base64:'.base64_encode(random_bytes(32));"
   ```
4. Database: *phpMyAdmin* → select the UAT database → *Import* `esh_lms.sql` (local dump,
   MariaDB 10.4, 32 KB) — **or** skip the import and enable the cPanel Terminal just once for
   `php artisan migrate --force --seed`.
5. The `public/storage` link cannot be created from File Manager. If the Terminal is available run
   `php artisan storage:link`. If it is not, open a ticket with the host or add a small PHP script
   that calls `symlink('../storage/app/public', __DIR__.'/storage')` and delete it afterwards —
   **without** the link, material and submission downloads return 404.
6. Set the document root of `uat-esh.lit-grp.com` to `esh-uat/public` (or the symlink fallback in §2).

## 5. Smoke-test the UAT deployment

Run the built-in check first (works over SSH or cPanel Terminal):

```bash
cd ~/esh-uat && php artisan esh:health
```

It verifies PHP 7.3–8.1 + extensions, `APP_KEY`, `APP_URL`, DB connectivity, pending migrations,
writable `storage`/`bootstrap/cache`, the `public/storage` link, upload limits, an existing
CEO/admin login and — as a warning — leftover demo accounts. Exit code is non-zero when something
must be fixed.

Then walk through the app on `https://uat-esh.lit-grp.com`:

| # | Check | Expected |
|---|---|---|
| 1 | `https://uat-esh.lit-grp.com/login` | Login page loads with Tailwind styling and Font Awesome icons (CDN), padlock in the browser bar |
| 2 | Log in as `ceo@esh.com` / `password` | Admin dashboard, **Payments** link visible |
| 3 | Log in as `admin@esh.com` / `password` | Admin dashboard **without** Payments; `GET /admin/payments` returns **403** |
| 4 | `tutor@esh.com` | Tutor dashboard: schedules, attendance, assignments, materials |
| 5 | Upload a material, then open **Learner → Materials → Download** | File downloads from `/storage/materials/...` (proves `storage:link`) |
| 6 | `learner@esh.com` | Dashboard, classes, materials, assignment submit (file upload) |
| 7 | `parent@esh.com` | Parent dashboard: 5 stat tiles (Schedule, Pending, Completed, Materials, Missed Classes) and tabs (Schedule / Assignments / Materials / Attendance) |
| 8 | Click a child's stat tile / **View Full Details** | `GET /parent/child/{learner}/progress` opens only for the parent's own children; another parent's learner returns **404/403** |
| 9 | Messages (`/messages`) as parent ↔ CEO | Thread opens, notification badge decreases |
| 10 | `/help/user-guide` | Guide renders, includes the parent dashboard + missed-class help |
| 11 | Any broken URL | Laravel 404 page, never a stack trace (`APP_DEBUG=false`) |

## 6. Promoting UAT → production

1. **Back up first**: *cPanel → Backup* (or phpMyAdmin export) of the live DB, plus a copy of the
   current `~/esh-live` folder.
2. Create a **separate** database/user and subdomain (e.g. `lms.lit-grp.com`) — never point
   production at the UAT database.
3. Deploy the same commit (`main`) to `~/esh-live` with §2 or §3, but:
   * copy `.env.production.example` → `.env` (`APP_ENV=production`, `APP_DEBUG=false`,
     `LOG_LEVEL=warning`, `SEED_DEMO_ACCOUNTS=false`, real `APP_URL`);
   * run `php artisan migrate --force` **without** `--seed`;
   * create the first real login with `php artisan esh:create-admin --role=ceo`
     (it also creates the missing `admin/tutor/learner/parent` role rows);
   * run `php artisan storage:link` and `php artisan esh:health`.
4. Delete the demo accounts if the database was cloned from UAT: `ceo@esh.com`, `admin@esh.com`,
   `tutor@esh.com`, `learner@esh.com`, `parent@esh.com` (all use the password `password`).
   `esh:health` warns while they exist.
5. Enable AutoSSL, confirm `SESSION_SECURE_COOKIE=true`, and protect or remove the UAT subdomain
   (cPanel → *Directory Privacy*) so it is not indexed with test data.
6. Re-run the §5 table against production with real accounts, then keep UAT in sync by deploying the
   same commit.

## 7. Rollback & troubleshooting

| Symptom | Cause / fix |
|---|---|
| **500** on every page, blank screen | `storage/` or `bootstrap/cache` not writable (`chmod -R ug+rwX`), or `.env` missing/`APP_KEY` empty. Check `storage/logs/laravel.log`. |
| 500 after editing `.env` | Stale cached config: `php artisan config:clear && php artisan config:cache`. |
| `Whoops`/stack trace visible | `APP_DEBUG` is still `true` — set `false`. |
| `Unable to prepare route ... Uses Closure` | You ran `route:cache`/`optimize`; use `config:cache` + `view:cache` only. |
| Downloads 404 under `/storage/...` | `php artisan storage:link` was not run (or `public/storage` was copied as a real folder). |
| `SQLSTATE[HY000] [1045] Access denied` | Wrong DB user/password in `.env`, or the user is not attached to the database. |
| `Specified key was too long` on import | Old MySQL; the shipped dump targets MariaDB 10.4 / MySQL 5.7+. |
| CSS missing | Outbound CDN blocked for the visitor, or a Content-Security-Policy/`.htaccess` rule blocks `cdn.tailwindcss.com`. |
| Upload of a large material fails | `upload_max_filesize`/`post_max_size` below 100M in *MultiPHP INI Editor*. |
| Login redirects back to `/login` | `SESSION_SECURE_COOKIE=true` while the site is still served over `http://` — either finish AutoSSL or set it to `false`. |

**Rollback:** `git checkout <previous-commit>` in `~/esh-uat`, then `composer install --no-dev`,
`php artisan config:cache && php artisan view:cache`. Schema changes: `php artisan migrate:rollback
--step=1` (only migrations from the last batch). Restore the database from the pre-deploy dump if the
rollback touches data.


