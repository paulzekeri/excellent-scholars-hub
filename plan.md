# ESH LMS - Implementation Plan

> Status: **delivered**. This checklist reflects the shipped system; the post-delivery
> enhancement phase at the bottom covers the CEO / oversight / messaging changes.

## Phase 1: Foundation Setup
- [x] Laravel 8 installed
- [x] Tailwind CSS via Laravel Mix (no Livewire dependency)
- [x] `.env` with database settings
- [x] MySQL database

## Phase 2: Database Schema (Migrations)
- [x] Roles table (custom `roles` table, no spatie dependency)
- [x] Users table with `role_id` + `is_active`
- [x] Learners, parents, tutors tables
- [x] Classes, class_schedules tables
- [x] Attendance table
- [x] Assignments, submissions tables
- [x] Learning_materials table
- [x] Payments table
- [x] Notifications table
- [x] Activity_logs table
- [x] `ceo` role migration (`2023_01_01_000014_add_ceo_role_to_roles_table`)
- [x] `messages` table (`2023_01_01_000015_create_messages_table`)

## Phase 3: Models & Relationships
- [x] User model with role helpers (`isCeo`, `isAdmin`, `isAdministrator`, `isTutor`, `isParent`, `isLearner`, `canManagePayments`, `unreadMessagesCount`)
- [x] Learner, ParentModel, Tutor models
- [x] Classes, ClassSchedule models
- [x] Attendance model
- [x] Assignment & Submission models
- [x] LearningMaterial model
- [x] Payment model
- [x] Notification model
- [x] Message model with `between()` scope
- [x] ActivityLog model

## Phase 4: Authentication & Middleware
- [x] Custom login/registration with roles
- [x] Role-based middleware (`CheckRole`) — supports comma-separated roles (`role:admin,ceo`)
- [x] Password reset functionality
- [x] Login redirect via `isAdministrator()` so the CEO lands on the admin dashboard

## Phase 5: Dashboard Module
- [x] Admin dashboard with stats (payments card and title adapt for the CEO)
- [x] Tutor dashboard
- [x] Parent dashboard (per-child stats, clickable detail tabs, missed-class alerts, "Message the School" + unread badge)
- [x] Learner dashboard

## Phase 6: Core CRUD Modules
- [x] User management (CRUD; `ceo` role hidden/locked for non-CEO admins)
- [x] Class management
- [x] Schedule management
- [x] Attendance tracking
- [x] Assignments management
- [x] Learning materials management
- [x] Payment management (CEO only)
- [x] Notification management
- [x] Messaging (parents ↔ CEO/administrators)

## Phase 7: Live Classroom
- [x] Schedule meetings with Google Meet/Zoom links
- [x] Join class with one click

## Phase 8: Frontend UI
- [x] Tailwind CSS layouts
- [x] Responsive mobile-first design
- [x] Navigation menu in a school-coloured left sidebar: grouped sections, active-page highlight, unread badges, collapsible icon rail on desktop and an off-canvas drawer on mobile
- [x] Dashboard components
- [x] In-app user guide (`/help/user-guide`)

## Phase 9: Client-Requested Enhancements
- [x] **CEO-only payments** — `ceo` role, `role:ceo` route group, controller-level gate, hidden nav link + dashboard card
- [x] **Admin/teacher submission review** — oversight `submissions` / `gradeSubmission` / `downloadSubmission`, tutor ownership guard
- [x] **Parent ↔ CEO/admin in-app messaging** — `messages` table/model/controller/views, notifications on send, unread badges
- [x] `DatabaseSeeder` made idempotent (`firstOrCreate`) so existing installs create the dedicated CEO account with `php artisan db:seed`
- [x] **Admin learner ↔ parent linking** — optional `parent_id` on the learner create/edit screens (`exists:parents,id`), learner profile auto-created on role change, linked parent shown in the Users table and linked learners listed on the parent's edit page
- [x] **Parent dashboard improvements** — per-child counters (schedule / pending / completed / materials) that open tabbed details, attendance rate, missed-class alerts, unrecorded-session notice, child-ownership guard and feature tests
- [x] Documentation updated (`README.md`, in-app user guide)

## Phase 10: Deployment
- [x] UAT target confirmed: `uat-esh.lit-grp.com` (cPanel account `litgrpco`; the document root holds the app and the
      root `.htaccess` forwards every request into `public/`, so no docroot change is needed)
- [x] `.env.uat.example` / `.env.production.example` templates (secure cookies, UAT-only demo seeding)
- [x] `.github/workflows/deploy-cpanel.yml`: push to `main` → Composer install → SFTP mirror (`lftp`) → prune deleted
      files → `migrate --force --seed`, `storage:link`, `config:cache`, `view:cache`, `esh:health` over SSH
      (same pattern as `tcntiu-debug/Smart-Church`, which publishes `dev.lit-grp.com` on push)
