# Excellent Scholars Hub (ESH)

A web-based Learning Management System (LMS) built on **Laravel 13** (PHP 8.4), **MySQL** and **Tailwind CSS**, with
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
- **Fees are paid by bank transfer (card is stubbed for Paystack).** A transfer must name the school account it
  went into, so `/admin/payment-accounts` lets only the CEO add, correct, switch off or delete the bank accounts.
  Active accounts appear on the parent form with a copy button; a switched-off account disappears without losing the
  payments already recorded (`payments.paid_into` keeps the snapshot). The school is online-only, so the parent form
  no longer offers cash (older cash records still verify against the cash book). Card shows as *coming soon* until
  `PAYSTACK_PUBLIC_KEY` / `PAYSTACK_SECRET_KEY` are set — see
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

Demo accounts are seeded automatically on `local`/`testing` installs, and on the live site only when
`SEED_DEMO_ACCOUNTS=true`. With the flag off they are skipped; create the first real login instead:

```bash
php artisan esh:create-admin --role=ceo
```

## Deployment

The repository is **`https://github.com/paulzekeri/excellent-scholars-hub`** (moved from
`tcntiu-debug/excellent-scholars-hub`). Two cPanel environments deploy from it with GitHub Actions:

| Environment | Trigger | URL | Workflow |
|---|---|---|---|
| Production | manual run (approve) | `https://eshglobal.org` | `.github/workflows/deploy-cpanel.yml` |
| UAT | push to `main` (automatic) | `https://uat.eshglobal.org` | `.github/workflows/deploy-cpanel-uat.yml` |

Both build `vendor/` on the runner, mirror the tree over SFTP (never `.env`, uploads, `public/storage`
or `bootstrap/cache`), prune files removed from the repo, then run
`migrate`/`storage:link`/caches/`esh:health` over SSH. **Pushing `main` deploys UAT only**; production is
a manual run from Actions after you have tested UAT. Secrets and variables are per-repository, so
they must be created again in the new account (production `CPANEL_HOST/USER/PASSWORD`, UAT
`UAT_CPANEL_HOST/USER/PASSWORD` — nothing else). The full runbook — transfer checklist, secrets, domain/PHP/database
setup, the cPanel Git pipeline (`.cpanel.yml`), the release-zip fallback (`deploy/build-release.ps1`),
the smoke-test matrix and rollback — is in **[DEPLOYMENT.md](DEPLOYMENT.md)**.

Environment templates:

| File | Use |
|---|---|
| `.env.example` | local development |
| `.env.uat.example` | UAT at `uat.eshglobal.org` (`APP_ENV=production`, `APP_DEBUG=false`, `SEED_DEMO_ACCOUNTS=true`) |
| `.env.production.example` | production at `eshglobal.org` (`SEED_DEMO_ACCOUNTS=false`, `LOG_LEVEL=warning`) |

These commands support the deploy:

```bash
php artisan esh:health                       # PHP/extensions, APP_KEY/URL, mailer, DB, pending migrations,
                                             # writable storage, public/storage link, upload limits, admin + demo users
php artisan esh:mail-test you@example.com    # send one test email to prove this environment's SMTP works
php artisan esh:create-admin --role=ceo      # first real CEO/admin login on a fresh production database
php artisan esh:create-super-admin ...       # optional hidden maintenance account — see MAINTENANCE-USER.md
```

A **hidden maintenance account** has full CEO access but is filtered out of every user list, dropdown,
contact picker, broadcast/notification fan-out and count, and never receives password-reset mail. Create
it once per environment with `php artisan esh:create-super-admin` (details and the direct-SQL alternative
in **[MAINTENANCE-USER.md](MAINTENANCE-USER.md)**).

Notes: the UI loads Tailwind/Font Awesome from CDNs (no `npm run production` needed on the server), and
`route:cache`/`optimize` must not be used because `routes/web.php` contains closures — cache with
`php artisan config:cache` and `php artisan view:cache`.

## Feature change log

### v1 wrap-up — bank details, multi-class payments, testimonials from messages (30 October 2026)

