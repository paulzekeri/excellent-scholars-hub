# Excellent Scholars Hub (ESH)

A web-based Learning Management System (LMS) built on **Laravel 8**, **MySQL** and **Tailwind CSS**, with
role-specific dashboards for the **CEO**, **Administrators**, **Tutors**, **Learners** and **Parents/Guardians**.

The full end-user documentation lives inside the app at **Help → User Guide** (`/help/user-guide`).

## Roles & access rules

| Role | Slug | Area | Key permissions |
|------|------|------|-----------------|
| CEO | `ceo` | Admin area | Everything an administrator can do **plus exclusive access to Payments and the school bank accounts** |
| Administrator | `admin` | Admin area | Users, classes, schedules, oversight, notifications, messages |
| Tutor | `tutor` | Tutor area | Own classes only: schedules, attendance, assignments & grading, materials, learning goals; messages the school and own learners |
| Learner | `learner` | Learner area | Enrolled classes, assignment submission, materials, schedules; live/next class panel; messages the school and own tutors |
| Parent/Guardian | `parent` | Parent area | Per-child stats & details (schedule, assignments, materials, learning goals), attendance & missed classes, grades, **own fee payments** (record, receipt, verified balance), messaging the school |

Implemented guards:

- **Parents record payments; the CEO verifies them.** `/parent/payments` (list + `create`/`store`/`receipt`) is open
  to parents only, restricted to the children linked to their own account, and every submission is stored as
  `pending`. The CEO's `/admin/payments` screen no longer records payments — it is a verification queue with
  *Payment received* (`admin.payments.verify`) and *Cannot match it* (`admin.payments.reject`) actions.
- **Fees are paid by transfer or cash (card is stubbed for Paystack).** A transfer must name the school account it
  went into, so `/admin/payment-accounts` lets only the CEO add, correct, switch off or delete the bank accounts.
  Active accounts appear on the parent form with a copy button; a switched-off account disappears without losing the
  payments already recorded (`payments.paid_into` keeps the snapshot). Cash needs no account and is checked against
  the cash book. Card shows as *coming soon* until `PAYSTACK_PUBLIC_KEY` / `PAYSTACK_SECRET_KEY` are set — see
  `App\Services\PaystackService` for the two-call flow (initialise → verify).
- **Passwords reset themselves.** `Forgot your password?` on `/login` emails a one-time link (`password.request`,
  `password.email`, `password.reset`, `password.update`), valid for 60 minutes and usable once; the new password
  signs the user straight in. When mail is not configured (UAT uses the `log` driver) the CEO/administrators can
  generate the same link from **Admin → Users → Reset password** and pass it on privately. Links are never shown
  to other roles, only the CEO can reset a CEO account, and inactive accounts are refused.
- **Payments remain CEO-only for the school.** The `/admin/payments*` routes sit behind `role:ceo`, and
  `Admin\PaymentController` re-checks `canManagePayments()` in its constructor, so administrators/tutors/parents who
  hit the URL directly receive **403 Forbidden**. The Payments link and dashboard stat card are hidden for non-CEO users.
