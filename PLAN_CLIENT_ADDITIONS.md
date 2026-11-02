# Client Additions — Execution Plan

Source: notes captured after the client meeting (`new addition.txt`) and the revised
scope in `CLIENT_QUOTATION 2.md`.

Status legend: `[ ]` not started · `[~]` in progress · `[x]` done & verified

---

## Meeting-note status (reviewed 29 October 2026)

The phases below cover meeting-note items **1–8** plus a set of follow-ups (password reset, role-aware
guide, sidebar). This is the honest picture of all 14 notes:

| Note | Item | Status |
|---|---|---|
| 1 | Parent records payment + receipt, CEO verifies | `[x]` — one submission can now cover **several classes** (a row per class plus a general row), each with its own amount and total fee; the receipt is shared by the batch |
| 2 | Categorised messaging | `[x]` |
| 3 | Learner ongoing/next class panel | `[x]` |
| 4 | Assignment images + optional max students | `[x]` |
| 5 | Monthly classes per tutor | `[x]` — admin/CEO can tick a class as completed (with audit + reopen); the report now counts **completed** classes and shows completed vs scheduled |
| 6 | Blue / white / orange branding | `[x]` |
| 7 | Tutor learning goals, shared by admin/CEO | `[x]` — goals are now a **long text area** (up to 5,000 characters) with a free **AI wording assistant**, and each goal is shared with **individual parents, learners and tutors** (pivot `learning_goal_shares`) or with everyone the goal is about; learners get a dashboard panel and a "My Learning Goals" page with attachment downloads |
| 8 | Affirmation card | `[x]` — administrators/CEO publish the weekly affirmation from **Affirmations**; every dashboard shows the latest active post (colourful card), with the built-in rotation as fallback |
| 9 | Landing page (slider, testimonials, about, register) | `[~]` — page + live programmes exist; testimonials are now member-written and CEO-approved; the slider remains static |
| 9-i | Testimonials backend + admin approval | `[x]` — members submit from **My profile**; the CEO approves/rejects; approved ones appear on the landing page (photo only when allowed) |
| 9-ii | Admin/CEO-managed slider | `[ ]` — **deferred to v2** (marked on the quotation) |
| 9-iii | Social media links | `[x]` — `@esh_tutoring` Instagram/X/TikTok/Facebook links published in the landing footer; **backend-managed links deferred to v2** |
| 10 | Profile update (name/email/photo) + testimonial consent, CEO tick | `[x]` — **My profile** (name, email, photo with replace/remove), testimonial submission with a photo-consent question, CEO approval/rejection with a note |
| 11 | Automated payment gateway | **Deferred by the client (29 Oct 2026)** — not in scope; `PaystackService` stays as the stub |
| 12 | Children without email: username login + reset | `[x]` — username login, provisioning, forced first-login change, guardian-delivered reset, parent-initiated reset and the backfill/audit commands are shipped (Phase 11) |
| 13 | Prospective-learner video-call assessment workflow | `[~]` — P1–P4 shipped (capture → booking → result → conversion); **P5 reminders/status page deferred to v2** |
| 14 | Final quotation with all additions and pricing | `[x]` — `CLIENT_QUOTATION 2.md` now carries the delivered-additions table, the VAT-inclusive total (₦260,000 + ₦40,000 VAT = ₦300,000) matching the 2 × ₦150,000 milestones, and the v2 deferred section |

**Client clarifications (29 October 2026):**

- Note 11 (payment gateway) is **deferred** — no gateway work in this scope.
- Note 12: the **guardian/parent email is mandatory** on every child account; there is no child account
  without a reachable guardian. Usernames must be **unique** and **linked to that guardian's email**
  (through `learners.parent_id → parents.user_id → users.email`, never a copied column).
- The **admin provisions accounts**: a parent gets **username + email + password**, the password is
  emailed and the parent must change it at first sign-in; a child gets **username + password +
  optional email**, the details go to the guardian's account, and the child must also change the
  password at first sign-in.
