# Excellent Scholars Hub (ESH)

A web-based Learning Management System (LMS) built on **Laravel 8**, **MySQL** and **Tailwind CSS**, with
role-specific dashboards for the **CEO**, **Administrators**, **Tutors**, **Learners** and **Parents/Guardians**.

The full end-user documentation lives inside the app at **Help → User Guide** (`/help/user-guide`).

## Roles & access rules

| Role | Slug | Area | Key permissions |
|------|------|------|-----------------|
| CEO | `ceo` | Admin area | Everything an administrator can do **plus exclusive access to Payments** |
| Administrator | `admin` | Admin area | Users, classes, schedules, oversight, notifications, messages |
| Tutor | `tutor` | Tutor area | Own classes only: schedules, attendance, assignments & grading, materials |
| Learner | `learner` | Learner area | Enrolled classes, assignment submission, materials, schedules |
| Parent/Guardian | `parent` | Parent area | Per-child stats & details (schedule, assignments, materials), attendance & missed classes, grades, messaging the school |

Implemented guards:

- **Payments are CEO-only.** The payments routes are grouped behind the `role:ceo` middleware and
  `Admin\PaymentController` re-checks `canManagePayments()` in its constructor, so administrators/tutors who hit
  the URL directly receive **403 Forbidden**. The Payments link and dashboard stat card are hidden for non-CEO users.
