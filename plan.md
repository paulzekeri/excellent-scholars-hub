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
- [x] Repository moved to the **`paulzekeri`** GitHub account
      (`https://github.com/paulzekeri/excellent-scholars-hub`); local `origin` points there. Secrets and
      variables are per-repository and must be recreated in the new account.
- [x] Production target confirmed: `https://eshglobal.org` (branch `main`; the document root holds the app and the
      root `.htaccess` forwards every request into `public/`, so no docroot change is needed)
- [x] UAT target: `https://uat.eshglobal.org` (branch `uat`, separate database, demo accounts seeded)
- [x] `.env.uat.example` (UAT) / `.env.production.example` (production) templates (secure cookies, demo seeding only
      on UAT/testing)
- [x] `.github/workflows/deploy-cpanel.yml` (production, `main`) and `deploy-cpanel-uat.yml` (UAT, `uat`): Composer
      install → SFTP mirror (`lftp`) → prune deleted files → `migrate --force --seed`, `storage:link`,
      `config:cache`, `view:cache`, `esh:health` over SSH
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
      parent, **learner** and/or tutor; parent child screen gains a Learning Goals tab; tutors see goals shared
      with them; learners see shared goals on their dashboard and a **My Learning → Learning Goals** page with
      attachment downloads
- [x] **Categorised messaging** — broadcasts to parents / tutors / students / everyone / one class, fan-out
      `messages` + notifications, broadcast report, inbox for every role, category filters for administrators
- [x] **Inbox guidance** — `/messages` opens with a *"Want to send a message? Start by clicking a name below"*
      callout, and every contact row shows a visible **Send message** / **Open chat** button (with avatar, role
      badge, unread count and last-message preview) instead of relying on a hover; the preview line now reads
      *No messages yet — click below to send the first one.*
- [x] **Live inbox and conversation** — both `/messages` and `/messages/{user}` poll small JSON endpoints
      (`messages/updates/inbox`, `messages/{user}/updates`) every 4–5 seconds, so a new message appears without
      reloading. The thread poll marks incoming messages read, pauses while the tab is hidden, and re-renders only
      when the newest message id changes; the inbox poll also watches the unread total. The rendered HTML comes
      from the shared `messages/_contacts` / `messages/_thread` partials, so no duplicate markup, and no
      Pusher/WebSocket service or front-end build is needed. Tests: `MessageInboxTest`
- [x] **Live notifications and broadcast report** — the same pattern covers `/notifications` (personal feed,
      8s), `/admin/notifications` (manager, page one only, 6s) and `/admin/messages` (broadcast report including
      its three counters, 6s). Each JSON endpoint (`notifications/updates`, `admin/notifications/updates`,
      `admin/messages/updates`) compares a newest-id + unread-total watermark and returns the re-rendered partial
      (`notifications/_list`, `admin.notifications/_rows`, `admin.messages/_batches`) only when something changed;
      the notification manager's read modal now uses event delegation so freshly polled rows still open it.
      Tests: `LiveUpdatesTest`