- **Delivery rule:** the child's **own email is used when it exists**, otherwise the guardian's — for
  account details and password resets alike. The child's email stays **optional on the register page**
  (never removed).
- Notes 12 and 13 are **design-first on purpose**: they change authentication for every role and add a
  domain that exists before a user account does. The design, schema, phasing, test plan and the seven
  open client decisions (D1–D7) live in `PLAN_LOGIN_AND_ADMISSIONS.md`; nothing there is implemented yet.

---

## Phase 1 — Payments (notes 1–4)

- [x] Migration `add_billing_fields_to_payments_table`: `term`, `class_id`, `total_fee`,
      `receipt_path`, `receipt_name` + index on (`learner_id`, `term`, `class_id`).
- [x] Part payments = one `payments` row per installment; balance is
      `total_fee - SUM(amount WHERE status = 'completed')` for a learner/term/class.
- [x] Receipt upload (`pdf|jpg|jpeg|png|webp`, 5 MB) stored on the `public` disk,
      previewed and downloadable from the CEO area and (for their own children) the parent area.
- [x] "Fine tuning": filters (learner, term, class, status), summary tiles
      (recorded / instalments / outstanding), per-term progress bars, and the missing
      `admin/payments/show` page (the route existed but had no view → 500).
- [x] `Payment::summaryFor()` helper + `Payment::scopeCompleted()`.
- [x] **Handover:** parents record the payment (a parent pays for two or three children,
      the CEO was doing it for a hundred). `/parent/payments` limits the child picker to
      their own children, saves every submission as `pending`, stores the receipt and
      notifies the CEO. The CEO screen keeps only **Payment received** (`verify` →
      `completed`, fee marked complete) and **Cannot match it** (`reject` + reason), with
      `payments.verified_at` / `verified_by` as the audit trail. `admin.payments.create`
      and `store` were removed. Migration `add_verification_columns_to_payments_table`.
      Tests: `tests/Feature/ParentPaymentsTest.php` (9).
- [x] **Payment methods + school accounts:** bank transfer (CEO-maintained accounts, required
      "which account" picker, `payments.paid_into` snapshot), cash at the office, and card
      shown as *coming soon* (Paystack keys/config already in place via
      `App\Services\PaystackService`). CEO-only `/admin/payment-accounts` adds, edits,
      switches off and deletes accounts; deleting keeps the historical snapshot.
      Migrations `create_payment_accounts_table` + `add_payment_source_columns_to_payments_table`.
      Tests: `tests/Feature/PaymentMethodsTest.php` (8).
- [x] **Role-aware user guide:** the Help page renders only the reader's role section (admin /
      tutor / learner / parent), keeps the shared Messages, Notifications, What's New and FAQ
      sections, hides CEO-only payment/bank-account walkthroughs from administrators and
      parents, and filters FAQ entries by role. Tests: `tests/Feature/UserGuideRolesTest.php` (6).
- [x] **Self-service password reset:** `password.request` / `password.email` / `password.reset` /
      `password.update` with one-time tokens (60 minutes, single use); the login page links to it,
      the reset signs the user in on their role dashboard, and the views match the login styling.
      `Admin\UserController@sendPasswordReset` gives administrators a fallback that generates the
      link on the Users screen when mail is not configured, refuses inactive accounts, and only
      lets the CEO reset a CEO. Tests: `tests/Feature/PasswordResetTest.php` (11).

## Phase 2 — Classes (note 8)

- [x] `max_students` nullable (raw `ALTER`, no `doctrine/dbal` dependency).
- [x] Validation `nullable|integer|min:1`; enrolment skips the capacity check when null.
- [x] Create/edit forms say "leave blank for no limit"; list/detail show "No limit".

## Phase 3 — Assignments (note 7)

- [x] Migration adds `assignments.images` (JSON) + `Attachment` helpers on the model.
- [x] Tutor creates an assignment with up to 6 pictures (2 MB each).
- [x] Pictures visible to learners (assignment list), tutors (index + submissions),
      admin/CEO oversight, and parents via the child detail screen.

