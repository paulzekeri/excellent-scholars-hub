# Runtime Upgrade — Laravel 8 / PHP 8.1 → Laravel 13 / PHP 8.4

This document records the framework upgrade on branch `upgrade/laravel-13` and the evidence that it
is safe to deliver. Read section 1 with the client; sections 2-6 are for whoever deploys.

---

## 1. Why the upgrade was mandatory (the 4-year question)

Laravel 8 and PHP 8.1 are both end-of-life, and the two clocks run on different schedules:

| Runtime | Release | Security fixes end | Meaning today |
|---|---|---|---|
| PHP 8.1 | Nov 2021 | **31 Dec 2025** | already EOL |
| Laravel 8.83 | — | Jan 2023 | EOL for nearly four years |
| PHP 8.2 | Dec 2022 | 31 Dec 2026 | do not target |
| PHP 8.3 | Nov 2023 | 31 Dec 2027 | — |
| **PHP 8.4** | Nov 2024 | **31 Dec 2028** | what SmartWeb offers (`ea-php84`) |
| PHP 8.5 | Nov 2025 | 31 Dec 2029 | not on SmartWeb yet |
| Laravel 12 | Feb 2025 | 24 Feb 2027 | too short for a 4-year term |
| **Laravel 13** | 17 Mar 2026 | **17 Mar 2028** | current stable, PHP 8.3 - 8.5 |

Delivering on Laravel 8 + PHP 8.1 means the site breaks the day SmartWeb retires `ea-php81`
(cPanel already labels 7.2 - 8.0 as "deprecated" in MultiPHP Manager). The hosting provider does not
change that; the runtime does.

**Delivered stack: Laravel 13.40 + PHP 8.4 (`ea-php84`).**
**Planned maintenance inside the 4-year term: one window, Feb-Mar 2028** — Laravel 13 → 15 *and*
PHP 8.4 → 8.5/8.6 in the same outage. That single window carries the site past 2030.

Suggested wording for the contract (this is what is actually true):

> "Delivered on Laravel 13 and PHP 8.4, both under vendor security support on go-live. The runtime
> covers the site through December 2028. One scheduled upgrade (framework + PHP) in Feb-Mar 2028 is
> included in the maintenance plan."

---

## 2. What changed in the code

### Dependencies (`composer.json`)

| Removed | Why |
|---|---|
| `fruitcake/laravel-cors` | CORS is built in since Laravel 10 (`Illuminate\Http\Middleware\HandleCors`) |
| `livewire/livewire` | **Unused**: only `@livewireStyles` / `@livewireScripts` in `resources/views/layouts/app.blade.php`. Removing it avoids the Livewire 2 → 3 migration entirely |
| `facade/ignition` | Not needed on Laravel 11+ |
| `laravel/sail` | Not used (Windows/XAMPP) |

| Upgraded | From | To |
|---|---|---|
| `laravel/framework` | `^8.75` | `^13.0` |
| `laravel/sanctum` | `^2.11` | `^4.3` (**kept** — `User` uses `HasApiTokens`, `routes/api.php` uses `auth:sanctum`) |
| `laravel/tinker` | `^2.5` | `^3.0` |
| `laravel/ui` | `^3.4` | `^4.6` (still provides `Auth::routes()`; ships the removed `Illuminate\Foundation\Auth\*` traits as shims) |
| `phpunit/phpunit` | `^9.5` | `^11.5` |
| `php` constraint | `^7.3\|^8.0` | `^8.3` |

### Structural files (Laravel 11+ skeleton)

| File | Change |
|---|---|
| `bootstrap/app.php` | Rewritten with `Application::configure()`; registers `EnsurePasswordIsChanged` (appended to `web`) and the `role` middleware alias |
| `bootstrap/providers.php` | **New** — `AppServiceProvider`, `AuthServiceProvider`, `EventServiceProvider` |
| `public/index.php`, `artisan` | New entry points using `handleRequest()` / `handleCommand()` |
| `config/app.php` | Dropped `providers` and `aliases` (ignored on L11+; held a stale `RouteServiceProvider` reference) |
| `config/sanctum.php` | Replaced with the Sanctum v4 default (its middleware map pointed at deleted classes) |
| `config/cache.php` | `env('CACHE_STORE', env('CACHE_DRIVER', 'file'))` so old and new keys both work |

### Deleted (framework no longer uses them)

`app/Http/Kernel.php`, `app/Console/Kernel.php`, `app/Exceptions/Handler.php`,
`app/Providers/RouteServiceProvider.php`, `tests/CreatesApplication.php`, and eight
framework-default middleware classes (`Authenticate`, `EncryptCookies`,
`PreventRequestsDuringMaintenance`, `RedirectIfAuthenticated`, `TrimStrings`, `TrustHosts`,
`TrustProxies`, `VerifyCsrfToken`) — every one an empty subclass of a framework class.

### Application code touched