- **Administrators and the CEO can review submissions.** `/admin/assignments` → *Review & Grade Submissions*
  (`admin.oversight.assignments.submissions`, `admin.oversight.submissions.grade`) lets them download a learner's
  file, set a score (validated against the assignment's total points) and add feedback. Tutors are restricted to
  assignments they own — anything else returns **403**.
- **In-app messaging is parents ↔ CEO/administrators.** `/messages` lists contacts per role, stores messages in the
  `messages` table, files a `Notification` for the recipient, and marks incoming messages as read when a thread is
  opened. Tutors and learners receive **403**.

## Default seeded accounts

| Role | Email | Password |
|------|-------|----------|
| CEO | `ceo@esh.com` | `password` |
| Administrator | `admin@esh.com` | `password` |
| Tutor | `tutor@esh.com` | `password` |
| Learner | `learner@esh.com` | `password` |
| Parent | `parent@esh.com` | `password` |

If a phpMyAdmin import replaced those hashes (each row ends up with a different
`$2y$...` value, so only the account whose password you know still works), run
`database/scripts/share-admin-password.sql` against that database. It copies the
working administrator hash onto every row, re-activates the accounts, clears old
"remember me" tokens and prints how many distinct hashes remain so you can
confirm the change. The same file contains a fixed-hash alternative and a query
that lists accounts missing their `learners`/`tutors`/`parents` profile row.
Test databases only — never run it on production data.


## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
# configure DB_* values in .env, then:
php artisan migrate --seed
npm install && npm run dev     # or: npm run production
php artisan serve
```

Running `php artisan migrate` creates the `ceo` role automatically
(`2023_01_01_000014_add_ceo_role_to_roles_table`) and the `messages` table
(`2023_01_01_000015_create_messages_table`). On existing installs the migration promotes the oldest administrator to
the `ceo` role so at least one CEO account keeps payment access.

To add a dedicated `ceo@esh.com` account (plus any other missing demo account), re-run the seeder:

```bash
php artisan db:seed
```

`DatabaseSeeder` uses `firstOrCreate`, so it is safe to run on a populated database: it only inserts accounts,
roles, classes and schedules that do not exist yet and never overwrites existing records. You can also create extra
CEO/administrator logins from *Users → New User* once signed in as the CEO.

Demo accounts are seeded automatically on `local`/`testing`/UAT installs. In production they are skipped unless
`SEED_DEMO_ACCOUNTS=true`; create the first real login instead:

```bash
php artisan esh:create-admin --role=ceo
```

## Deployment

UAT lives at **`https://uat-esh.lit-grp.com`** — a cPanel subdomain whose document root holds the whole app
(the repository's root `.htaccess` forwards every request into `public/`). Every push to `main` is published
by **GitHub Actions** (`.github/workflows/deploy-cpanel.yml`: build `vendor/`, SFTP mirror, prune, then
`migrate`/`storage:link`/caches/`esh:health` over SSH) — the same pipeline pattern used for `dev.lit-grp.com`.
The full runbook — secrets, subdomain/PHP/database setup, the cPanel Git pipeline (`.cpanel.yml`), the
release-zip fallback (`deploy/build-release.ps1`), the UAT smoke-test matrix, production promotion and
rollback — is in **[DEPLOYMENT.md](DEPLOYMENT.md)**.

Environment templates:

| File | Use |
|---|---|
| `.env.example` | local development |
| `.env.uat.example` | UAT (`APP_ENV=production`, `APP_DEBUG=false`, `SEED_DEMO_ACCOUNTS=true`) |
| `.env.production.example` | go-live (`SEED_DEMO_ACCOUNTS=false`, `LOG_LEVEL=warning`) |

Two commands support the deploy:

```bash
php artisan esh:health                       # PHP/extensions, APP_KEY/URL, DB, pending migrations,
                                             # writable storage, public/storage link, upload limits, admin + demo users
php artisan esh:create-admin --role=ceo      # first real CEO/admin login on a fresh production database
```

Notes: the UI loads Tailwind/Font Awesome from CDNs (no `npm run production` needed on the server), and
`route:cache`/`optimize` must not be used because `routes/web.php` contains closures — cache with
`php artisan config:cache` and `php artisan view:cache`.

## Feature change log

### UAT deployment kit (`uat-esh.lit-grp.com`)
- **`.github/workflows/deploy-cpanel.yml`** — push-to-`main` pipeline: Composer install with `--no-dev`, SFTP
  mirror (`lftp`) to `~/uat-esh.lit-grp.com/`, pruning of files removed from the repo, then `migrate --force
  --seed`, `storage:link`, `config:cache`, `view:cache` and `esh:health` over SSH. Never uploads `.env`,
  `storage/` uploads or `public/storage`.
- **`.htaccess` (repository root)** — forwards every request into `public/` and returns 403 for `.env`,
  `artisan`, `composer.*`, `*.log`, `*.sql`, `*.sh`, `*.yml`, so the app can live inside the document root.
- **`.user.ini`** — 100 MB upload limits, 256 MB memory and `session.upload_progress.enabled=0`.
- **`DEPLOYMENT.md`** — cPanel runbook: secrets, subdomain/PHP/MySQL setup, GitHub Actions deploy, cPanel Git
  pipeline, release zip, UAT smoke-test matrix, UAT → production promotion, rollback and troubleshooting.
- **`.cpanel.yml`** — fallback cPanel *Git™ Version Control* tasks (composer install, `migrate --force --seed`,
  `storage:link`, config/view caches, `esh:health`) that never overwrite `.env` or `storage/` uploads.
- **`.env.uat.example` / `.env.production.example`** — environment templates (`uat-esh.lit-grp.com`, secure cookies,
  demo seeding on UAT only).
- **`php artisan esh:health`** — post-deploy check of PHP/extensions, `APP_KEY`/`APP_URL`, database, pending migrations,
  writable `storage`/`bootstrap/cache`, `public/storage` link, upload limits, admin logins and leftover demo accounts.
- **`php artisan esh:create-admin`** — creates the first CEO/administrator login (and the role rows) on a fresh
  production database.
- **`deploy/build-release.ps1`** — builds a ready-to-upload release zip (vendor included, `.env`/`esh_lms.sql` excluded).
- **`DatabaseSeeder` safety guard** — demo users/class/schedule are skipped when `APP_ENV=production` unless
  `SEED_DEMO_ACCOUNTS=true`, so public demo passwords can never reach production.
- Feature tests: `tests/Feature/DeploymentToolingTest.php` (6 tests).

### Parent dashboard: per-child stats, details & missed classes
- Each child card on `/parent/dashboard` now shows clickable counters — **upcoming classes**, **pending assignments**,
  **completed assignments** and **published materials** — plus an attendance rate and a red **"N missed"** badge when a
  tutor has marked the child absent.
- Counters open the child's detail screen (`parent.child.progress`) on the matching tab. Tabs: *Overview*, *Class
  Schedule*, *Pending Assignments*, *Completed Assignments*, *Materials Shared* and *Attendance*, each paginated.
- The attendance tab lists every recorded session, highlights **missed classes** (status `absent`) and separately reports
  past sessions where the tutor has **not recorded attendance yet**, so unmarked sessions are never mistaken for absences.
- `Parent\DashboardController` now guards child access with `abort_unless($learner->parent_id === $parent->id, 403)`
  (previously any parent could open any learner by ID) and renders an empty state for a parent account without a
  parent profile.

### Admin learner ↔ parent linking
- `Admin\UserController` now accepts an optional `parent_id` when creating or updating a learner (validated with
  `exists:parents,id`) and writes it to `learners.parent_id`, so a learner created by an administrator can be
  attached to a parent account (one parent → many learners).
- The *Create User* / *Edit User* screens show a "Parent / Guardian" selector that only appears for the learner
  role; the learner profile is created automatically if an existing user is switched to the learner role.
- The *Users* table gained a **Parent / Guardian** column (linked parent name, *Not linked*, or learner count for
  parents) and a parent's edit screen lists all linked learners.
- In-app user guide updated with the linking steps.

### CEO-only payments
- New `ceo` role (migration + seeder) and helpers on `User`: `isCeo()`, `isAdministrator()`, `canManagePayments()`.
- `/admin/payments*` routes moved behind `role:ceo`; `Admin\PaymentController` constructor aborts with 403 otherwise.
- Navigation Payments dropdown entry and dashboard payments stat card are rendered only for the CEO.
- Login redirect and the admin UI treat the CEO as an administrator, so no separate CEO screens are needed.
- `Admin\UserController` hides the `ceo` role from non-CEO administrators and refuses to edit/assign it.

### Assignment submission review for admins & teachers
- `Admin\OversightController` gained `submissions()`, `gradeSubmission()` and `downloadSubmission()` for cross-class review.
- `Tutor\AssignmentController` now verifies ownership before showing submissions/grading, and validates the score
  against `total_points`.
- Oversight assignments page links every assignment to a "Review & Grade Submissions" screen.

### Parent ↔ CEO/administrator messaging
- `messages` table + `Message` model (`between()` scope), `MessageController` (`index`, `show`, `store`) with
  role-based contact lists and 2,000-character limit.
- Each message files an in-app `Notification` for the recipient; the navigation shows unread badges (desktop + mobile).
- Parents get a "Message the School" button and unread badge on their dashboard.

---

## Laravel Framework README

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 1500 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