## Phase 4 — Learner panel (note 6)

- [x] "Now / Up next" panel at the top of `/learner/dashboard`: a live session
      (`start_time <= now <= end_time`) wins, otherwise the nearest upcoming one,
      with Join and a countdown label. Today highlight is kept.

## Phase 5 — Dashboard reporting + brading (notes 9, 10, 12)

- [x] Monthly classes-per-tutor ranking with a month picker on the admin **and** CEO
      dashboard (`?month=YYYY-MM`), plus a 6-month trend strip.
- [x] **Note 5 completion tick:** administrators and the CEO can mark a past session completed (or reopen it)
      from Schedules; the tick records `completed_at` / `completed_by` and an `activity_logs` row. The
      monthly report and trend now count **completed** classes and show "completed of scheduled" per tutor,
      so the number matches what a tutor is paid for.
- [x] Palette: navy → blue, orange accents, sun-yellow highlights, white surfaces; **red removed entirely**
      (orange carries actions, warnings and attention).
- [x] Flyer typography and landing treatment: Poppins headings / Inter body (Caveat script only on the public
      page), navy corner + wave, subject strip, book illustration, brush underline, pale-blue callouts, navy footer
      with `@esh_tutoring` socials. Existing `logo.jpg` kept per the client's instruction.
- [x] LMS palette pass: sun scale for attention (replaces yellow **and** amber), pale-blue table headers with navy
      labels, `#F5F8FC` canvas, shared `partials.stat-card` (blue people / navy academics / orange finance / sun
      attention / green success), sun sidebar active bar + unread badges, hairline top bar.
- [x] `partials/affirmation` card (deterministic per day) on all four dashboards.

## Phase 6 — Learning goals (note 11)

- [x] Migration + `LearningGoal` model (`tutor`, `learner`, `class`, `sharedBy`).
- [x] Tutor: upload goals per learner or per class, with optional PDF/image evidence.
- [x] Admin/CEO: see every goal, share/unshare with parent, **learner** and/or tutor, download.
- [x] Parent: new "Learning Goals" tab on the child detail screen (shared only).
- [x] Learner: "Goals shared with you" dashboard panel and a **My Learning → Learning Goals** page
      (own goals + class-wide goals for their classes, attachment downloads, 403 for anything not shared).
- [x] Tutor: "Shared with you" section on the goals screen.

## Phase 7 — Categorised messaging (note 5)

- [x] Broadcast composer at `/admin/messages` — audience: parents, tutors, learners,
      everyone, or one class; fan-out creates a `messages` row + notification per
      recipient, tagged with `audience`, `class_id` and a shared `batch`.
- [x] Recipients (including learners and tutors, who previously had no inbox) can
      read and reply; replies stay one-to-one in the same thread.
- [x] Inbox category tabs for administrators (All / Parents / Tutors / Learners).
- [x] Conversation permission matrix centralised in the controller.

## Phase 8 — Public landing page (note 13)

- [x] `/` shows a marketing page for guests: hero slider, programmes (from the
      classes table), how-it-works, testimonials, about, contact and a
      "Register for classes" call to action; signed-in users still redirect to their
      dashboard.

## Phase 9 — Documentation & tests

- [x] In-app user guide (`resources/views/help/user-guide.blade.php`) rewritten for
      every new screen + updated table of contents.
- [x] `plan.md` Phase 11, `README.md` feature list, this plan updated.
- [x] Feature tests: `tests/Feature/ClientAdditionsTest.php` (part payments/receipts,
      cashier guards, optional class capacity, assignment pictures, broadcast audiences, learning-goal
      sharing, learner next-class panel, monthly tutor report, grouped sidebar menu per role,
      and a render smoke test for every new screen) plus `tests/Feature/ParentPaymentsTest.php`
      (9 tests for the parent records → CEO verifies flow) and
      `tests/Feature/PaymentMethodsTest.php` (8 tests for the CEO's bank-account CRUD,
      transfer/cash/card rules, the method filter and deleted-account history) and
      `tests/Feature/UserGuideRolesTest.php` (6 tests for the role-aware Help page) and
      `tests/Feature/PasswordResetTest.php` (11 tests for forgot-password, token expiry/replay and the admin fallback).