- [x] root `.htaccess` front controller (403 for `.env`/`artisan`/`composer.*`/`*.sql|log|sh|yml`) + `.user.ini`
      (100M uploads, 256M memory, `session.upload_progress.enabled=0`)
- [x] `DEPLOYMENT.md` runbook (Actions deploy, manual SSH, cPanel Git pipeline, release-zip fallback, smoke tests,
      production promotion, rollback)
- [x] `.cpanel.yml` fallback pipeline (same layout; composer install, migrate, storage link, caches, health check)
- [x] `deploy/build-release.ps1` release packager for File Manager uploads
- [x] `php artisan esh:health` post-deploy check
- [x] `php artisan esh:create-admin` first-production-login command
- [x] `DatabaseSeeder` guard: demo accounts skipped in production unless `SEED_DEMO_ACCOUNTS=true`
- [x] `tests/Feature/DeploymentToolingTest.php` (7 tests)

## Phase 11: Client Meeting of 26 October 2026
- [x] **Payments fine-tuned with part payments** — one row per installment, `total_fee`, `term`, `class_id`,
      receipt upload/download, filters, summary tiles and the missing payment detail page
- [x] **Receipt upload** — `pdf|jpg|jpeg|png|webp`, 5 MB, stored on the `public` disk and downloadable only inside
      the CEO area
- [x] **Pay per term / per class** — `Payment::summaryFor()` groups installments by learner + term + class
- [x] **Classes: `max_students` optional** — nullable column (driver-aware `ALTER`, no `doctrine/dbal`),
      `Classes::hasCapacity()`, "leave blank for no limit" on the forms
- [x] **Assignment pictures** — `assignments.images` JSON, up to six 2 MB images, thumbnails for learners, tutors
      and admin oversight
- [x] **Learner dashboard: live/nearest class panel** — live session wins, otherwise the nearest upcoming one,
      with Join and a countdown
- [x] **Monthly classes per tutor** — month picker, ranked bars and a six-month trend on the admin **and**
      CEO dashboard, counting classes **ticked as completed** (with completed vs scheduled shown)
- [x] **Learning goals** — tutors upload (per learner/class, optional file); `role:admin,ceo` screen shares with
      parent and/or tutor; parent child screen gains a Learning Goals tab; tutors see goals shared with them
- [x] **Categorised messaging** — broadcasts to parents / tutors / students / everyone / one class, fan-out
      `messages` + notifications, broadcast report, inbox for every role, category filters for administrators
- [x] **Blue / white / orange branding** — indigo removed from all 38 affected views, orange accents, red kept for
      "today" and errors
- [x] **Affirmation of the day** — rotation card on all four dashboards
- [x] **Public landing page** — hero slider, live programmes, how-it-works, testimonials, about/contact and
      register CTA at `/` for guests (signed-in users still redirect to their dashboard)
- [x] **Grouped left sidebar navigation** — role menus split into Overview / People / Academics / Finance (CEO) /
      Communication / Learning / Support, school-coloured shell, collapsible icon rail on desktop, off-canvas drawer
      on mobile, remembered group + rail state, and a feature test per role
- [x] **Responsive headers & top menu** — the phone-size headers no longer overflow: the landing brand truncates
      instead of using `shrink-0`/`whitespace-nowrap`, section links collapse into a `<details>` menu below `lg`,
      the app wordmark hides below `sm` while quick actions stay pinned, and the sticky-header offsets were
      retuned; guarded by `tests/Feature/TopBarResponsiveTest.php` + `LandingPageTest` (47 feature tests)
- [x] **Parents record payments, the CEO verifies them** — `Record Payment` left the CEO screen and now lives at
      `/parent/payments` (children linked to the parent only, installments saved as `pending`, receipt upload,
      CEO notification). The CEO queue verifies (`completed`, fee marked complete) or rejects with a reason, and
      `payments.verified_at` / `verified_by` record who confirmed the money; parent dashboard gains a per-child fee
      line and the sidebar a Finance group. Covered by `tests/Feature/ParentPaymentsTest.php` (9 tests)
- [x] **Payment methods & school bank accounts** — the parent form offers **bank transfer**, **cash** and **card**.
      A transfer shows only the switched-on school accounts (copy button + "which account did you pay into?") and
      saves a `paid_into` snapshot; cash needs no account and tells the CEO to check the cash book; card is
      disabled as "coming soon". The CEO's new `/admin/payment-accounts` screen adds, edits, switches off and
      deletes accounts, and shows whether Paystack is connected; `App\Services\PaystackService` holds the future
      initialise/verify calls. Covered by `tests/Feature/PaymentMethodsTest.php` (8 tests)