- **Administrators and the CEO can review submissions.** `/admin/assignments` → *Review & Grade Submissions*
  (`admin.oversight.assignments.submissions`, `admin.oversight.submissions.grade`) lets them download a learner's
  file, set a score (validated against the assignment's total points) and add feedback. Tutors are restricted to
  assignments they own — anything else returns **403**.
- **In-app messaging is parents ↔ CEO/administrators.** `/messages` lists contacts per role, stores messages in the
  `messages` table, files a `Notification` for the recipient, and marks incoming messages as read when a thread is
  opened. Every role has an inbox: parents reach the school, learners reach the school and their own tutors, tutors
  reach the school and the learners in their classes, and administrators/CEO can message parents, tutors, learners
  and each other. Administrators can also broadcast one message to a whole category (parents, tutors, students,
  everyone or a single class); each recipient gets their own `messages` row (tagged with `audience`, `class_id` and a
  shared `batch`) plus a notification, and the broadcast report counts recipients and reads. `MessageController::canMessage()`
  is the single source of truth for the permission matrix; anyone outside it gets **403**.

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

### Enquiry conversion (note 13, phase 4, 30 October 2026)

An *Awaiting enrolment* enquiry can now become a real family without re-typing anything: the school
confirms the usernames and class, and the child is enrolled with logins emailed to the guardian.

| Area | Change | Where |
|------|--------|-------|
| Wizard | **Convert to learner** on a placed enquiry: child username + optional email, guardian username (or reuse the existing parent matched by email), class (recommended preselected) | `EnquiryConversionController`, `admin/enquiries/convert.blade.php` |
| Accounts | Parent and learner users + profiles, unique usernames, temporary passwords, `must_change_password`; the child inherits the enquiry's grade level; existing parents are never duplicated | `EnquiryConversionController@store` |
| Enrolment | Child attached to the class (capacity enforced), enquiry marked **Enrolled** with `converted_learner_id` / `converted_at`, timeline entry and activity log | same |
| Credentials | `AccountProvisionedMail` to the guardian (and the child's own email when present); when mail cannot deliver, both sets are flashed on the enquiry page | `AccountProvisionedMail` |
| Safeguards | Placed status required, guardian email required and not a staff/learner address, duplicate usernames refused, double submission made idempotent with a locked transaction | same |
| Tests | `tests/Feature/EnquiryConversionTest.php` (10) — accounts, reuse, idempotency, status guard, duplicate username, staff email, capacity, credential flash, access control, render | `tests/Feature` |

### Assessment results and placement (note 13, phase 3, 30 October 2026)

After the call, the tutor (or the school) records what happened; the enquiry then moves to *Assessed* or
*Awaiting enrolment* automatically, with the report kept on file.

| Area | Change | Where |
|------|--------|-------|
| Result form | Assessment detail pages for the school and the tutor: **Completed / No show**, outcome (ready / needs support / not ready), strengths, focus areas, recommended class + level, internal notes and an optional PDF/image report (5 MB, replaces the old one) | `admin/assessments/show.blade.php`, `tutor/assessments/show.blade.php`, `partials/assessment-result-form.blade.php` |
| Pipeline | Completed → **Assessed**; completed **with a recommended class** → **Awaiting enrolment**; no-show → **Contacted** (rebook). Manual status moves to *Assessed*/*Awaiting enrolment* are refused without the matching result | `AssessmentResultService`, `EnquiryController@updateStatus` |
| Evidence | Report download for the school and the assigned tutor; every result and no-show writes a timeline entry and an `activity_logs` row | `Admin\AssessmentController@downloadReport`, `Tutor\AssessmentController@downloadReport` |
| Safeguards | A tutor can only open/record their own assessments (403 otherwise); a no-show or cancelled assessment cannot be edited into a completion | `Tutor\AssessmentController`, `AssessmentResultService` |
| Tests | `tests/Feature/AssessmentResultTest.php` (11) — completion, placement, outcome requirement, no-show, late recommendation, uploads/downloads, mime guard, tutor isolation, pipeline guards, render smoke | `tests/Feature` |

### Assessment booking (note 13, phase 2, 30 October 2026)

A parent can now book the assessment from the public landing page — before any account exists — and the
school turns that request into a scheduled video call that the administrator/CEO, the tutor and the
parent all join from the same link.

| Area | Change | Where |
|------|--------|-------|
| Public booking | "Book a video-call assessment" section on `/` with child, guardian name/phone/email, preferred slot and notes; rate-limited and honeypot-protected; creates an enquiry (`source = website`) and alerts every administrator/CEO. **No account is created** | `PublicBookingController`, `landing.blade.php` |
| School booking | On the enquiry page (after *Contacted*): tutor, date/time, duration, platform, Meet/Zoom link and guardian email → enquiry moves to **Assessment booked**, timeline entry, in-app tutor invitation | `Admin\AssessmentController@store`, `admin/enquiries/show.blade.php` |
| Guardian invite | Email to the guardian with the date, tutor and **Join** button (`AssessmentScheduledMail`), plus a pre-filled WhatsApp message with the same link on the enquiry page | `AssessmentScheduledMail`, `EnquiryController@show` |
| Everyone can join | `/admin/assessments` (today/upcoming/past + tutor filter + Join), `/tutor/assessments` (own calls only), "Today's assessment calls" panels on both dashboards | `Admin\AssessmentController`, `Tutor\AssessmentController`, dashboards |
| Cancellation | Cancel a scheduled call; the enquiry returns to *Contacted* and the timeline records it | `AssessmentController@cancel` |
| Data | New `assessment_sessions` table (per call, so a child can be assessed many times) + `enquiries.preferred_at` | `2026_10_30_000001_create_assessment_sessions_table` |
| Tests | `tests/Feature/AssessmentBookingTest.php` (13) — public booking, honeypot, validation, booking guards, guardian email, tutor isolation, admin list, cancellation, dashboard panels | `tests/Feature` |

### Admissions pipeline (note 13, phase 1, 29 October 2026)

A prospective learner used to live only in a WhatsApp thread. The school can now capture the enquiry
before any account exists, see the pipeline at a glance, and keep every call on the record.

| Area | Change | Where |
|------|--------|-------|
| Capture | A one-minute form for the child, guardian (name + phone + optional email), source and notes; **no user/learner account is created** | `Admin\EnquiryController@store`, `admin/enquiries/create.blade.php` |
| Pipeline | Tabs for **Open / All / New / Contacted / Declined / Archived** with counts, search (child, guardian, phone, email, reference) and a source filter; references are `ENQ-YYYY-0001` | `Admin\EnquiryController@index`, `Enquiry` |
| Detail | Guardian phone/email links, a ready-to-send **WhatsApp** button, child details, owner, notes, and a timeline of calls, messages and status changes | `admin/enquiries/show.blade.php`, `EnquiryNote` |
| Guarded moves | Only `Enquiry::STATUS_FLOW` transitions are accepted; declining requires a reason; archived is final; assessment/enrolment targets stay closed until those screens exist. Every move writes a timeline entry **and** an `activity_logs` row | `Admin\EnquiryController@updateStatus` |
| Dashboard | **New Enquiries** and **Enquiries In Progress** cards linking into the filtered pipeline | `Admin\DashboardController`, `admin/dashboard.blade.php` |
| Navigation | **People → Admissions** in the sidebar | `layouts/sidebar.blade.php` |
| Tests | `tests/Feature/AdmissionsTest.php` (12) — permissions, capture, validation, filters, timeline, moves, decline reason, edit, counters, render smoke | `tests/Feature` |

### Children sign in with a username (note 12, 29 October 2026)

Some learners have no email address, so the login field now accepts either an email or a unique
username, and every child account must have a reachable guardian email.

| Area | Change | Where |
|------|--------|-------|
| Login | One field accepts an **email or a username**; deactivated accounts are refused by the login query | `App\Auth\EmailOrUsernameUserProvider`, `LoginController`, `auth/login.blade.php` |
| Registration | Learners choose a username; their email is **optional**; the guardian email is required, a staff email is refused as a guardian, and a child's email cannot equal the guardian's | `RegisterController`, `auth/register.blade.php` |
| Provisioning | Admins create a **parent** (username + email + temporary password) and add **children** from the parent's edit screen (username + password + optional email); credentials are emailed once | `Admin\UserController@store/addChild`, `AccountProvisionedMail` |
| First sign-in | A school-set password must be replaced before the app opens; the full-page interstitial cannot be skipped, and logout stays reachable | `EnsurePasswordIsChanged`, `ChangePasswordController`, `auth/passwords/change.blade.php` |
| Password resets | "Forgot your password?" accepts an **email or username**. A child with no mailbox gets a per-user one-time link emailed to the **guardian**; the parent dashboard can send it too, and the admin fallback still works when mail is off | `ForgotPasswordController`, `ResetPasswordController`, `LearnerPasswordResetService`, `LearnerPasswordResetMail` |
| Operations | After deploying the migration: backfill usernames for existing accounts and audit learners whose guardian has no email | `esh:backfill-usernames`, `esh:audit-guardian-links` |
| No-mail installs | When the mailer cannot deliver (`log`/`array`, as on UAT), the credentials are shown once to the administrator to hand over privately | `Admin\UserController::sendProvisioningMail()` |
| Data | Unique `users.username`, nullable `users.email`, `must_change_password`, `password_changed_at` | `2026_10_29_000001_add_username_to_users_table` |
| Tests | `UsernameLoginTest` (12), `AccountProvisioningTest` (11), `GuardianPasswordResetTest` (12) and `MaintenanceCommandsTest` (8); `AdminUserParentLinkTest` rewritten for the mandatory guardian | `tests/Feature` |

### Parents record fees, the CEO only verifies (follow-up to the 26 October 2026 meeting)

The CEO was entering every family's payment by hand; a parent paying for two or three children can do it in one
sitting, then the school only confirms the money arrived.

| Area | Change | Where |
|------|--------|-------|
| Parent fees | New **Fees & Payments** screen: per-child fee cards (term · class), verified vs pending amounts, progress bars, "Payment complete" badges and a submission history with receipt downloads | `Parent\PaymentController`, `parent/payments/*`, sidebar `Finance` group |
| Record payment | Moved from the CEO to the parent: the form picks only the parent's own children and their classes, forces `status = pending`, and notifies every CEO that money is waiting | `parent.payments.create/store`, `Payment::STATUS_PENDING` |
| Payment methods | **Bank transfer**, **cash** or **card**. A transfer must pick the school account it went into and keeps a `paid_into` snapshot; cash needs no account and points the CEO at the cash book; card is disabled with "coming soon" | `Payment::METHOD_*`, `parent/payments/create.blade.php` |
| School bank accounts | CEO-only `/admin/payment-accounts`: add, edit, switch off (hides it from parents) and delete accounts. Active ones appear on the parent fee screens with a copy-number button; deleting keeps the historical `paid_into` | `Admin\PaymentAccountController`, `payment_accounts`, `admin/payment-accounts/*` |
| Card (future) | Paystack seam with `initializeTransaction()` / `verifyTransaction()`, config keys and a status panel on the bank-accounts screen; the card radio stays disabled until the flow is wired | `App\Services\PaystackService`, `config/services.php`, `PAYSTACK_*` |
| Role-aware guide | The in-app **User Guide** now shows only the sections for the signed-in role: administrators get the admin guide without finance, the CEO also gets payments/bank accounts, and tutor/learner/parent content (plus role-specific FAQs) is hidden from everyone else | `resources/views/help/user-guide.blade.php` |
| Password reset | **Forgot your password?** emails a one-time reset link (60 minutes, single use) and signs the user in after choosing a new password; CEO/administrators can generate the link from **Users → Reset password** when mail is not configured | `ForgotPasswordController`, `ResetPasswordController`, `auth/passwords/*`, `admin.users.password-reset` |
| CEO verification | `/admin/payments` is now a verification queue. **Payment received** marks the installment `completed` (clearing the fee), **Cannot match it** rejects it with a reason; both notify the parent | `admin.payments.verify`, `admin.payments.reject`, `Payment::markVerified()` / `markRejected()` |
| Audit trail | `payments.verified_at` / `payments.verified_by` record who confirmed the money and when | `AddVerificationColumnsToPaymentsTable` |
| Balances | Only verified (`completed`) installments count, so a pending submission never reduces a balance; `Payment::totalsForLearner()` sums every fee for a child | `Payment::feeGroupsForLearner()`, `Payment::totalsForLearner()` |
| Tests | `ParentPaymentsTest` (9) covers submission, ownership, verification, rejection, receipts and the removed CEO button; `PaymentMethodsTest` (8) covers the CEO's bank-account CRUD, transfer/cash/card rules, the method filter and deleted-account history; `UserGuideRolesTest` (6) proves each role only sees its own guide sections; `PasswordResetTest` (11) covers the email link, token expiry/replay, admin-generated links and finance guards | `tests/Feature/ParentPaymentsTest.php`, `tests/Feature/PaymentMethodsTest.php`, `tests/Feature/UserGuideRolesTest.php`, `tests/Feature/PasswordResetTest.php` |

### Client meeting of 26 October 2026 — payments, categorised messaging, learning goals, dashboards & landing page

| Area | Change | Where |
|------|--------|-------|
| Payments | Part payments (one `payments` row per installment), `total_fee`/`term`/`class_id` allocation, receipt upload + download, filters, totals and a payment detail page (recording later moved to the parent area — see the entry above) | `Admin\PaymentController`, `admin/payments/*`, `admin.payments.receipt` |
| Classes | `max_students` is nullable — blank means "no limit" and capacity checks are skipped | `MakeMaxStudentsNullableOnClassesTable`, `Classes::hasCapacity()` |
| Assignments | Tutors attach up to six pictures; thumbnails appear for learners, tutors and oversight | `assignments.images`, `Tutor\AssignmentController` |
| Learner panel | "Live now / Up next" session card with Join and countdown at the top of the dashboard | `Learner\DashboardController`, `learner/dashboard.blade.php` |
| Dashboards | Monthly classes-per-tutor ranking with a month picker + six-month trend; "affirmation of the day" card on all dashboards | `Admin\DashboardController`, `partials/affirmation` |
| Learning goals | Tutors upload goals (optionally with a file); admins/CEO share them with parent and/or tutor; parents get a Learning Goals tab | `learning_goals`, `Tutor\LearningGoalController`, `Admin\LearningGoalController` |
| Messaging | Categorised broadcasts, inbox for every role, administrator category filters | `Admin\MessageController`, `MessageController` |
| Branding | Blue / white / orange palette (indigo removed, orange accents, red kept for "today" and errors) | all Blade views |
| Website | Public landing page with hero slider, live programmes, how-it-works, testimonials, about/contact and registration CTA | `routes/web.php` (`/`), `landing.blade.php` |
| Docs & tests | In-app user guide updated for every change; `tests/Feature/ClientAdditionsTest.php` | `help/user-guide.blade.php`, `PLAN_CLIENT_ADDITIONS.md` |


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
- Navigation Payments entry (Finance group) and dashboard payments stat card are rendered only for the CEO.
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
- Each message files an in-app `Notification` for the recipient; the sidebar menu and top bar show unread badges.
- Parents get a "Message the School" button and unread badge on their dashboard.

### Grouped left-sidebar navigation
- `resources/views/layouts/sidebar.blade.php` builds the menu from one `$navSections` array per role, so the
  CEO's ~15 links sit in labelled groups (Overview, People, Academics, Finance, Communication, Learning, Support)
  and a future screen is a single array line.
- School-coloured shell: navy gradient (`#0a2550`–`#0d3070`) with orange accents, orange bar on the active page,
  unread badges, and the signed-in user + logout pinned at the bottom.
- `resources/views/layouts/navigation.blade.php` is now a slim blue top bar (toggle, role badge, messages,
  notifications, help, logout). `layouts/app.blade.php` provides the shell CSS/JS.
- Desktop: the toggle collapses the sidebar to a 4.75rem icon rail; mobile/tablet (< 1024px): off-canvas drawer with
  overlay, close button and Escape support. Group and rail state persist in `localStorage`.
- Covered by `ClientAdditionsTest::test_the_sidebar_groups_the_menu_for_each_role`.

### Responsive headers (phones → desktop)
- **Landing page** (`resources/views/landing.blade.php`): the brand used `shrink-0` + `whitespace-nowrap`, which
  pushed the *Register* button ~90px past the viewport on 320–390px phones (`scrollWidth` 410 on a 320px screen).
  The wordmark now lives in a `min-w-0` flex child with `truncate`, the auth buttons stay `shrink-0`, and below
  `lg` the section links collapse into a `<details>` menu button (positioned under the header, closes on link tap
  or outside click) instead of a clipped horizontal strip. *Log in* moves into that panel below `sm`, and
  `section[id] { scroll-margin-top }` is 5rem on phones / 6rem from `sm` (4rem / 5rem sticky header + 1rem).
- **App shell** (`resources/views/layouts/navigation.blade.php`): quick actions are pinned with `shrink-0`, the
  wordmark hides below `sm` (logo only) so it can never squeeze the actions, the help icon hides below `sm` and
  the signed-in name below `md`, *Logout* collapses to its icon, icon-only buttons keep `aria-label`s, and the
  drawer toggle stays the only navigation entry point below `lg`.
- Verified with headless Edge at 320 / 360 / 390 / 414 / 640 / 768 / 1024 / 1440px: `scrollWidth` equals the
  viewport on both headers (only the clipped hero decorations sit outside, inside `overflow-hidden`).
- Guarded by `tests/Feature/TopBarResponsiveTest.php` (3 tests) and
  `LandingPageTest::test_header_collapses_into_a_menu_on_small_screens`.

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