- [x] `php -l` on every touched PHP file, `php artisan migrate`, `view:cache`,
      `php artisan test`.

## Phase 10 — Scalable navigation (left sidebar)

The animated top bar had grown to ~15 links for the CEO, so the menu moved into a
grouped, school-coloured sidebar.

- [x] New `resources/views/layouts/sidebar.blade.php`: one PHP menu definition per
      role (`$navSections`), rendered as labelled groups — Overview, People, Academics,
      Finance (CEO only), Communication, Learning, Support (`Teaching` / `My Learning`
      for tutors and learners). Adding a future screen = one array line.
- [x] `resources/views/layouts/navigation.blade.php` slimmed to a blue top bar with
      the menu toggle, role badge, unread message/notification counters, help and logout.
- [x] Sidebar uses the school colours (navy `#0a2550`–`#0d3070` with orange accents),
      highlights the current page, shows unread badges and remembers which groups each
      user folded away (`localStorage`).
- [x] Desktop: the top-bar button collapses the sidebar to an icon rail (remembered);
      mobile: off-canvas drawer with overlay, close button and Escape support.
- [x] `resources/views/layouts/app.blade.php` wraps authenticated pages in the shell
      (`esh-shell` / `esh-sidebar`) without touching any page view.
- [x] User guide navigation sections rewritten for the sidebar; feature test
      `test_the_sidebar_groups_the_menu_for_each_role` asserts the right groups per role.

## Phase 11 — Note 12: usernames, guardian email and the first-login password change

Design: `PLAN_LOGIN_AND_ADMISSIONS.md` §1. Built on 29 October 2026; the reset-by-username half is the
next slice.

- [x] Migration `2026_10_29_000001_add_username_to_users_table`: unique `username`, nullable `email`
      (raw driver-aware ALTER, no doctrine/dbal), `must_change_password`, `password_changed_at`.
- [x] Login accepts an **email or a username**: `App\Auth\EmailOrUsernameUserProvider` registered as
      the `esh-eloquent` provider, `LoginController` (`username()` / `credentials()`), login field
      renamed to `login`. Deactivated accounts are now refused by the login query itself.
- [x] Registration: username required for learners, child email **optional** (never removed), guardian
      name + email mandatory, a staff email cannot be used as the guardian address, and a child's own
      email cannot equal the guardian's. A guardian account created during registration gets a
      generated username and is flagged for a password change.
- [x] Registration form order: **"I am a — Learner / Tutor"** is now the first field (two selectable
      cards, learner listed first). The shared inputs are re-worded for a learner — *Learner Full Name*,
      *Learner Username*, *Learner Email*, *Learner Phone*, *Learner Password*, *Confirm Learner Password*,
      *Create Learner Account* — and the guardian block is titled **Parent/Guardian Details** with
      *Parent/Guardian Full Name* / *Parent/Guardian Email*, so learner fields can never be confused with the
      parent's. The button/labels switch back to tutor wording when Tutor is chosen, and `role_id` is now
      validated against `learner`/`tutor` so the public form cannot create other roles.
      Covered by `tests/Feature/RegistrationFormTest.php`.
- [x] Admin provisioning: **Users → New User** creates a parent with username + email + temporary
      password; **Edit parent → Add child** creates a learner with username + password + optional
      email. Both email the credentials (`AccountProvisionedMail`) and are flagged.
- [x] No-mail fallback: when the mailer cannot deliver (`log`/`array`), the credentials are flashed on
      the Users screen for the administrator to hand over privately.
- [x] Forced first-login change: `EnsurePasswordIsChanged` (web middleware group),
      `ChangePasswordController`, full-page interstitial with current/new/confirm,
      `different:current_password`, flag cleared and `password_changed_at` stamped; logout stays
      reachable.