| Area | What changed |
|---|---|
| **School bank accounts** | Administrators can now add, edit, switch off and delete the accounts parents transfer into (previously CEO-only). Verification of payments stays CEO-only. The parent payment page lists bank name, account name and number with a copy button |
| **Multi-class payments** | The parent payment form ticks every class a transfer covered (plus a *general* row) and records **one installment per class in a single submission**, each with its own amount and optional total fee. Rows are filtered to the chosen child, typing an amount ticks its row, and a live total is shown. One transfer reference may now appear on several rows (migration `2026_10_30_000006` turns the unique index into a lookup index); the receipt is shared by the batch and the CEO gets one notification summarising it |
| **Testimonials through messaging** | The compose form has a **Message type** dropdown (*General message* / *Testimonial — may be published*) for parents, tutors and learners. Choosing testimonial files it in the review queue, notifies administrators and the CEO, and keeps one testimonial per member |
| **Review and polish** | Administrators **and** the CEO open *Communication → Testimonials*, can **edit the wording** (an approved quote stays approved), toggle photo publication, approve or reject with a reason |
| **Landing slider** | Approved testimonials publish **three at a time** in an auto-rotating slider (dots + previous/next, pauses on hover) instead of one long grid |
| **Quotation** | `CLIENT_QUOTATION 2.md` now shows subtotal ₦260,000 + VAT ₦40,000 = **₦300,000**, matching the 2 × ₦150,000 milestones, and lists every client-requested addition as delivered at no extra cost |

Migrations added: `2026_10_30_000006_allow_shared_references_on_payments_table`, `2026_10_30_000007_add_type_to_messages_table`.
Tests: `PaymentBatchTest` (9) and five new cases in `TestimonialTest` — full suite **217 passing**.

### LMS palette pass — sun semantics, calm surfaces (30 October 2026)

The public site matched the flyer; the signed-in app was still grey-dominant (2,192 `gray-*` utilities
versus 5 uses of the brand sun). This pass gives the LMS the same colour logic without touching layout.

| Area | Before | After |
|---|---|---|
| Attention colour | Tailwind `yellow-*` (100 uses) **and** `amber-*` (25 uses) doing the same job | One **sun** scale: `sun` `#F7C94B`, `sun-light` `#FFF7DB`, `sun-edge` `#F4DFA0`, `sun-ink` `#8A6100` — defined in the app, plain and landing Tailwind configs |
| Small meta text | `text-gray-400` (83 uses, ≈2.8:1 on white) | `text-gray-500` (4.8:1) |
| App canvas | `bg-gray-50` | **`#F5F8FC`** pale blue-grey, so white cards separate |
| Table headers | `bg-gray-50` + gray labels (23 tables) | **`#EAF2FB`** band with **navy semibold** labels (164 cells) |
| Stat cards | White card + `border-l-4` in blue/yellow/orange (a rainbow per screen) | Shared **`partials/stat-card`**: neutral card, navy value, one coloured chip — **blue = people, navy = academics, orange = finance, sun = attention, green = success** (23 cards: admin/CEO, tutor, learner) |
| Sidebar / top bar | Thick `border-b-2 border-orange-500`, orange active bar, orange badges, `text-orange-200` role chip | `border-white/10` hairline, **sun** active bar + icon, **sun** unread badges (navy text), sun role chip; gradient simplified to `#0A2550 → #0D3070` |
| Unread counters | Orange (`messages/index`, parent dashboard, top bar) | **Sun** — orange is now reserved for "do this" (buttons, delete/reject) |

Accessibility after the pass: white on navy 13.6:1 · navy on sun 9.6:1 · sun-ink on sun-light 5.4:1 ·
navy labels on the table band 12.9:1 · gray-500 meta 4.8:1 · navy text on orange buttons ≈5:1.
Zero `yellow-*`/`amber-*`/`text-gray-400` utilities remain in the views.

The sweeps live in `tools/` as re-runnable records: `sun-sweep.php`, `table-sweep.php`,
`badge-sweep.php`, `dashboard-cards.php` (each reports zero changes once applied).