- [x] **Installable app + phone alerts (PWA & Web Push)** — the app installs to a phone's home screen
      (`public/manifest.webmanifest` + generated icons + `public/service-worker.js`; an **Install app** button in the
      top bar uses Chrome's `beforeinstallprompt` and shows the iOS *Add to Home Screen* instructions otherwise).
      The **Enable phone alerts** card on `/notifications` subscribes the device to Web Push:
      `app/Services/WebPushCrypto.php` implements RFC 8291 encryption + the RFC 8292 VAPID JWT with `openssl` only
      (no Composer package), `WebPushService` keeps one row per device plus an outbox drained by `push:send`
      (scheduled every minute), and `Notification::booted()` queues a push for every in-app notification. A server
      with no VAPID keys stays completely silent. Tests: `WebPushTest`, `ProgressiveWebAppTest`; setup, cron and
      troubleshooting in `PWA.md`
- [x] **Hidden maintenance account** — `users.is_hidden` (migration `add_is_hidden_to_users_table`) plus
      `php artisan esh:create-super-admin`: a CEO-role account with full access that is filtered out of every
      user list, role count, dashboard total, messaging contact picker, broadcast/notification fan-out and
      enquiry owner dropdown, never receives password-reset mail, and returns 404 if another administrator
      tries to edit/delete/reset it. Re-running the command rotates its password. Tests: `SuperAdminTest`;
      docs: `MAINTENANCE-USER.md`
- [x] **Blue / white / orange branding** — navy/blue structure with orange actions and **sun-yellow highlights**;
      indigo, purple and red removed from every view (orange now carries warnings and attention too)
- [x] **Flyer look and feel** — Poppins headings + Inter body (`Caveat` script on the marketing page only), the
      landing hero rebuilt as the flyer: navy corner + wave, subject strip, colour-coded book illustration, brush
      underline, pale-blue callouts, sun sparkles and a navy footer with the school's socials
- [x] **LMS palette pass** — brand sun scale replaces Tailwind yellow/amber (attention), pale-blue table headers,
      `#F5F8FC` canvas, `partials/stat-card` with semantic tones (people/academics/finance/attention/success),
      sun active bar and unread badges, hairline top bar, `text-gray-400` meta lifted to `gray-500`
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
- [x] **Note 10 — profiles and testimonials** — **My profile** lets every member update their name, email
      and photo (replace/remove, JPG/PNG/WebP up to 4 MB; learners may keep no email), and submit a
      testimonial with a "publish my profile photo" consent question. The CEO reviews submissions in
      **Communication → Testimonials** (approve or reject with a reason); approved testimonials render on the
      landing page with the photo only when allowed, falling back to the sample quotes when nothing is
      approved. Tests: `ProfileTest` (7) + `TestimonialTest` (7), full suite 189 passing
- [x] **Note 8 — admin-authored affirmations** — administrators/CEO publish the weekly message from
      **Communication → Affirmations**; every dashboard (including learners) shows the latest active post in a
      colourful blue/orange card, with switch-off/delete and the built-in daily rotation as fallback.
      Tests: `AffirmationTest` (7), full suite 196 passing
- [x] **v1 wrap-up (30 October 2026)** — administrators (not just the CEO) maintain the **school bank accounts**
      that parents see; a parent can pay for **several classes in one submission** (one row per class plus a
      general row, shared receipt and reference); the messaging compose form has a **message-type dropdown**, so
      parents/tutors/learners can send a **testimonial** that administrators/CEO **edit and approve**, and approved
      quotes publish **three at a time in the landing slider**.
      Tests: `PaymentBatchTest` (9) + `TestimonialTest` (12), full suite 217 passing
- [ ] **V2 items marked on the quotation** — admin-managed landing slider (9-ii), social media links (9-iii),
      assessment reminders + guardian status page (13-P5), payment gateway (11), automatic Meet/Zoom links.
      **Not included in the current scope**; see the v2 section of `CLIENT_QUOTATION 2.md`.
- [x] **Note 14 — final quotation** — `CLIENT_QUOTATION 2.md` lists the delivered additions, reconciles the money
      (subtotal ₦260,000 + VAT ₦40,000 = **₦300,000**, matching the 2 × ₦150,000 milestones) and keeps the v2
      deferred section.


## Phase 12: Client Review of 1 November 2026
- [x] **One payment record for several children** — the parent form has a child checkbox list and a general +
      per-class row for every ticked child (`class_ids[] = learnerId:classId`); one submission, reference,
      receipt, account and date create one installment per row, and the CEO verifies the transfer once. The
      legacy single-child payload still works. Tests: `PaymentBatchTest` (11, two new)
- [x] **Role filter on Users** — `/admin/users?role=tutor` (plus status and name/username/email search) with
      quick role chips and per-role counts. Tests: `RoundTwoEnhancementsTest`
- [x] **Class Code optional** — blank codes are generated from the class name (`NAME-XXXXX`, unique); editing with
      a blank code keeps the current one. Tests: `RoundTwoEnhancementsTest`
- [x] **Repeat a schedule once** — `recurrence_rule` / `recurrence_until` / `recurrence_group` on
      `class_schedules`; `ClassScheduleService::createSeries()` creates every weekly session from the first date
      until the chosen end date, visible automatically to the class and tutor; the lists flag weekly sessions.
      New **Automatic video room (Jitsi Meet)** platform generates a free, reusable meeting link when the link is
      left blank. Tests: `RoundTwoEnhancementsTest`
- [x] **Weekly repeat on several days & times** — the schedule forms take any number of day + start + end rows
      (10:00 Monday, 14:00 Wednesday, 18:00 Friday) between **Repeats from** and **Repeat until**; each slot
      repeats on its own weekday and time with its own length, all rows sharing one series. Validation covers
      missing days, an end time before its start, and an end date before the first lesson.
      Tests: `WeeklySlotScheduleTest`
- [x] **Schedules filter** — the admin Schedules list filters by **Class**, **Tutor**, a **Start Time (day)**
      date picker and **Status**, keeps the filters across pagination, shows *Showing X of Y sessions*, offers
      **Today** / **This week** / **Reset** shortcuts and an empty state. Tests: `ScheduleFiltersTest`
- [x] **Schedules week view** — a weekly series can run for years, so the admin list now opens on the
      **current week (Mon–Sun)** instead of the whole history, groups sessions under a day heading with a
      per-day session count, highlights today, and moves between weeks with **Previous week** / **This week** /
      **Next week** (the class/tutor/status filters are kept, and filtering keeps the week you are browsing). A week fits on one screen, so there is no
      pagination; **All schedules** still offers the full history, 25 sessions per page, for audits, and a
      filtered week that matches nothing says so with links to clear the filters or browse everything.
      Saving or deleting a session returns to that session's own week. Tests: `ScheduleWeekViewTest`
- [x] **Tutor schedule week view** — the tutor *My Schedules* page gets the same treatment: current week
      (Mon–Sun) grouped by day with **Previous week** / **This week** / **Next week**, a session count, a
      **Today** shortcut, *No sessions scheduled this week* with a link to the full history, and
      **All schedules** (`?view=all`, 25 per page). A tutor only ever sees their own sessions. The week maths
      lives in `ClassScheduleService::weekStart()`, shared with the admin list, and the rows are shared through
      `tutor/schedules/_row.blade.php`. Migration `add_start_time_index_to_class_schedules_table` indexes
      `start_time` now that both screens filter and sort by it. Tests: `TutorScheduleWeekViewTest`
- [x] **Class Library (external Google Drive resources)** — `class_library_resources` holds a title, optional
      description, URL and type (link / video / PDF / document) for one class. **Academics → Class Library**
      (administrators + CEO) and **Teaching → Class Library** (tutors) add, filter and remove links; a tutor is
      limited to their own classes (validated with `Rule::exists(...)`, 403 on deleting another tutor's link).
      Learners get **My Learning → Class Library**, grouped by class, where the **title** is the clickable link
      (new tab, `rel="noopener noreferrer"`) and the URL is never shown; links must be real http(s) addresses.
      Tests: `ClassLibraryTest`
- [x] **Learning goals as free text** — `learning_goals.title` is now `TEXT`; the upload form is one 5,000-character
      goal area with the learner picker, "What success looks like" and notes removed (existing rows keep their
      data). A free **LanguageTool** wording assistant fixes spelling/grammar via
      `POST /tutor/learning-goals/polish` and fails gracefully offline. Tests: `RoundTwoEnhancementsTest`
- [x] **Individual goal sharing** — `learning_goal_shares` pivot (`learning_goal_id`, `user_id`, `role_slug`,
      `shared_by`); the admin Goals screen picks individual parents/learners/tutors (or everyone the goal is
      about) and shows chips; parent child page, learner page and tutor "Shared with you" all honour direct
      shares, and tutor downloads work for recipients. Tests: `RoundTwoEnhancementsTest`,
      `LearnerLearningGoalsTest`, `ClientAdditionsTest`
- [x] **By-name sharing no longer looks like "everyone"** — `share_all_parent` / `share_all_learner` /
      `share_all_tutor` record the explicit *Every … the goal is about* tick, so choosing people by name
      leaves the boxes unticked and the chips show only the named people. `shared_with_*` still means
      "shared with that role at all" for the filters and legacy scopes; the migration backfills the new
      flags only where nobody of that role was picked by name. Tests: `LearningGoalSharingTest`
- [x] **Long goals are readable in full** — the admin and tutor lists show an excerpt plus a *Read the full
      goal (n characters)* toggle (`partials/goal-text.blade.php`) instead of a dead cut-off; the tutor
      "Shared with you" list and the learner/parent pages all expose the complete text, and the learner
      dashboard preview is a 160-character excerpt linking to the goals page. Tests: `LearningGoalReadingTest`
- [x] **Documentation** — in-app user guide (What's New + Classes/Schedules/Fees/Learning goals sections),
      `PLAN_CLIENT_ADDITIONS.md` (Round 2) and this phase. Full suite: **225 passing**