- [x] Users screen: username column with the guardian's email, a "No email — via guardian" fallback and
      an "Awaiting first sign-in" badge.
- [x] Tests: `UsernameLoginTest` (12), `AccountProvisioningTest` (11), `GuardianPasswordResetTest` (12)
      and `MaintenanceCommandsTest` (8); `AdminUserParentLinkTest` rewritten (a learner always needs a
      guardian and detaching is refused); `PasswordResetTest` updated for the new login field and the
      neutral reset response. Full suite: **122 passing**.
- [x] **Forgot password by email or username**: unknown identifiers get a neutral answer; a user with
      their own email keeps the Laravel broker; a child without email gets a per-user token
      (`noemail:{id}`, so siblings never collide) emailed to the guardian, and the link resets the
      password and signs them in.
- [x] **Parent-initiated reset**: the parent dashboard's "Children & logins" panel lists each child's
      username and sends the one-time link to the guardian's own address; another family's child
      returns 403.
- [x] **Admin fallback**: resetting a child without email from **Users** emails the guardian, or flashes
      a one-time link to the administrator when the mailer cannot deliver (`log`/`array`).
- [x] **Operator commands**: `esh:backfill-usernames` (dry-run, `--apply`, `--role`) and
      `esh:audit-guardian-links` (reports learners with no guardian email; `--learner` checks one child).
- [x] `DEPLOYMENT.md` documents the one-time backfill + audit run after the migration, and the user
      guide covers the new login, provisioning and reset flows.

## Phase 12 — Note 13: admissions pipeline

Design: `PLAN_LOGIN_AND_ADMISSIONS.md` §2. Phase 1 shipped on 29 October 2026.

- [x] **P1 — capture, pipeline, timeline**: `enquiries` + `enquiry_notes` migrations, `Enquiry` /
      `EnquiryNote` models (reference `ENQ-YYYY-0001`, source and status vocabularies, guarded
      transitions), `Admin\EnquiryController` (index with tabs/search/source, create, store, show,
      edit, update, status, timeline note), the **People → Admissions** sidebar entry, and the
      **New Enquiries / Enquiries In Progress** dashboard counters.
- [x] Status changes write a timeline entry **and** an `activity_logs` row; declining requires a reason;
      archived is final; assessment/enrolment targets stay closed until those screens exist.
- [x] Tests: `tests/Feature/AdmissionsTest.php` (12) — permissions, capture without an account,
      validation, filters, timeline, guarded moves, decline reason, edit, counters, render smoke.
- [x] **P2 — assessment booking**: public request form on the landing page (`POST /book-assessment`,
      rate-limited + honeypot) creating an enquiry with `source = website` and notifying every
      administrator/CEO; school booking from the enquiry screen (tutor, date/time, duration, platform,
      Meet/Zoom link, guardian email) → `assessment_booked`, in-app tutor invitation, timeline entry,
      **guardian email with the join link** and a pre-filled WhatsApp message; `/admin/assessments`
      (Today / Upcoming / Past / All + tutor filter + Join) and `/tutor/assessments` (own calls only);
      "Today's assessment calls" panels on both dashboards; cancel returns the enquiry to `Contacted`.
      No learner/parent account is created at any point.
- [x] Tests: `tests/Feature/AssessmentBookingTest.php` (13) — public booking, honeypot, validation,
      booking guards, guardian email, tutor isolation, admin list, cancellation, dashboard panels.
- [x] **P3 — assessment result and placement**: `AssessmentResultService` records completed/no-show with
      outcome, strengths, focus areas, internal notes, a recommended class + level and an optional
      PDF/image report (5 MB, replaceable, downloadable by the school and the tutor). A completed result
      moves the enquiry to **Assessed**; choosing a recommended class moves it straight to **Awaiting
      enrolment**. A no-show returns the enquiry to **Contacted** for rebooking, and a no-show cannot be
      edited into a completion. Manual status moves to those two states are guarded by the result.
      Tests: `tests/Feature/AssessmentResultTest.php` (11).