Also fixed in the same batch: commit `8f0e901` had a bad find/replace that wrote `trangray` instead of
`translate` in three places, which silently disabled the **mobile off-canvas sidebar**. Restored in
`ecf5960`.

### Brand palette alignment — navy / blue / orange, no red (30 October 2026)

The interface now matches the school flyer: **navy structure, royal-blue actions, orange for actions/warnings,
sun-yellow highlights and no red anywhere**. Legacy purple auth screens and the scattered purple/pink/teal/indigo/
slate accents were removed too.

| Token | Hex | Used for |
|------|-----|----------|
| Navy | `#0A2550` | Sidebar, top bar, auth/plain background, footer |
| Blue | `#1D4ED8` | Structure, links, primary non-destructive buttons |
| Orange | `#F26B21` | Primary actions, warnings, attention, delete/reject |
| Sun | `#F7C94B` | Highlights, "Today" chips (always with navy text) |
| Paper | white / `#F8FAFC` | Cards and content |

Rules applied across the app:

- **Red removed entirely** (152 class usages + 8 model badge maps): validation and flash errors are orange with a
  warning icon, destructive actions are orange, declined/rejected/no-show statuses are orange tints, unread
  counters are orange, "Today" is sun-yellow with navy text, required asterisks are orange.
- **Purple auth gradients replaced** with navy → blue (login, register, forgot and reset), and auth cards carry an
  orange top border.
- **Non-brand families removed** from views and models (purple/pink/teal/indigo/violet/fuchsia/rose/cyan/sky →
  blue or orange; emerald/lime → green; slate → gray). Green is kept only for success/verified.
- **Contrast fixes**: solid orange buttons now use navy labels (≈5:1, previously white-on-orange 2.8:1),
  orange text on white uses `orange-700` (5.2:1), and orange-500 icons are darkened.
- **Tokens** `esh.navy / esh.blue / esh.orange / esh.sun / esh.ink` are defined in the app, plain and landing
  Tailwind setup for future components.
- The client asked to **keep the existing `logo.jpg`** (30 October 2026), so the mark is untouched; it is shown on
  a white chip in the footer so it stays legible on navy.

### Flyer look and feel — typography and landing rebuild (Pass B, 30 October 2026)

The public landing page now carries the flyer's visual language instead of a plain hero.

| Element | Implementation |
|---|---|
| Type | **Poppins** 600–800 headings + **Inter** body everywhere; **Caveat** script used only on the marketing page ("Bigger goals, brighter future", "Come and see us"). App headings inherit Poppins through a single `h1–h3` rule in `layouts/app.blade.php`; utility classes still win |
| Top bar | **Navy sticky header** (`#0A2550/95` + blur, `white/10` underline) with a white chip behind `logo.jpg` (the JPG has a white background), white wordmark with a **sun** second line, blue-100 links that turn sun on hover, a sun "Book assessment" link, a white-outline Log in and the orange Register CTA. The mobile sheet stays white for legibility and its open-state highlight is `white/14` |
| Hero | Cream `#FBFAF7` paper, **navy corner shape + navy bottom wave** (inline SVG), sun-tinted glow, **subject strip** (Math \| English \| Sciences \| SATs/11+ \| Coding & AI \| Chess \| Creative Writing), navy headline with an **orange→sun brush underline**, navy / orange / outline CTA set |
| Slider panel | **Navy gradient block** (`#0A2550 → #0C2B5D → #123A73`) with a `white/10` border, 2rem radius, `shadow-2xl`, a soft orange glow and an **overflow-clipped inner layer** so the sun sticky note can still overhang the corner. Chosen over white/grey because the client wanted less white on the page |
| Illustration | Hand-built inline SVG, recoloured for the dark panel: **sun** cap and "EXCEL" spine, **orange** "ACHIEVE", **light blue** `#93C5FD` "GROW", **white** "LEARN", lighter monitor frame, `#94A3B8` stand, sun sparkle and orange accent — every label uses navy text (5:1–9:1 contrast) |
| Sections | Pale-blue `#EAF2FB` programme cards with circular icon chips, sun/orange section eyebrows, quote cards on cream paper with initial-avatar fallbacks, navy About band with sun check marks, navy footer band carrying the flyer's socials (`@esh_tutoring` on Instagram, X, TikTok, Facebook) and the contact block |
| Boundaries | The slider content, testimonial data, booking form, nav anchors and every asserted string are unchanged — this is styling only, so the landing tests still guard the structure |

