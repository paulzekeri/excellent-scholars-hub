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
- [x] **Monthly classes per tutor** — month picker, ranked bars and a six-month trend on the admin **and** CEO dashboard
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