| File | Change |
|---|---|
| `app/Http/Controllers/HomeController.php` | **New** — the `/` and `/help/user-guide` closures moved here, so `php artisan route:cache` works again |
| `routes/web.php` | The two closures now point at `HomeController` (route names unchanged) |
| `Auth/LoginController.php`, `Auth/RegisterController.php` | `$redirectTo = RouteServiceProvider::HOME` → `'/'` (same value) |
| `app/Providers/AppServiceProvider.php` | Re-added the `api` rate limiter that lived in `RouteServiceProvider` |
| `app/Console/Commands/DeploymentHealthCheck.php` | `fail()` → `recordFailure()` (**`Command::fail()` is public**; a private override is a fatal error); PHP bounds moved to 8.3 - 8.5 |
| `resources/views/layouts/app.blade.php` | Livewire directives removed |
| `phpunit.xml` | PHPUnit 11 schema, `force="true"` on every `<env>`, dedicated `esh_lms_test` database |
| `tests/TestCase.php` | `CreatesApplication` trait removed |
| `.github/workflows/deploy-cpanel.yml` | `PHP_VERSION: '8.4'` |
| `.cpanel.yml` | `ea-php81` → `ea-php84` |
| `DEPLOYMENT.md`, `README.md`, both quotations | Laravel 8 / PHP 8.1 → Laravel 13 / PHP 8.4 |

---

## 3. Traps that cost the most time (do not re-learn them)

1. **A cached config silently defeats `phpunit.xml`.** `bootstrap/cache/config.php` freezes
   `APP_ENV`, `DB_DATABASE` and friends, so a test run after `php artisan config:cache` uses the
   **live development database**. Always clear it before testing.
2. **PHPUnit 11 needs `force="true"`.** It exports `<env>` values through `getenv()`, which Laravel's
   immutable Dotenv repository does not read, so `.env` wins. Every `<env>` now carries
   `force="true"`.
3. **This suite uses `DatabaseTransactions`, not `RefreshDatabase`** (32 files vs 0). It never
   migrates anything, so the test database must already exist, be migrated and be seeded before the
   first run — see section 4.
4. **`config/cache.php` was still reading the Laravel 8 key `CACHE_DRIVER`.** Without it the
   password-reset rate limiter (`ForgotPasswordController`) remembered hits between tests, producing
   "Too many reset requests". Both keys now work and `phpunit.xml` sets both.
5. **`Command::fail()` collision** — see `DeploymentHealthCheck` above.
6. **`route:cache` is now allowed.** `routes/web.php` has no closures any more, so
   `php artisan optimize` (config + routes + views) works on the server.

---

## 4. Running the suite locally (Windows / XAMPP)

XAMPP's bundled PHP is 8.0 and cannot run Laravel 13 — no XAMPP reinstall is needed. Use the
standalone build already on this machine (`C:\tools\php83`, whose `php.ini` was given
`extension=pdo_mysql`).

```powershell
# 1. MySQL (XAMPP) must be running
Start-Process 'C:\xampp\mysql\bin\mysqld.exe' -ArgumentList '--defaults-file=C:\xampp\mysql\bin\my.ini'

# 2. One-off: create + migrate + seed the throwaway test schema
& 'C:\xampp\mysql\bin\mysql.exe' -u root -e "CREATE DATABASE IF NOT EXISTS esh_lms_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
$env:APP_ENV='testing'; $env:DB_DATABASE='esh_lms_test'
& 'C:\tools\php83\php.exe' artisan migrate --force
& 'C:\tools\php83\php.exe' artisan db:seed --force

# 3. Never leave a stale config cache behind
Remove-Item bootstrap\cache\config.php -Force -ErrorAction SilentlyContinue

# 4. Run everything
& 'C:\tools\php83\php.exe' vendor\bin\phpunit --no-coverage
```

Current result: **263 tests, 1,558 assertions, 0 failures, 0 errors** (7 cosmetic PHPUnit deprecation
notices remain; they do not fail the build).

Composer must also run on 8.3+:
`& 'C:\tools\php83\php.exe' 'C:\ProgramData\ComposerSetup\bin\composer.phar' install`

---

## 5. Deploying to SmartWeb (production)

1. **MultiPHP Manager → `lms.<domain>` → PHP 8.4 (`ea-php84`)**. `ea-php84` honours the committed
   `.user.ini` (100M upload / 104M post / 256M memory / 300s). Confirm the same values in
   *MultiPHP INI Editor* if uploads still fail.
2. **MySQL**: create the database + user, `GRANT ALL` on that database only.
3. **`.env`** from `.env.production.example`: `APP_KEY` (`php artisan key:generate --force`),
   `APP_URL=https://lms.<domain>`, DB credentials, `MAIL_MAILER=smtp` + relay credentials,
   `SESSION_SECURE_COOKIE=true`, `APP_DEBUG=false`, `SEED_DEMO_ACCOUNTS=false`.
4. **SSL**: *SSL/TLS Status → Run AutoSSL* before relying on secure cookies.
5. Deploy, then on the server:

   ```bash
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize        # config + route + view cache (now possible)
   php artisan esh:health
   php artisan esh:create-admin --role=ceo
   ```

6. **Smoke test**: upload a ~100 MB material and download it again; trigger "forgot password" for a
   learner and confirm the guardian receives the mail; log in as each of the five roles.
7. **Backups**: nightly off-host database dump plus `storage/app`. Host backups alone are not a
   backup strategy for a school's records.

---

## 6. Rollback

`main` is untouched by this work. If anything is wrong, point SmartWeb back at `main` (Laravel 8 on
PHP 8.1) and set the domain to `ea-php81`. The schema is compatible in both directions because the
upgrade added no migrations.