Still outstanding (client-requested, v2): admin-managed slider slides, admin-managed social links, and a
vector logo asset.

### Learning goals shared with learners (note 7 follow-up, 30 October 2026)

The meeting note asked for goals to reach learners as well as parents and tutors. That last audience is now
covered.

| Area | Change | Where |
|------|--------|-------|
| Share switch | **Share with** now has **Parent / Learner / Tutor**; the tiles and filter include a learner count | `Admin\LearningGoalController`, `admin/learning-goals/index.blade.php` |
| Learner view | A **"Goals shared with you"** panel on the learner dashboard (latest 3) and **My Learning → Learning Goals** with the full list, targets, class/term, tutor and attachment downloads | `Learner\LearningGoalController`, `learner/learning-goals/index.blade.php`, learner dashboard |
| Scope | A learner sees goals shared with learners that are **theirs** or **class-wide for a class they attend**; anything else returns 403, including the attachment URL | `LearningGoal::scopeVisibleToLearner()` / `isVisibleToLearner()` |
| Data | `learning_goals.shared_with_learner` | migration `2026_10_30_000005_add_learner_sharing_to_learning_goals` |
| Tests | `tests/Feature/LearnerLearningGoalsTest.php` (7) — dashboard/page visibility, hidden goals, class-wide isolation, download guards, admin switch, role access | `tests/Feature` |

### Member profiles and CEO-approved testimonials (note 10, 30 October 2026)

Members can now manage their own profile and share a testimonial; the CEO decides what appears on the
public website.

| Area | Change | Where |
|------|--------|-------|
| My profile | Every role gets **My profile** (top bar): update name and email and upload/replace/remove a profile photo (JPG/PNG/WebP ≤ 4 MB); learners may keep no email; username is read-only | `ProfileController`, `profile/edit.blade.php` |
| Testimonial | Members write up to 1,000 characters and tick **"Publish my profile photo"** (requires a photo); one testimonial per member; any edit returns it to the CEO for approval | `ProfileController@storeTestimonial`, `testimonials` table |
| CEO review | **Communication → Testimonials** (CEO only): pending/approved/rejected tabs, approve for the website, or reject with a reason the member sees | `Admin\TestimonialController`, `admin/testimonials/index.blade.php` |
| Landing page | Approved testimonials render live (name, role, quote, photo only when allowed); sample quotes remain the fallback when nothing is approved | `routes/web.php` (`/`), `landing.blade.php` |
| Tests | `tests/Feature/ProfileTest.php` (7) and `TestimonialTest.php` (7) — photo replace/remove, unique email, learner without email, submission/consent, CEO approval/rejection, photo publishing and landing visibility | `tests/Feature` |

### Weekly affirmations written by the school (note 8, 30 October 2026)

The affirmation card is no longer only a built-in rotation: the school writes the weekly message.

| Area | Change | Where |
|------|--------|-------|
| Compose | **Communication → Affirmations** (admin + CEO): publish a message (max 500 characters); new posts take over immediately | `Admin\AffirmationController`, `admin/affirmations/index.blade.php` |
| History | Older posts stay listed with author/date; switch one off (falls back to the previous active post or the rotation) or delete it | same |
| Dashboards | A colourful blue/orange card shows the latest active post on **every** dashboard — including the learner dashboard, which previously had no affirmation | `partials/affirmation.blade.php`, dashboards |
| Tests | `tests/Feature/AffirmationTest.php` (7) — publish, newest wins, switch-off fallback, permissions, validation, delete, render | `tests/Feature` |

