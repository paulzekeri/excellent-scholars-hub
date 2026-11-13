# Deployment Guide — ESH LMS (cPanel / production + UAT)

The repository lives at **https://github.com/paulzekeri/excellent-scholars-hub** (moved from
`tcntiu-debug/excellent-scholars-hub`). Both environments are **cPanel** accounts with MySQL and Apache
(example host `147.124.220.162`, panel <https://cpanel.lit-grp.com>, SSH/FTP ports open); everything
below applies to any cPanel host.

| Environment | Branch | URL | App folder | Demo accounts |
|---|---|---|---|---|
| Production | `main` | `https://eshglobal.org` | `/home/<account>/eshglobal.org` (document root) | **never** (`SEED_DEMO_ACCOUNTS=false`) |
| UAT | `uat` | `https://uat.eshglobal.org` | `/home/<account>/uat.eshglobal.org` | yes (`SEED_DEMO_ACCOUNTS=true`) |

> **DNS + SSL first:** both domains must resolve to their server and have certificates
> (*SSL/TLS Status → Run AutoSSL*) before `SESSION_SECURE_COOKIE=true` — and before the install button /
> phone alerts can work, since service workers require HTTPS.

## Moving the repository to the `paulzekeri` account

1. On GitHub: repository → *Settings → General → Danger Zone → Transfer ownership* to `paulzekeri`.
   GitHub keeps redirects from the old URL, so existing clones keep working.
   (Alternative: create an empty `paulzekeri/excellent-scholars-hub` and `git push --mirror` to it.)
2. In this workspace the remote is already updated:
   `git remote -v` → `https://github.com/paulzekeri/excellent-scholars-hub.git`.
3. **Secrets and variables are per-repository** — create them again in the new repo
   (*Settings → Secrets and variables → Actions*): production `CPANEL_*` + `ENV_CONTENT`, UAT
   `UAT_CPANEL_*` + `UAT_ENV_CONTENT` (see §2.1 and §6).
4. Enable Actions, keep `main` as the default branch, and create a **`uat`** branch for UAT deploys.

## 0. What the app needs

* **PHP 8.4** (`ea-php84`). Laravel 13 supports PHP 8.3 - 8.5 and 8.4 is what SmartWeb's
  *MultiPHP Manager* offers; PHP 8.4 gets security fixes until **31 Dec 2028**. Select it
  per-domain in *MultiPHP Manager*. Required extensions: `pdo_mysql`, `mbstring`, `openssl`,
  `fileinfo`, `tokenizer`, `xml`, `ctype` (all standard on cPanel).
* **MySQL 5.7+/MariaDB 10.3+** database + user, created in *cPanel → MySQL® Databases*
  (grant ALL on the app database only).
* **No Composer on the server**: the Actions workflow builds `vendor/` on the runner (§2) and the
  release zip ships it (§4). Only the manual SSH path (§2.4) and `.cpanel.yml` (§3) need `composer`
  on the account, where cPanel's Terminal provides it.
* **`upload_max_filesize` and `post_max_size` ≥ 100M** because materials and submissions validate at
  `max:102400` (100 MB). The committed `.user.ini` sets 100M/104M/256M; confirm the same values in
  *MultiPHP INI Editor* if the account ignores `.user.ini`.
* **Free SSL** via *cPanel → SSL/TLS Status → Run AutoSSL* so `https://` works before you set
  `SESSION_SECURE_COOKIE=true`.
* No queue worker is required: `QUEUE_CONNECTION=sync`, notifications are in-app, and password reset /
  email verification are disabled in `routes/web.php`. **One cron entry is needed for phone alerts**
  (`* * * * * php artisan schedule:run`) — without it the install button still works, but Web Push
  messages sit in the `push_messages` outbox until `push:send` runs. See `PWA.md`.
* The UI loads Tailwind and Font Awesome from **CDNs** — visitors' browsers need outbound HTTPS.

## 1. Create the domain and database (cPanel UI)

1. **Domains → Create A New Domain** → `eshglobal.org`, and keep the document root cPanel
   proposes (`/home/litgrpco/eshglobal.org`). The repository ships a root `.htaccess` that
   forwards every request into `public/`, so the document root does **not** have to be changed and
   `.env`, `app/`, `vendor/` stay unreachable from a URL. Pointing the document root straight at
   `public/` also works if cPanel lets you set it.
2. **MultiPHP Manager** → assign **PHP 8.4** (`ea-php84`) to `eshglobal.org`.
3. **MultiPHP INI Editor** → set `upload_max_filesize=100M`, `post_max_size=104M`,
   `max_execution_time=300`, `memory_limit=256M` for that domain.
4. **MySQL® Databases** → create database `esh_uat` and user `esh_uat` with a strong password; add
   the user to the database with **ALL PRIVILEGES**. cPanel prefixes both with your account name
   (e.g. `cpuser_esh_uat`) — note the full names for `.env`.
5. **SSL/TLS Status** → Run AutoSSL for `eshglobal.org` (needs DNS to resolve first).

## 2. Deploy on every push (GitHub Actions) — recommended

`.github/workflows/deploy-cpanel.yml` publishes production on every push to `main`;
`.github/workflows/deploy-cpanel-uat.yml` publishes UAT on every push to `uat`. Both build `vendor/`,
mirror the staged tree over SFTP and then run the artisan steps on the server. Nothing has to be
installed on the account and no SSH session is opened by hand.

### 2.1 One-time repository setup

*Settings → Secrets and variables → Actions* in
<https://github.com/paulzekeri/excellent-scholars-hub>:

| Kind | Name | Value |
|---|---|---|
| Secret | `CPANEL_HOST` | host of the account that serves `eshglobal.org` |
| Secret | `CPANEL_USER` | that cPanel username |
| Secret | `CPANEL_PASSWORD` | its cPanel/SSH password |
| Secret (optional) | `ENV_CONTENT` | the complete production `.env` as text — written once, only when `.env` is missing (CRLF stripped, `chmod 600`) |
| Variable (optional) | `CPANEL_SSH_PORT` | `22` (the default) |
| Variable (optional) | `CPANEL_REMOTE_DIR` | `eshglobal.org/` (the default) |
| Secret | `UAT_CPANEL_HOST` / `UAT_CPANEL_USER` / `UAT_CPANEL_PASSWORD` | the same three for `uat.eshglobal.org` (§6) |
| Secret (optional) | `UAT_ENV_CONTENT` | the UAT `.env` body (from `.env.uat.example`) |
| Variable (optional) | `UAT_CPANEL_REMOTE_DIR` | `uat.eshglobal.org/` (the default) |

### 2.2 What a deploy does

1. `composer install --no-dev --optimize-autoloader` on the runner, so `vendor/` needs no server shell.
2. `rsync` a staged tree, then `lftp` mirrors it to `~/eshglobal.org/` over SFTP. Never
   uploaded: `.env`, `storage/` uploads, `public/storage`, `bootstrap/cache/*.php`, `.well-known`.
3. Prunes exactly the files `git rm` removed since the last successful run.
4. If `~/eshglobal.org/.env` is missing **and** the optional `ENV_CONTENT` secret is set,
   writes it once from that secret (`chmod 600`, Windows CRLF stripped). An existing `.env` is never
   modified; without the secret the step prints a note and moves on.
5. Over SSH (the same shell *cPanel → Terminal* uses): `config:clear`, `migrate --force --seed`,
   `storage:link`, `config:cache`, `view:cache`, `esh:health`. If the account refuses shell commands
   the step only warns and prints the commands to paste into *cPanel → Terminal*. A missing `.env`
   stops the step with `::error title=No .env on the server` before touching the database.
6. Writes a summary table to the workflow run page.

#### One-time data steps after the usernames migration

`2026_10_29_000001_add_username_to_users_table` adds the columns but deliberately does not invent
credentials. Run these once per environment (UAT and production) in *cPanel → Terminal*:

```bash
# Preview only — writes nothing
php artisan esh:backfill-usernames

# Write the usernames, then hand the printed table to the families
php artisan esh:backfill-usernames --apply

# Children with no guardian email cannot receive a reset link
php artisan esh:audit-guardian-links
```

`esh:audit-guardian-links` exits non-zero when it finds a child whose guardian has no email; fix those
children from **Admin → Users → Edit** before relying on guardian-delivered password resets.

### 2.3 Server `.env` — created once, never deployed

Every deploy deliberately skips `.env`, so the file has to exist on the server before the first
`migrate`. When it is missing, Laravel silently falls back to the defaults in `config/database.php`
and the run fails with:

```text
SQLSTATE[HY000] [1045] Access denied for user 'forge'@'localhost' (using password: NO)
```

That message means **"there is no `.env` in `~/eshglobal.org`"** — it is not a wrong database
password. Create the file once, with either:

* **Automated:** add the repository secret `ENV_CONTENT`, paste the whole `.env` body (same keys
  as `.env.uat.example`) and re-run the workflow. The *Bootstrap server .env* step writes it, strips
  CRLF and sets `chmod 600`; later runs leave it untouched.
* **Manual:** *cPanel → File Manager* → `eshglobal.org` → **+ File** → name it `.env` → paste
  the body (the template is `.env.uat.example` in the repository; it is intentionally **not**
  uploaded to the server) → then, if `APP_KEY` is empty, run
  `php artisan key:generate --force` in *cPanel → Terminal*.

Values that matter on the live site:

| Key | Value |
|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_URL` | `https://eshglobal.org` |
| `APP_KEY` | 51 characters: `base64:` + 44. A trailing CR on this line makes every request fail. |
| `DB_DATABASE` / `DB_USERNAME` | `litgrpco_esh_uat` (create it in *MySQL® Databases*) |
| `DB_PASSWORD` | that database user's password |
| `MAIL_MAILER` | `log` (the live site sends no mail). Password-reset emails need a working mailer; while this is `log`, the CEO/administrator generates a one-time reset link from **Admin → Users → Reset password**. |
| `SESSION_SECURE_COOKIE` | `true` |
| `PAYSTACK_PUBLIC_KEY` / `PAYSTACK_SECRET_KEY` | optional — leave blank until card payments are switched on; the parent form shows card as "coming soon". |
| `SEED_DEMO_ACCOUNTS` | `false` for real use; `true` only while testing — creates `ceo@`/`admin@`/`tutor@`/`learner@`/`parent@esh.com`, password `password` |

After editing `.env` by hand run `php artisan config:clear` (each deploy does this for you).

### 2.4 Manual fallback: deploy over SSH

If Actions is not an option, the same layout can be maintained by hand:

```bash
# 1. Get the code (first deploy only)
cd ~
git clone https://github.com/paulzekeri/excellent-scholars-hub.git eshglobal.org
cd eshglobal.org

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

# 7. Installable app + phone alerts (optional; full guide in PWA.md)
php artisan push:keys          # paste the two printed lines into .env, then rebuild the config cache
#    cron entry (once on the server):
#    * * * * * cd /home/litgrpco/eshglobal.org && php artisan schedule:run >> /dev/null 2>&1
#    https:// is required: service workers and the install prompt do not exist on plain HTTP.
```

### Later deploys (same server)

```bash
cd ~/eshglobal.org
git pull --ff-only origin main
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan storage:link        # no-op if the link exists
php artisan view:clear && php artisan view:cache
php artisan config:cache
php artisan esh:health
php artisan push:send --prune   # optional: deliver queued phone alerts now, prune old rows
```

### Document root

The folder the subdomain points at **is** the app root, and the repository's root `.htaccess`
forwards every request into `public/`. Two supported layouts:

* Default (what the Actions pipeline and `.cpanel.yml` use): document root
  `/home/litgrpco/eshglobal.org`, with `.htaccess`, `.env` and `public/` inside it. Nothing to
  configure in cPanel beyond creating the subdomain.
* Stricter: set the document root to `/home/litgrpco/eshglobal.org/public` in
  *Domains → Manage*. The `.htaccess` in `public/` then handles everything and the root
  `.htaccess` is simply never reached.

`.env` and the upload folders are protected either way: dotfiles are hidden by LiteSpeed, and the
root `.htaccess` returns 403 for `.env`, `.log`, `.sql`, `.sh`, `.yml`, `artisan` and `composer.*`.

## 3. Alternative: cPanel Git pipeline (`.cpanel.yml`)

The repository also ships `.cpanel.yml`, which cPanel's **Git™ Version Control** runs on *Deploy HEAD
Commit* — use it when cPanel itself clones the repository. It produces the same layout (whole app in
the document root, root `.htaccess` → `public/`).

1. *cPanel → Git™ Version Control → Create* → clone
   `https://github.com/paulzekeri/excellent-scholars-hub.git`, branch `main`, repository path
   `repositories/esh-uat`.
2. The committed `.cpanel.yml` already targets `/home/litgrpco/eshglobal.org` and
   `/opt/cpanel/ea-php84/root/usr/bin/php`; change those two values for a different account or PHP
   version.
3. Create `~/eshglobal.org/.env` once (from `.env.uat.example`, see §2.3) — the pipeline never
   overwrites `.env`, `storage/` uploads or `vendor/`.
4. Run *Pull or Deploy → Update from Remote → Deploy HEAD Commit*.

The tasks copy `app bootstrap config database public resources routes`, `artisan`,
`composer.json`/`composer.lock`, `.htaccess` and `.user.ini`, run `composer install --no-dev`,
`migrate --force --seed`, `storage:link`, rebuild the config/view caches and end with `esh:health`.
The task log is shown on the same page and a failing task stops the deploy.

> `route:cache` / `optimize` are intentionally **not** in the pipeline: `routes/web.php` defines
> closures (`/` and `/help/user-guide`), which cannot be serialised.

## 4. Deploy without any pipeline (release zip via File Manager)

Last resort, e.g. a host with neither Actions nor a terminal (Composer can run locally).

```powershell
# On Windows, from the repository root:
powershell -ExecutionPolicy Bypass -File deploy\build-release.ps1
# -> deploy\eshglobal-release.zip (~13 MB, vendor/ included, no .env and no esh_lms.sql)
```

1. *cPanel → File Manager* → upload the zip **into the document root**
   (`eshglobal.org`) → *Extract* → delete the zip. The tree already contains `.htaccess`,
   `.user.ini`, `public/` and `vendor/`.
2. Create `.env` from `.env.uat.example` (File Manager → New File), fill in the DB credentials,
   `APP_URL=https://eshglobal.org` and the `SEED_DEMO_ACCOUNTS=true` line.
3. Generate the key without a terminal — run this locally (PHP installed) and paste the result into
   `APP_KEY=`:
   ```powershell
   php -r "echo 'base64:'.base64_encode(random_bytes(32));"
   ```
4. Database: *phpMyAdmin* → select the live database → *Import* `esh_lms.sql` (local dump,
   MariaDB 10.4) — **or** enable the cPanel Terminal once for `php artisan migrate --force --seed`.
5. The `public/storage` link cannot be created from File Manager. Run `php artisan storage:link` in
   the Terminal; if the account has none, open a ticket with the host — **without** the link,
   material and submission downloads return 404.

## 5. Smoke-test the live deployment

Run the built-in check first (works over SSH or cPanel Terminal):

```bash
cd ~/eshglobal.org && php artisan esh:health
```

It verifies PHP 8.3 - 8.5 + extensions, `APP_KEY`, `APP_URL`, DB connectivity, pending migrations,
writable `storage`/`bootstrap/cache`, the `public/storage` link, upload limits, an existing
CEO/admin login and — as a warning — leftover demo accounts. Exit code is non-zero when something
must be fixed.

Then walk through the app on `https://eshglobal.org`:

| # | Check | Expected |
|---|---|---|
| 1 | `https://eshglobal.org/login` | Login page loads with Tailwind styling and Font Awesome icons (CDN), padlock in the browser bar |
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
| 12 | `https://eshglobal.org/.env`, `/artisan`, `/composer.json`, `/app/Models/User.php` | **403** for the dotfile/blocked names, **404** for the app path — the root `.htaccess` forwards into `public/`, so no source file is served |

## 6. UAT at `uat.eshglobal.org`

`.github/workflows/deploy-cpanel-uat.yml` is the same pipeline for a second, throw-away environment, so
a test push can never touch production data.

1. **Create the domain:** *cPanel → Domains → Create A New Domain* → `uat.eshglobal.org`, keep the
   folder cPanel proposes (`/home/<account>/uat.eshglobal.org`), assign **PHP 8.4**, the same 100M INI
   limits as §1, and run AutoSSL for it.
2. **Separate database/user** (never the production one), then create its `.env` from
   `.env.uat.example`: `APP_ENV=production`, `APP_DEBUG=false`, `SEED_DEMO_ACCOUNTS=true`,
   `APP_URL=https://uat.eshglobal.org`, `SESSION_SECURE_COOKIE=true`. Set the `UAT_ENV_CONTENT` secret
   and the workflow writes it once, exactly like production.
3. **Repository secrets/variables** in the new repo: `UAT_CPANEL_HOST`, `UAT_CPANEL_USER`,
   `UAT_CPANEL_PASSWORD`; optionally `UAT_CPANEL_REMOTE_DIR` (`uat.eshglobal.org/`, the default) and
   `UAT_CPANEL_SSH_PORT`.
4. **Deploy:** `git push origin uat` (create the branch from `main` first with
   `git push origin main:uat`). The workflow mirrors the tree, runs `migrate --force --seed`,
   `storage:link`, the caches and `esh:health` against the UAT database.
5. **Verify:** walk the §5 table against `https://uat.eshglobal.org` with the demo accounts, then merge
   into `main` to publish production.
6. Keep the UAT copy private if you like (cPanel → *Directory Privacy*), and remember it needs its own
   `php artisan push:keys` pair (or copy the production pair) plus the `schedule:run` cron for phone
   alerts — see `PWA.md`.

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

**Rollback:** revert the commit and push (`git revert <sha> && git push`) — the workflow republishes
the previous code and its prune step removes the files the revert deleted. Manual installs:
`git checkout <previous-commit>` in `~/eshglobal.org`, then `composer install --no-dev` and
`php artisan config:cache && php artisan view:cache`. Schema changes: `php artisan migrate:rollback
--step=1` (only migrations from the last batch). Restore the pre-deploy database dump if the rollback
touches data.