- [x] **Role-aware user guide** — the in-app Help page now renders only the sections (and FAQ entries) for the
      signed-in role: administrators see the admin guide without finance, the CEO also sees payments/bank accounts,
      tutors, learners and parents each get their own section, and the TOC matches. Covered by
      `tests/Feature/UserGuideRolesTest.php` (6 tests)
- [x] **Self-service password reset** — `Forgot your password?` on the login page emails a one-time link
      (60 minutes, single use) and signs the user in after they choose a new password. When mail is not
      configured (UAT uses `log`), administrators generate the same link from **Admin → Users → Reset password**
      and hand it over privately; inactive accounts are refused and only the CEO can reset a CEO. Covered by
      `tests/Feature/PasswordResetTest.php` (11 tests)
- [x] **Documentation** — in-app user guide (What's New, Payments, Learning Goals, Broadcasts, Dashboard extras,
      FAQ), `README.md`, `PLAN_CLIENT_ADDITIONS.md`
- [x] `tests/Feature/ClientAdditionsTest.php` — tests covering the items above
- [ ] **Note 11 (payment gateway) deferred by the client on 29 October 2026** — the card option stays
      "coming soon"; `App\Services\PaystackService` remains the prepared stub, no live keys required
- [x] **Note 12 (complete) — usernames, guardian email, provisioning, resets** — login accepts an email or
      a unique username; a learner may have no email but must have a guardian; admins provision parents
      and children and the credentials are emailed; school-set passwords must be replaced at first
      sign-in; a child resets by username and the one-time link goes to the guardian (or the parent sends
      it from the dashboard). Usernames can be backfilled and guardians audited with
      `esh:backfill-usernames` / `esh:audit-guardian-links`. Tests: `UsernameLoginTest` (12),
      `AccountProvisioningTest` (11), `GuardianPasswordResetTest` (12), `MaintenanceCommandsTest` (8),
      full suite 122 passing
- [x] **Note 13 (phase 1) — admissions pipeline** — capture WhatsApp/phone enquiries before any account
      exists, tabbed pipeline (Open / New / Contacted / Declined / Archived) with search and source
      filter, a detail screen whose timeline records calls, WhatsApp messages and status changes,
      guarded status moves (declining needs a reason; archived is final) and New/In-Progress counters on
      the admin and CEO dashboards. Tests: `AdmissionsTest` (12), full suite 134 passing
- [x] **Note 13 (phase 2) — assessment booking, public request and the shared call link** — parents can
      request an assessment from the landing page without an account; the school creates the Meet/Zoom
      link, assigns the tutor and emails the guardian the join link (WhatsApp message pre-filled too);
      admin/CEO (`/admin/assessments`), the tutor (`/tutor/assessments`) and the parent all join from the
      same link; today's calls appear on both dashboards and cancelling returns the enquiry to
      *Contacted*. Tests: `AssessmentBookingTest` (13), full suite 147 passing
- [x] **Note 13 (phase 3) — assessment result and placement** — the tutor or the school records the result
      (completed/no-show, outcome, strengths, focus areas, recommended class/level, notes and an optional
      PDF/image report). Completing moves the enquiry to *Assessed*; picking a recommended class moves it to
      *Awaiting enrolment*; a no-show returns it to *Contacted* for rebooking. Reports download for the
      school and the assigned tutor. Tests: `AssessmentResultTest` (11), full suite 158 passing
- [x] **Note 13 (phase 4) — conversion wizard** — from *Awaiting enrolment*, **Convert to learner** creates
      or reuses the parent account, creates the learner with a unique username and temporary password,
      enrols them in the recommended class, marks the enquiry *Enrolled* and emails the logins (flashing
      them when mail is off). Guards cover status, guardian email, staff emails, class capacity, duplicate
      usernames and double submission. Tests: `EnquiryConversionTest` (10), full suite 168 passing
- [ ] **Note 13 (phase 5)** — optional assessment reminders and a guardian status page. See
      `PLAN_LOGIN_AND_ADMISSIONS.md` §2
- [x] **Note 5 — admin/CEO "class completed" tick** — mark a past session completed or reopen it from
      Schedules; `completed_at` / `completed_by` and an activity-log entry record who ticked it. The monthly
      report and six-month trend now count **completed** classes and show "completed of scheduled" per tutor.
      Tests: `ScheduleCompletionTest` (7), full suite 175 passing
- [ ] Remaining gaps from the meeting note (see `PLAN_CLIENT_ADDITIONS.md`): admin-authored affirmations
      (note 8), dynamic
      slider + testimonials backend + social links (notes 9, 10), and the final quotation rewrite (note 14)