### Class completion tick and an honest monthly tutor report (note 5, 30 October 2026)

The monthly report used to count *scheduled* sessions, so a cancelled class still looked like teaching. The
school can now tick the classes that actually took place, and the report counts those.

| Area | Change | Where |
|------|--------|-------|
| Completion tick | Administrators and the CEO can **mark a past session completed** from the Schedules list or the session page; the guard still refuses sessions whose end time has not passed | `Admin\ScheduleController@markCompleted`, `admin/schedules/*` |
| Audit | `class_schedules.completed_at` / `completed_by` plus an `activity_logs` entry record who ticked it and when; the session page shows it | migration `2026_10_30_000002_add_completion_columns_to_class_schedules_table` |
| Reopen | Undo a tick (**Reopen**) — the session returns to *scheduled* and stops counting | `Admin\ScheduleController@reopen` |
| Report | **Classes per tutor** now counts completed classes, shows "X completed of Y scheduled" per tutor, and the six-month trend counts completions; a link jumps to Schedules | `Admin\DashboardController`, `admin/dashboard.blade.php` |
| Tutor side | The tutor's own "Complete" action now also stamps `completed_at` / `completed_by` and logs the change | `Tutor\ScheduleController@markCompleted` |
| Tests | `tests/Feature/ScheduleCompletionTest.php` (7) — admin/CEO tick, future-session guard, tutor tick, reopen, permissions, monthly completed counts and month isolation | `tests/Feature` |

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
| Payment methods | **Bank transfer** on the parent form (the school is online-only, so cash is no longer offered; older cash records still verify against the cash book), plus **card** disabled with "coming soon". A transfer must pick the school account it went into and keeps a `paid_into` snapshot | `Payment::METHOD_*`, `parent/payments/create.blade.php` |
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
| Branding | Navy / royal blue / orange palette with **sun-yellow highlights** (no red anywhere; orange carries actions, warnings and attention) | all Blade views |
| Website | Public landing page with hero slider, live programmes, how-it-works, testimonials, about/contact and registration CTA | `routes/web.php` (`/`), `landing.blade.php` |
| Docs & tests | In-app user guide updated for every change; `tests/Feature/ClientAdditionsTest.php` | `help/user-guide.blade.php`, `PLAN_CLIENT_ADDITIONS.md` |


### Deployment kit (`eshglobal.org`)
- **`.github/workflows/deploy-cpanel.yml`** — production pipeline on push to `main`: Composer install with
  `--no-dev`, SFTP mirror (`lftp`) to `~/public_html/`, pruning of files removed from the repo, then
  `migrate --force --seed`, `storage:link`, `config:cache`, `view:cache` and `esh:health` over SSH. Never
  uploads `.env`, `storage/` uploads or `public/storage`.
- **`.github/workflows/deploy-cpanel-uat.yml`** — the same pipeline for UAT, on every push to `main`
  (`~/public_html/uat.eshglobal.org/`, `UAT_CPANEL_*` secrets).
- **`.htaccess` (repository root)** — forwards every request into `public/` and returns 403 for `.env`,
  `artisan`, `composer.*`, `*.log`, `*.sql`, `*.sh`, `*.yml`, so the app can live inside the document root.
- **`.user.ini`** — 100 MB upload limits, 256 MB memory and `session.upload_progress.enabled=0`.
- **`DEPLOYMENT.md`** — cPanel runbook: repository transfer, secrets, domain/PHP/MySQL setup, GitHub Actions
  deploy for production and UAT, cPanel Git pipeline, release zip, smoke-test matrix, rollback and troubleshooting.
- **`.cpanel.yml`** — fallback cPanel *Git™ Version Control* tasks (composer install, `migrate --force --seed`,
  `storage:link`, config/view caches, `esh:health`) that never overwrite `.env` or `storage/` uploads.
- **`.env.uat.example` / `.env.production.example`** — environment templates for `uat.eshglobal.org` and
  `eshglobal.org` (secure cookies, demo seeding only on UAT/testing).
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