- [x] **P4 — conversion wizard**: from **Awaiting enrolment**, **Convert to learner** creates the parent
      account (or reuses one matched by email) and the learner account with unique usernames and temporary
      passwords, enrols the child in the recommended class, marks the enquiry **Enrolled**, records the
      timeline/activity log and emails both sets of credentials (flashing them when mail is off). Guards:
      placed only, guardian email present and not a staff/learner address, class capacity, duplicate
      usernames refused, idempotent via a locked transaction. Tests: `EnquiryConversionTest` (10).
- [ ] **P5** — optional cron reminders, guardian status page, automatic Meet/Zoom links. **Deferred to v2
      and marked on the quotation** (the deployment has no cron; today's panels already cover visibility).

---

## Round 2 — client review of 1 November 2026

All six items are implemented and verified. New/updated tests:
`tests/Feature/RoundTwoEnhancementsTest.php` (6), `PaymentBatchTest` (+2),
`LearnerLearningGoalsTest` (updated for the removed target/notes). Full suite: **225 passing**.

| # | Client request | Status |
|---|---|---|
| R1 | "If a parent has many children, record payment once for all children, not per child" | `[x]` |
| R2 | "On tutor page, do filtering by role" / `…/admin/users` filter by roles | `[x]` — the Users page (where tutors are listed) now has a role filter |
| R3 | Class Code should not be mandatory when creating a class | `[x]` |
| R4 | Set a schedule once with a repeat (every Monday, same time, users and video link automatic) | `[x]` |
| R5 | Learning goals: drop "What success looks like", learner and notes; take enough text; free AI wording correction | `[x]` |
| R6 | Learning goals shared to individual parents / learners / tutors | `[x]` |

### R1 — one payment for the whole family

- The parent payment form now shows a **child checkbox list** ("Children covered by this payment"). Ticking a
  child reveals a **General payment** row and one row per class that child attends.
- Every row posts as `class_ids[]=<learnerId>:<classId>` (`:0` = general), with `amounts[<key>]` and
  `total_fees[<key>]`. `Parent\PaymentController::installments()` + `rowTarget()` create one `payments` row per
  row, all under **one reference, one receipt, one bank account and one date**, so the CEO verifies the transfer
  once. The old single-child payload (`learner_id` + plain class ids) is still accepted.
- Rows for another family are rejected (`class_ids.<key>`), the CEO notification summarises the total and names
  every child, and the success message reports "N installments for M children".

### R2 — role filter on the users page

- `Admin\UserController@index` accepts `role`, `status` and `q` (name/username/email), with
  `withQueryString()` pagination.
- `admin/users/index` adds a filter form plus quick role chips showing the count per role
  (`All users (n)`, `Tutor (n)`, `Learner (n)`, …).

### R3 — optional class code

- `ClassController` validates `code` as nullable; a blank code becomes `NAME-XXXXX` from the class name
  (`generateCode()`), guaranteed unique. Editing with a blank code keeps the existing code.
- Create/edit screens say "(optional) / Leave blank to auto-generate".

### R4 — set once, repeat weekly, automatic video room

- Migration `add_recurrence_to_class_schedules_table`: `recurrence_rule`, `recurrence_until`,
  `recurrence_group` (+ index).
- `App\Services\ClassScheduleService::createSeries()` creates one session per week from the first date until
  `repeat_until` (max 52), all sharing `recurrence_group`; duration, tutor, class, meeting link and platform are
  copied. Because they are ordinary sessions, learner/tutor dashboards, attendance, completion and the monthly
  tutor report need no changes.
- New platform **"Automatic video room (Jitsi Meet)"**: with a blank link the service generates a free
  `https://meet.jit.si/esh-…` room once and reuses it for every session in the series.
- Both schedule forms (admin and tutor) gain **Repeat** (does not repeat / every week) and **Repeat until**,
  and both lists flag weekly sessions.
- **Several days at different times:** the weekly repeat takes any number of day + start + end rows
  (`slots[0][day]`, `slots[0][start]`, `slots[0][end]`, …) plus **Repeats from** and **Repeat until**, so
  *10:00 Monday, 14:00 Wednesday and 18:00 Friday* are created from one submission. Each slot keeps its own
  length (`end - start`), the first lesson of each day is on/after `repeats_from`, every slot stops at
  `repeat_until`, and all rows share one `recurrence_group` (52 sessions per slot, 364 per form).
  `ClassScheduleService::repeatRules()` / `seriesErrors()` hold the shared validation (each slot must end after
  it starts, the end date may not precede the first lesson), `slotSummary()` writes the flash message, and
  `ClassSchedule::seriesLabel()` lists the series' days for the *Weekly* badge tooltip. The old single-date
  payload still works, so existing links and tests are unaffected. Covered by `tests/Feature/WeeklySlotScheduleTest.php`.
- **Schedules filters:** `/admin/schedules` takes `class_id`, `tutor_id`, `date` (a single day of `start_time`,
  via `whereDate`) and `status`, so the school can drill into "everything on this day / for this class / with
  this tutor". Filters survive pagination (`withQueryString()`), a hand-typed or invalid date is ignored instead
  of erroring, the bar reports *Showing X of Y sessions* with **Today** / **Any day** shortcuts and a **Reset**,
  and an empty result shows a message instead of a blank table. Covered by `tests/Feature/ScheduleFiltersTest.php`.

### R5 — learning goals: long text, no target/learner/notes, free AI check

- Migration `expand_learning_goal_text` changes `learning_goals.title` to `TEXT` (driver-aware, no doctrine/dbal).
  The tutor form now has one large **Goal** textarea (5,000 characters) and no "What success looks like",
  learner picker or notes field. `description`/`target` remain in the schema for existing rows but are no longer
  collected or shown.
- **Wording assistant** (`App\Services\GoalWordingService`): the **Improve wording (free AI)** button posts the
  text to `POST /tutor/learning-goals/polish` (throttled), which calls the free public LanguageTool API
  (`https://api.languagetool.org/v2/check`, no key), applies the first replacement for each match and returns the
  corrected paragraph plus a count. Failures (no internet / API down) return a friendly message and leave the
  text unchanged. Config: `config/services.php → languagetool` + `GOAL_WORDING_ASSISTANT`, `LANGUAGETOOL_URL`,
  `LANGUAGETOOL_LANGUAGE`, `LANGUAGETOOL_TIMEOUT`.

### R6 — share a goal with individual people

- Migration `create_learning_goal_shares_table`: one row per (`learning_goal_id`, `user_id`) with `role_slug`
  (`parent` / `learner` / `tutor`) and `shared_by`; `LearningGoal::recipients()` is a `belongsToMany`.
- The admin Goals screen replaces the three switches with **Choose people**: multi-selects for Parents,
  Learners and Tutors (current picks pre-selected), optional "All parents/learners/tutors the goal is about",
  and chips showing exactly who has the goal. A new **Shared with chosen people** filter lists them.
- Visibility now honours both styles:
  - parent goals tab — legacy `shared_with_parent` scope **or** a direct share to that parent;
  - learner dashboard / "My Learning Goals" — `scopeVisibleToLearner()` includes direct shares and
    `isVisibleToLearner()` checks them first;
  - tutor "Shared with you" — legacy class-wide tutor sharing **or** a direct share; downloads now work for
    goals shared by name (previously 403 for the recipient).
- The legacy `shared_with_*` payload is still supported, and it also adds/removes the implied person
  (the learner's parent, the learner, the tutor) so both sharing styles stay in step.

### Documentation

- In-app **Help → What's new in this release**: rows updated for Fees, Learning goals and Classes, new rows for
  Schedules and Users; the Classes, Schedules, Fees and Learning-goals sections were rewritten for the new flow.
- `PLAN_CLIENT_ADDITIONS.md` (this file) and `plan.md` (Phase 12) list the same changes.

