# Client Additions — Execution Plan

Source: notes captured after the client meeting (`new addition.txt`) and the revised
scope in `CLIENT_QUOTATION 2.md`.

Status legend: `[ ]` not started · `[~]` in progress · `[x]` done & verified

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
- [x] Palette: indigo → blue, orange accents, white surfaces (red stays reserved for
      "today" and errors).
- [x] `partials/affirmation` card (deterministic per day) on all four dashboards.

## Phase 6 — Learning goals (note 11)

- [x] Migration + `LearningGoal` model (`tutor`, `learner`, `class`, `sharedBy`).
- [x] Tutor: upload goals per learner or per class, with optional PDF/image evidence.
- [x] Admin/CEO: see every goal, share/unshare with parent and/or tutor, download.
- [x] Parent: new "Learning Goals" tab on the child detail screen (shared only).
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

