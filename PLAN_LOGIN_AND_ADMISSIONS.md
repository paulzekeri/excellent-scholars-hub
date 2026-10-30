# Notes 12 & 13 — username login for children, and admissions (video-call assessment)

Status: **design only, nothing implemented.** Source: meeting-note items **12** and **13** at the
bottom of `CLIENT_QUOTATION 2.md`.

## Implementation status (29 October 2026)

The first slice is built, migrated and green (`php artisan test` → **102 passing**):

| Slice | State |
|---|---|
| Migration: `users.username`, nullable `email`, `must_change_password`, `password_changed_at` | ✅ `2026_10_29_000001_add_username_to_users_table` |
| Login with an email **or** a username | ✅ `App\Auth\EmailOrUsernameUserProvider` + `esh-eloquent` provider, `LoginController`, login view |
| Registration: required username, optional child email, mandatory guardian email, staff-email guard | ✅ `RegisterController`, `auth/register.blade.php` |
| Admin provisions a parent (username/email/password) and adds a child from the parent screen | ✅ `Admin\UserController@store` / `addChild`, users create/edit views |
| Credential mail + UAT/log fallback (`provisioned_credentials` flash) | ✅ `AccountProvisionedMail` |
| Forced password change at first sign-in | ✅ `EnsurePasswordIsChanged`, `ChangePasswordController`, `auth/passwords/change` |
| Reset by username → guardian link (per-user token, emailed to the guardian) | ✅ `LearnerPasswordResetService`, `LearnerPasswordResetMail`, `ForgotPasswordController` |
| Parent-initiated reset from the child's card | ✅ `parent.children.password-reset`, "Children & logins" panel on the parent dashboard |
| Admin fallback for a child with no email | ✅ `Admin\UserController::sendGuardianResetLink()` — emails the guardian, or flashes the link on UAT |
| `esh:backfill-usernames` + `esh:audit-guardian-links` | ✅ `app/Console/Commands` |
| Note 13 **P1** — enquiry capture, pipeline list, detail + timeline, dashboard counters | ✅ `Admin\EnquiryController`, `admin/enquiries/*`, `Enquiry` / `EnquiryNote`, sidebar **People → Admissions** |
| Note 13 **P2** — public booking, school-created Meet/Zoom link, tutor invite, guardian email + WhatsApp, three-party join | ✅ `PublicBookingController`, `Admin\AssessmentController`, `Tutor\AssessmentController`, `AssessmentSession`, `AssessmentScheduledMail` |
| Note 13 **P3–P5** — result recording, conversion wizard, reminders/status page | ⏳ next |

Tests added: `tests/Feature/UsernameLoginTest.php` (12), `AccountProvisioningTest.php` (11),
`GuardianPasswordResetTest.php` (12), `MaintenanceCommandsTest.php` (8), `AdmissionsTest.php` (12) and
`AssessmentBookingTest.php` (13); `AdminUserParentLinkTest` rewritten for the mandatory-guardian rule and
`PasswordResetTest` updated for the new login field and the neutral reset response. Full suite:
**147 passing**.

Implementation notes that differ from the sketch above:

- The forgot-password form now answers **neutrally** for unknown identifiers (it used to show a
  validation error). `PasswordResetTest::test_an_unknown_identifier_gets_the_same_neutral_response`
  records the deliberate change.
- The reset page keeps Laravel's `guest` middleware, so the guardian mail tells the parent to open the
  link on the child's device or in a private window when they are signed in.
- `Admin → Users → Reset password` on a child without email now emails the guardian (or flashes a
  one-time link when the mailer cannot deliver) instead of showing an "unavailable" message.

## Client decisions confirmed so far

| Date | Decision |
|---|---|
| 29 Oct 2026 | **Note 11** (online payment gateway) is **deferred** — out of scope; `PaystackService` stays a stub. |
| 29 Oct 2026 | **Note 12: the guardian/parent email is mandatory.** There is no "child account without a responsible adult" path — the guardian address is the reset delivery channel, so it must always exist. |
| 29 Oct 2026 | **Note 12: usernames are unique and linked to the guardian's email.** The username is a credential; the guardian record it hangs off is what makes a reset possible and traceable. |
| 29 Oct 2026 | **Admin creates a parent** with **username, email and password**; the password is emailed to that address and the parent is forced to change it at first login. |
| 29 Oct 2026 | **Admin adds a child to a parent** with **username, password and an optional email**; the child's details go to the guardian's account and the child is forced to change the password at first login. |
| 29 Oct 2026 | **Delivery rule:** use the child's **own email when one exists**, otherwise the guardian's email — for account details and for password resets alike. The child's email stays **optional on the register page**, never removed. |
| 29 Oct 2026 | Notes 12 and 13 are **design-first** (this document), then implemented in the order in §3. |

Both notes are "needs careful thought" items because they change things every other feature sits on:

| | Note 12 (username login) | Note 13 (admissions) |
|---|---|---|
| Touches | authentication for **every** role: login, registration, password reset, admin user screens, mail, seeders, UAT scripts | an entirely new domain that exists **before** a learner/parent record does |
| Data | `users.email` currently NOT NULL + unique → schema change + backfill of live accounts | new tables + a state machine; no existing table is modified |
| Risk if rushed | lock-outs, account-takeover via a shared email, resets going to the wrong person | half-built pipeline the school abandons and goes back to WhatsApp |

They also depend on each other: the last step of note 13 (**convert a prospect into a learner**)
is exactly the "child account without an email" case note 12 defines. **Note 12's schema must land
before note 13's conversion wizard.**

---

## 1. Note 12 — children log in with a username

### 1.1 What the client asked

> "children usually don't have email so can we have parents email / username so children can
> register with parent mail but can use their username to login in. they can use their username to
> reset password in production"

Three separate requirements hide in that sentence:

1. **Registration** — a child may give the *parent's* email instead of their own.
2. **Login** — the child signs in with a **username** (+ password).
3. **Password reset** — the child can start a reset with their **username**, and it still has to
   reach a human who can open the link (the guardian), because the child has no mailbox.

**Clarified by the client (29 Oct 2026):** the guardian email is **not optional**. It is a required
field on every child account, and the username is stored **against that guardian** — so a username
always resolves to exactly one child and one reachable guardian address. §1.4.3a turns this into a
hard invariant that every write path enforces.

### 1.2 Where the code stands today (verified)

| Area | Current behaviour | File |
|---|---|---|
| Users table | `email` `NOT NULL` + `unique`; no `username` column | `database/migrations/2014_10_12_000000_create_users_table.php` |
| Login | email + password only (`AuthenticatesUsers`) | `app/Http/Controllers/Auth/LoginController.php` |
| Login form | `name="email"`, `type="email"` | `resources/views/auth/login.blade.php` L18-21 |
| Registration | child's own email **required** + unique; parent name/email optional-but-required-for-learners; parent account auto-created with a random password and mailed (`ParentWelcomeMail`) | `app/Http/Controllers/Auth/RegisterController.php` L40-125 |
| Forgot password | email only; 60-minute single-use token; generic response | `ForgotPasswordController`, `ResetPasswordController`, `resources/views/auth/passwords/*` |
| Reset page | hidden `token` + **readonly email** field, posts to `password.update` | `resources/views/auth/passwords/reset.blade.php` L34-40 |
| Admin users | email required + unique on create/update | `app/Http/Controllers/Admin/UserController.php` L34-35, L84-89 |
| Admin reset fallback | mints a broker token and flashes the URL; **assumes non-null email** | `Admin\UserController::sendPasswordReset()` L124-177 |
| Token store | `password_resets(email index, token, created_at)`, token hashed by the broker | `database/migrations/2014_10_12_100000_create_password_resets_table.php` |
| Not-null assumptions | plenty of places display or mail `$user->email` (Users table, reset messages, welcome mail) | `admin/users/*`, `Admin\UserController`, `Mail\ParentWelcomeMail` |
| Tests today | `tests/Feature/PasswordResetTest.php` (11) assume email-based reset; nearly every feature test signs in with an email | `tests/Feature` |

**The hard constraint:** two siblings cannot share one email row, because `users.email` is unique.
So "register with the parent's mail" can only mean **the child's `users.email` stays NULL** and the
parent's address lives on the `parents` row they are linked to (`learners.parent_id`).

### 1.3 Options considered

| Option | How it works | Pros | Cons | Verdict |
|---|---|---|---|---|
| **A. Nullable email + unique username** | `users.email` nullable; `users.username` unique; login/reset accept either; guardian email used for delivery | Honest data model; siblings share the parent; no fake addresses; works for staff too | Touches auth for all roles; needs backfill for existing learners; makes a guardian mandatory for every child (client-confirmed 29 Oct) | **Recommended** |
| B. Synthetic addresses | store `tolu.okafor@learners.esh.internal` as the email | No schema surprises; broker works untouched | Fake addresses leak into admin screens, mail, exports; reset mail bounces; confusing for the school | Reject |
| C. Parent-only accounts | no child login at all; learners work inside the parent portal | Simplest, most secure | Client explicitly wants children to log in themselves | Reject (keep as fallback if they change their mind) |
| D. Plus-addressing | children register as `parent+child@gmail.com` | No schema change | Gmail-only habit, not universal, parents will not understand it; unverifiable | Reject |

**Decision A**, with the small safety addition in §1.4.3 (a child cannot silently attach itself to a
stranger's email).

### 1.4 Recommended design

#### 1.4.1 Schema — one migration, additive only

`database/migrations/2026_10_29_000001_add_username_to_users_table.php`

```php
// up()
Schema::table('users', function (Blueprint $table) {
    $table->string('username', 30)->nullable()->unique()->after('name');
    // School-provisioned accounts must set their own password before using the app.
    $table->boolean('must_change_password')->default(false)->after('password');
    $table->timestamp('password_changed_at')->nullable()->after('must_change_password');
});

// email must become nullable for learners without a mailbox.
// doctrine/dbal is not installed, so use the same driver-aware raw ALTER as
// 2026_10_27_000002_make_max_students_nullable_on_classes_table.
```

`must_change_password` is the flag behind §1.4.3c; `password_changed_at` is only for the audit trail
("when did this person last choose their own password") and is not used for expiry.

- MySQL/MariaDB: `ALTER TABLE users MODIFY email VARCHAR(255) NULL`.
- The existing `unique` index on `email` stays — MySQL allows many NULLs in a unique index, so any
  number of children can have no email.
- **No data is rewritten by the migration.** Backfilling usernames is a separate, reviewable step
  (§1.4.6) because a username is a *credential* the school will hand to a parent.

#### 1.4.2 Login with either email or username

Keep Laravel's own login machinery; only the *lookup* changes.

1. `app/Auth/EmailOrUsernameUserProvider.php` — extends `Illuminate\Auth\EloquentUserProvider`,
   overrides `retrieveByCredentials()`:
   - if the credentials contain `login`, apply
     `where('email', $login)->orWhere('username', $login)` (trimmed, lowercased);
   - every other non-password key keeps the parent behaviour (`remember` excluded).
2. Register the driver in `app/Providers/AuthServiceProvider::boot()`:
   `Auth::provider('esh-eloquent', fn ($app, $config) => new EmailOrUsernameUserProvider($app['hash'], $config['model']));`
3. `config/auth.php` → `providers.users.driver = 'esh-eloquent'` (single line; everything else,
   including the password broker, keeps using the same provider).
4. `LoginController`:
   - `public function username() { return 'login'; }`
   - `credentials()` → `['login' => trim($request->input('login')), 'password' => ...]`
   - keep the built-in throttle (it already keys on `username()` + IP → now "login string + IP").
5. `resources/views/auth/login.blade.php`: label **"Email or username"**, `type="text"`,
   `name="login"`, `autocomplete="username"`, `old('login')`. Error text stays the neutral
   "These credentials do not match our records."
6. `Admin\UserController` and the seeder/UAT scripts keep working: they still set `email`, and
   `LoginController` accepts an email in the same field, so **no existing user is locked out**.

Why a provider and not a manual `Auth::attempt()`: login, "remember me", session regeneration,
`is_active` checks and the throttle all stay standard, and the change is one class + two lines of
config.

#### 1.4.3 Registration (child without a mailbox)

`auth/register.blade.php` for the **learner** role only:

- Email becomes optional: label "Email address (leave blank if your child has no email)".
- New required field: **Username** (`alpha_dash`, 3–30 chars, unique, not reserved — §1.4.7),
  with a hint "This is what your child will sign in with".
- Parent/Guardian name **and email are required** (client-confirmed, 29 Oct 2026) — they are the
  delivery channel and the record the username is tied to.
- When the child *does* have an email, everything works exactly as today (the child receives the account
  mail; the guardian is still linked and recorded).
- If the guardian account is **created during registration**, its password is system-generated and
  emailed, so that guardian account is also flagged `must_change_password` — the child picked their own
  password at registration and is not flagged.

`RegisterController` validation changes:

```php
'email'    => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
'username' => ['nullable', 'string', 'min:3', 'max:30', 'alpha_dash',
               'not_in:'.implode(',', User::RESERVED_USERNAMES), Rule::unique('users', 'username')],
```

plus: for the learner role **`username` is required when `email` is empty**, and the role rule
stays `exists:roles,id`.

Safety rules for the parent's address (this closes a hole that exists today, where any stranger's
email can be turned into a parent account):

- If the address already belongs to a **parent** → link the child as a sibling, notify the parent.
- If it already belongs to a *staff* or *learner* account → validation error ("That email belongs to
  another account, please use the parent's own email").
- If it is new → create the parent account as today (`ParentWelcomeMail` now also includes the
  child's **username**, so the family has both logins in one mail).
- **Optional hardening (recommended for production, later phase):** the new guardian must click a
  one-time confirmation link before `learners.parent_id` is set. The guardian email itself is already
  **mandatory** (client-confirmed, see §1.4.3a); this only adds proof that the adult consented to the
  link. Adds a `parent_link_confirmed_at` column — see open decision D6.

`Admin\UserController::store()/update()` mirror the same rules: when the selected role is `learner`,
`email` is nullable but `username` is required + unique, and (per §1.4.3a) a guardian with an email is
required; for every other role nothing changes.

#### 1.4.3a Invariant G — no learner without a reachable guardian (client-confirmed)

> **Every learner account must be linked to a `parents` row whose `users.email` is not null.**

This is the rule that makes "reset by username" possible, so it is enforced on **every write path**,
not just in the registration form:

| Write path | Enforcement |
|---|---|
| Learner self-registration | `parent_email` required + valid; if the address is new, a parent account is created with it |
| Admin **create learner** | a parent is now **required** (today it is optional — `test_admin_can_create_a_learner_without_a_parent` will be replaced). Add an inline "New parent" option (name + email + phone) so the admin does not have to leave the screen |
| Admin **edit learner** | cannot clear the learner's own email to empty **and** remove the parent in the same/any save; the parent selector is required when the learner has no own email |
| Note 13 conversion | `guardian_email` is required before the wizard will create the child (see §2.5) |
| Direct model writes (seeders/tests) | not blocked at the DB level — a cross-table rule cannot be a simple constraint — so a **dry-run audit command** reports violations |

`php artisan esh:audit-guardian-links` (dry-run, prints + exits non-zero when rows are found) lists
learners with **no parent** or a parent **without an email**, so the school can fix the handful of
legacy/orphan accounts after the migration. Until they are fixed, those specific children keep the
administrator-generated reset link (the existing `Users → Reset password` button), which is the only
sensible fallback and is not the normal path.

**Username ⇄ guardian linkage** is not a copied column (it would drift): the single source of truth is
`learners.parent_id → parents.user_id → users.email`. To make the link visible to the people who need
it:

- **Admin → Users** shows the username **with the guardian's email** underneath, and the parent/child
  relationship is already visible in the Users table.
- **Parent dashboard** gains a "Children & logins" strip listing each child's **username** and a
  **Reset password** action that emails the parent the reset link directly (the parent is already
  authenticated, so no address has to be typed and no stranger can trigger it — see §1.4.4).
- The welcome mail to a new guardian includes every child's username.

#### 1.4.3b School-provisioned accounts (admin → parent, admin → child)

Two screens, both replacing credentials that are currently made up on WhatsApp.

**A. Admin creates a parent — `Admin → Users → New User`, role Parent**

| Field | Rule |
|---|---|
| Name | required |
| **Username** | required, unique, `alpha_dash` 3–30 (§1.4.7) — the parent can sign in with it too |
| **Email** | required, unique |
| **Password** | required, min 8 — the admin types it **or** clicks **Generate** (recommended) |
| Phone | optional |

On save: `users` + `parents` rows are created, `must_change_password = true`, and
`AccountProvisionedMail` emails the username and the password to that address ("sign in and you will be
asked to choose your own password"). The password is never shown again in the UI.

**B. Admin adds a child to a parent — an "Add child" button on the parent's edit screen**
(the same fields appear on New User when the role is Learner)

| Field | Rule |
|---|---|
| Child name | required |
| **Username** | required, unique — globally, so it can never collide with a parent's or a sibling's |
| **Password** | required, min 8 (or Generate) — emailed, and the child must change it |
| **Email** | **optional** — when supplied it becomes the child's primary address |
| Parent | fixed to the parent you are editing (or selected on New User) → creates `learners.parent_id` |

On save: the learner + user are created, `must_change_password = true`, and the mail goes to the
**guardian** (per the client: "the child's details is sent to the father's account") **and** to the
child's own email when one was supplied. If the installation has no working mailer (UAT uses `log`),
the credentials are flashed to the administrator to hand over privately — the same pattern as the
existing password-reset fallback (`Admin\UserController::mailCanDeliver()`).

Because an admin-chosen password is known to the administrator, it is treated as **temporary**: the
forced change in §1.4.3c guarantees the account does not stay on a password the school knows.

**Delivery helper:** `User::deliveryEmail()` returns `$this->email ?: optional($this->learner->parent->user)->email`.
Provisioning mail and reset routing both call it, so the client's "own email first, guardian otherwise"
rule lives in exactly one place instead of being re-implemented per screen.

`AccountProvisionedMail` also **replaces the current `ParentWelcomeMail`** on the self-registration
path, so the project ends up with exactly one "here are the credentials" template for parents and
children instead of two divergent ones.

#### 1.4.3c Forced password change at first sign-in

1. `users.must_change_password` (§1.4.1), default false. Set to `true` whenever **someone other than the
   account owner** set the password: an admin creates a parent or child (§1.4.3b), a guardian account is
   auto-created during a child's registration (system-generated password), and both accounts created by
   the note 13 conversion wizard. A user who chose their own password — self-registration or any reset
   flow — is never flagged.
2. `app/Http/Middleware/EnsurePasswordIsChanged.php`, appended to the `web` middleware group in
   `app/Http/Kernel.php`, so it covers every authenticated page without touching individual route
   groups:

   ```php
   if (auth()->check() && auth()->user()->must_change_password
       && ! $request->routeIs('password.change', 'password.change.update', 'logout')) {
       return redirect()->route('password.change');
   }
   ```

3. `GET/POST /password/change` (`ChangePasswordController`, names `password.change` /
   `password.change.update`), auth-only:
   - a **full-page interstitial** in a minimal layout (login styling, no sidebar) — it cannot be
     dismissed like a modal and there is nothing else to click;
   - fields: **current password** (`current_password`), **new password** (`password`) and **confirm**
     (`password_confirmation`); rules `current_password` → `required|current_password`, `password` →
     `required|min:8|confirmed|different:current_password` (`current_password` is a built-in Laravel 8
     rule, so no custom validator is needed);
   - on success: `Hash::make`, `must_change_password = false`, `password_changed_at = now()`,
     `remember_token` rotated, session kept, flash "Password updated" and redirect to the role
     dashboard (`isAdministrator()` / `isTutor()` / `isParent()` / `isLearner()` — same mapping as
     `LoginController::authenticated()`).
4. A deep link pasted past the interstitial also redirects back — the flag lives on the user row, not
   in the session.
5. Logout stays reachable so an interrupted user is never trapped.
6. Self-service reset and parent-initiated reset already make the user choose a password, so they never
   set the flag; a future admin "set a temporary password" action would.

#### 1.4.4 Password reset by username (the tricky part)

The requirement is "reset by username", but a link still has to reach a human. Design:

1. **Form** (`auth/passwords/email.blade.php`): label "Email or username", single `login` field.
2. `ForgotPasswordController::sendResetLinkEmail()` resolves the identifier with the same provider
   (`retrieveByCredentials(['login' => ...])`) — never reveals whether it matched.
3. **User has an email** → unchanged standard broker (`Password::broker()->sendResetLink(...)`), so
   `tests/Feature/PasswordResetTest.php` stays valid. For a **child with their own email** the link goes
   to the child — that is the client's rule ("own email first"). Parents and staff get it in their own
   inbox, as today.
4. **User has no email (a child) — guardian fallback** → `app/Services/LearnerPasswordResetService`:
   - first asserts **Invariant G** (`learners.parent.user.email` exists). With the client-confirmed
     rule this is always true for accounts created by the app; a legacy orphan is reported to the
     administrator instead of a silent failure;
   - writes a hashed token to `password_resets` under a **per-user synthetic key**
     `noemail:{id}` (the table's `email` column is a plain indexed string, so this is safe and
     keeps siblings independent — two children resetting the same day cannot overwrite each other,
     which a shared parent-email key would do);
   - expires after `config('auth.passwords.users.expire')` (60 min) and is single use;
   - mails the **guardian** (`learners.parent.user.email`, the fallback address used only because the
     child has no mailbox) a `LearnerPasswordResetMail`:
     *"A password reset was requested for {child}. Share this link with them privately"* +
     `route('password.reset', ['token' => $token, 'uid' => $user->id])`;
   - the mail never contains a password, only the one-time link.
5. `Admin\UserController::sendPasswordReset()` uses the same service for users with a NULL email, so
   the "share this link privately" fallback finally works for child accounts — needed only when mail
   is not configured (UAT uses `log`) or for a legacy orphan flagged by the audit command.
6. **Parent-initiated reset** (the zero-typing path for real families):
   `POST /parent/children/{learner}/password-reset` in the parent area — the parent is already signed
   in, so ownership is the only authorisation check (`$learner->parent_id === $parent->id`). It calls
   the same service and mails the link to the parent's own address, then flashes "Reset link sent to
   your email". This is how the school will actually recover a child's password day to day; it also
   satisfies "username linked to the guardian's email" without asking anyone to remember an address.
7. `ResetPasswordController` accepts the optional `uid`:
   - `uid` present → resolve that user, verify the token from the `noemail:{id}` row, update the
     password (`Hash::make`), delete the row, rotate `remember_token`, sign in, redirect by role;
   - otherwise → the untouched Laravel flow (email + token).
   `auth/passwords/reset.blade.php` shows the **username** instead of an email when `uid` is present
   (hidden `uid` input, email input rendered only for the email flow).
8. Rate limiting: `throttle:6,1` on `password.email` and on the parent-initiated reset route (and the
   existing 5/min login throttle), same neutral response on every branch.

#### 1.4.5 Admin screens

- `admin/users/index.blade.php`: new **Username** column ("—" when empty) with the **guardian's email**
  underneath it, and "No email" instead of a blank email cell for the child.
- `admin/users/create.blade.php` / `edit.blade.php`: username field with a **"Suggest"** button
  (`firstname_lastname`, collision-safe hint); email optional + marked *not needed for children* when
  the picked role is learner (same JS toggle pattern already used on the register page).
  The **Parent / Guardian selector becomes required for the learner role** — with an inline
  "New parent" block (name, email, phone) because the school is the one onboarding families and
  should not have to create the parent account first. This replaces the current "optional parent"
  behaviour, which existing tests assert (`test_admin_can_create_a_learner_without_a_parent`,
  `test_admin_can_reassign_and_remove_a_learners_parent` → both will be rewritten to the new rule).
- **Password field on create** (both parent and child provisioning, §1.4.3b): `type="password"` with a
  **Generate** button and a short "the password is emailed and must be changed at first sign-in" note.
  On **edit** there is no password field — the existing **Reset password** action covers that.
- **"Add child" on the parent's edit screen**: a compact inline form (child name, username, password,
  optional email) that creates the learner already linked to that parent — this is the path the school
  will use most, so it must not require the admin to remember to pick a parent on a different screen.
- An **"Awaiting first sign-in"** badge on the Users list for `must_change_password = true`, so the
  school can see who has not yet chosen their own password.
- `Admin\UserController@index` is a plain paginated list today; it needs no query change, but any
  future search box must match `username` as well as name/email.
- Parent area: a **Children & logins** strip on `/parent/dashboard` (or the child detail screen) with
  each child's username and the **Reset password** action from §1.4.4.

#### 1.4.6 Backfill (operational step, not a blind migration)

`app/Console/Commands/BackfillUsernames.php` —
`php artisan esh:backfill-usernames [--apply] [--role=learner]` (follows the existing
`esh:create-admin` / `DeploymentHealthCheck` conventions):

- dry-run by default, prints `id | name | email | proposed username`;
- derives `firstname_lastname` (slugged, no accents/punctuation), appends `2`, `3`, … on collision,
  never overwrites an existing username, never proposes a reserved word;
- `--apply` writes the rows and prints a copy-paste table so the school can send each family its
  child's login (UAT and production both need one run after deploy);
- rerunnable/idempotent.

#### 1.4.7 Username rules

- 3–30 characters, `alpha_dash`, stored lowercase, no `@` (so a username can never look like an email
  and confuse the login field).
- **Required for parents and learners** (both sign in by username); optional but recommended for
  administrators/tutors, who keep email as their primary identifier.
- Reserved list (constant on the `User` model so the validator and the command share it):
  `admin, administrator, ceo, owner, support, help, info, school, esh, root, system, login, logout,
  api, staff, tutor, teacher, learner, student, parent`.
- Uniqueness is case-insensitive for free (MySQL `utf8mb4_unicode_ci`); additionally the validator
  lowercases before saving so the column stays consistent on other drivers.
- **Renaming a username is an administrator action only** (audit trail via `activity_logs`); the
  future self-service profile screen (note 10) shows it read-only until the client asks otherwise.

#### 1.4.8 Security notes that must be in the implementation

1. Never disclose whether an identifier exists (login, forgot-password, admin reset alike).
2. Reset tokens are hashed at rest and single-use; `noemail:` rows are deleted on success *and*
   expired rows pruned like the broker's.
3. A learner can never be attached to a staff/learner email (§1.4.3).
4. Inactive accounts (`is_active = false`) are refused by the standard login path — the custom
   provider must not bypass `AuthenticatesUsers`.
5. No passwords are ever emailed; the guardian receives a *link* or a temporary password only on
   account creation (existing behaviour).
6. `users.email` staying unique means one child cannot be created with a sibling's address; the
   parent address is the only thing shared.
7. **Invariant G:** every learner has a guardian with a non-null email, so a reset link always has a
   legitimate destination. A child's link goes to the **child's own email when one exists**, otherwise
   the guardian's (or is handed over by an administrator) — it is never generated into a void.
8. **A parent may only reset their own children.** The route resolves `learner->parent_id` against the
   signed-in parent and returns 403 otherwise — no enumeration of other families' usernames.
9. A provisioned password is **temporary by construction**: emailed once, never displayed again, and
   the account is locked to the change-password screen until it is replaced.
10. The forced-change screen is **server-side middleware**, not a dismissible modal or a JS redirect,
    so it cannot be skipped by closing a dialog or editing the URL.

#### 1.4.9 Files that must change (impact audit)

| File | Change |
|---|---|
| `database/migrations/2026_10_29_000001_add_username_to_users_table.php` | new (username, nullable email, `must_change_password`, `password_changed_at`) |
| `app/Auth/EmailOrUsernameUserProvider.php` | new |
| `app/Providers/AuthServiceProvider.php` | register the `esh-eloquent` provider driver |
| `config/auth.php` | `providers.users.driver` |
| `app/Http/Controllers/Auth/LoginController.php` | `username()` + `credentials()` |
| `resources/views/auth/login.blade.php` | "Email or username" field |
| `app/Http/Controllers/Auth/RegisterController.php` | nullable email, required username, parent-email guard, username in the welcome mail |
| `resources/views/auth/register.blade.php` | optional email + username field for learners |
| `app/Http/Controllers/Auth/ForgotPasswordController.php` | identifier resolution + learner branch |
| `app/Http/Controllers/Auth/ResetPasswordController.php` | `uid` branch |
| `app/Services/LearnerPasswordResetService.php` | new |
| `app/Mail/LearnerPasswordResetMail.php` + `resources/views/emails/learner-password-reset.blade.php` | new |
| `app/Mail/AccountProvisionedMail.php` + `resources/views/emails/account-provisioned.blade.php` | new — username + temporary password, guardian copy for a child; replaces `ParentWelcomeMail` |
| `app/Http/Middleware/EnsurePasswordIsChanged.php` | new — forced first-login change |
| `app/Http/Kernel.php` | append the middleware to the `web` group |
| `app/Http/Controllers/Auth/ChangePasswordController.php` + `resources/views/auth/passwords/change.blade.php` + a minimal `layouts/plain.blade.php` | new — interstitial screen |
| `app/Models/User.php` | `username`, `must_change_password`, `password_changed_at` in `$fillable`/casts, `RESERVED_USERNAMES`, `deliveryEmail()`, `suggestUsername()` |
| `app/Http/Controllers/Admin/UserController.php` | provisioning (parent + child), password/Generate validation, `must_change_password = true`, add-child handler, duplicate-email guard, reset fallback |
| `resources/views/admin/users/{index,create,edit}.blade.php` | username column/field, password + Generate, "Add child" form on the parent screen, "Awaiting first sign-in" badge |
| `resources/views/auth/passwords/{email,reset}.blade.php` | "email or username" + `uid` |
| `app/Console/Commands/BackfillUsernames.php` | new |
| `app/Console/Commands/AuditGuardianLinks.php` | new — dry-run report for Invariant G violations |
| `routes/web.php` | `password.change` routes; parent-initiated reset in the parent group |
| `app/Http/Controllers/Parent/DashboardController.php` | parent-initiated reset for their own children |
| `resources/views/parent/dashboard.blade.php` (or child detail) | "Children & logins" strip + Reset password button |
| `resources/views/help/user-guide.blade.php` | login/registration/password/first-login sections |
| `README.md` | roles & access rules + change log |
| `tests/Feature/PasswordResetTest.php`, new `tests/Feature/UsernameLoginTest.php` | tests |
| new `tests/Feature/AccountProvisioningTest.php` | admin creates parent/child, mailed credentials, forced change |
| `tests/Feature/AdminUserParentLinkTest.php` | rewritten: a guardian is now required for learners, and cannot be detached |

Non-issues confirmed by inspection: `DatabaseSeeder`, `esh:create-admin` and
`database/scripts/share-admin-password.sql` all key on `email`; email stays required for every
staff/demo account, so they keep working unchanged.

#### 1.4.10 Test plan

`tests/Feature/UsernameLoginTest.php` — login, registration and reset routing:

- a learner with a username + no email can sign in and lands on `/learner/dashboard`;
- a parent/CEO can still sign in with their email in the same field, and a parent can sign in by username;
- wrong password → neutral error + throttle after 5 attempts;
- inactive account is refused even with a valid username;
- registration without a child email **and with a guardian email** creates the learner + parent link,
  stores `email = null`, and mails the guardian the child's username;
- registration **with** a child email sends the welcome mail to the child's address and still links the
  guardian (the email field is optional, never removed);
- **registration without a guardian email is refused** (client-confirmed rule), with the error on
  `parent_email`;
- a second child can register with the same guardian email (siblings), but that email cannot be
  reused as a *child's own* email;
- registering with a staff email as `parent_email` is refused;
- an admin can create/edit a learner with a username and no email **only when a guardian is linked**;
  creating a learner without a guardian is refused; detaching the last guardian from a learner with no
  own email is refused (`AdminUserParentLinkTest` updated to this rule);
- duplicate usernames are refused case-insensitively; reserved words refused;
- reset by username for a child **with no email** emails the guardian; for a child **with an email** it
  emails the child (client rule: own email first). The link sets a new password, signs in, cannot be
  replayed, and two siblings' tokens never collide;
- a parent's own reset goes to the parent's address; **parent-initiated reset** for their own child
  mails the parent and returns 403 for another family's learner;
- the Users screen shows the child's username next to the guardian's email;
- `esh:backfill-usernames` dry-run writes nothing and `--apply` is idempotent;
- `esh:audit-guardian-links` reports an orphan learner and exits non-zero.

`tests/Feature/AccountProvisioningTest.php` — admin-created accounts and the forced first change:

- admin creates a **parent** with username + email + password → `parents` row exists,
  `must_change_password = true`, `AccountProvisionedMail` sent to that email (`Mail::fake()`) and it
  contains the username (not just "click here");
- admin adds a **child** from the parent's edit screen → learner linked to that parent, username saved,
  mail sent to the guardian **and** to the child's own email when one was supplied;
- a duplicate username is refused (parent vs child, sibling vs sibling, case-insensitive);
- a child with no email and no reachable guardian is refused (Invariant G);
- with `mail.default = log` (the UAT setting) the credentials are flashed to the administrator instead
  of silently disappearing;
- first sign-in with the temporary password redirects to `/password/change`; any other URL (dashboard,
  deep link) redirects there too;
- the change screen requires the current password, rejects `same-as-current` and an unconfirmed new
  password; success clears the flag, stamps `password_changed_at`, and lands on the role dashboard;
- afterwards the temporary password no longer works and the new one does;
- **logout stays reachable** from the interstitial;
- a self-registered **child** and any user who completed a reset are **not** forced to change again
  (flag stays false);
- the guardian account auto-created by a child's self-registration **is** flagged, so their first
  sign-in lands on the change screen too.

---

## 2. Note 13 — admissions: assessing a prospect on a video call before placement

### 2.1 What the client asked

> "new / prospective customers / learners are normally accessed via a video call before their
> classes is determined. All this happens via WhatsApp, calls etc and a video call scheduled to
> assess the child. So suggest a better way to add this to the app."

The school's real pipeline today:

1. A parent messages/calls on WhatsApp → the child's details live in a chat and someone's memory.
2. A video call is agreed and scheduled by hand.
3. The tutor assesses the child (level, gaps, class fit).
4. A class is recommended, fees are agreed, the child is enrolled — *then* accounts are created
   (usually by the school).

**The app currently starts at step 4.** Everything before it is invisible, unassigned and
unmeasurable, which is exactly the gap to close.

### 2.2 What already exists to build on

| Need | Existing building block | Reuse? |
|---|---|---|
| Meeting link + platform (Meet/Zoom/phone) | `class_schedules.meeting_link` / `meeting_platform`, tutor schedule UI | pattern only — do **not** store assessments in `class_schedules` (they are not tied to a class/enrolment and would pollute the tutor timetable and the "classes per tutor" report from note 5) |
| Person to run the call | `tutors` + `users` | yes, `tutor_id` FK |
| Notifications | `notifications` table + notification bell | yes |
| Conversation history | `messages` (one-to-one) | yes, after conversion |
| Audit trail | `activity_logs` | yes for status changes |
| Classes to recommend | `classes` (+ `tutor_id`) | yes, `recommended_class_id` |
| Account creation | `Learner`, `ParentModel`, `learners.parent_id`, `ParentWelcomeMail` | yes, in the conversion step |
| Background jobs | **none** — `DEPLOYMENT.md`: no cron, no queue worker (`QUEUE_CONNECTION=sync`) | ⚠ reminders must not depend on cron |

### 2.3 Proposed data model (2 new tables, no change to existing tables)

Statuses and enums are string columns with model constants, matching `Payment` / `Notification` /
`ClassSchedule` style (no database ENUMs, so future states are a code change only).

**`enquiries`** — the prospect, before they are a learner:

| Column | Notes |
|---|---|
| `id`, `reference` | `ENQ-2026-0001`, generated after insert from id + year, unique |
| `child_name`, `child_dob` (nullable), `child_grade_level` (nullable) | what the school asks on WhatsApp |
| `guardian_name`, `guardian_phone` (required), `guardian_email` (nullable at capture, **required before conversion**) | contact channel — the email is the delivery address for the child's username/reset once the account exists (note 12, Invariant G) |
| `source` | `whatsapp`, `phone`, `referral`, `walk_in`, `website`, `instagram`, `other` |
| `status` | `new`, `contacted`, `assessment_booked`, `assessed`, `placed`, `enrolled`, `declined`, `no_show` (+ `archived`) |
| `assigned_to` | `users.id` nullable — who is chasing it |
| `converted_learner_id` | nullable FK → `learners.id`, unique, set once (idempotent conversion) |
| `converted_at`, `declined_reason`, `notes` (free text) | |
| `created_by`, `timestamps` | |
| index | `(status)`, `(guardian_phone)`, `(source)`, `(assigned_to)` |

**`assessment_sessions`** — one row per video call (re-assessments create a new row):

| Column | Notes |
|---|---|
| `enquiry_id` | FK → `enquiries`, cascade |
| `tutor_id` | FK → `tutors`, who runs it |
| `scheduled_at`, `duration_minutes` (default 30) | future-only on create |
| `platform` | `google_meet`, `zoom`, `phone`, `in_person` |
| `meeting_link` | required unless `phone`/`in_person`; validated as URL |
| `status` | `scheduled`, `completed`, `no_show`, `cancelled` |
| `outcome` | nullable: `ready`, `needs_support`, `not_ready` |
| `recommended_class_id` | nullable FK → `classes` |
| `recommended_level` | free text ("Year 5, needs phonics support") |
| `strengths`, `focus_areas`, `notes` (text), `attachment_path` (nullable report file, `public` disk like receipts) | what the tutor reports |
| `conducted_by` (users.id), `completed_at`, `reminder_sent_at` (nullable) | |
| index | `(enquiry_id)`, `(tutor_id, scheduled_at)`, `(status)` |

**`enquiry_notes`** — the timeline (cheap, and it is what stops the team going back to WhatsApp):

`id, enquiry_id, user_id, type (note|call|whatsapp|status), body, created_at` — appended on every
status change automatically, plus free-form notes typed by the team.

**State machine (single source of truth in `Enquiry::STATUS_FLOW`):**

```
new → contacted → assessment_booked → assessed → placed → enrolled
                    ↓                  ↓
                 no_show ──────────────┘ (back to contacted / rebook)
any → declined (reason required) | archived (no delete from the UI)
```

Guards enforced in the model/controller:

- `assessment_booked` only with a future `scheduled_at` + tutor (+ link unless phone/in_person);
- `assessed` only with `outcome` set and `status = completed` on the assessment;
- `placed` only with `recommended_class_id`;
- `enrolled` **only** via the conversion wizard (sets `converted_learner_id`);
- every transition writes an `enquiry_notes` row **and** an `activity_logs` row.

### 2.3a Client clarifications (30 October 2026) — who books, who joins

The phase-1 design above assumes an administrator books from a captured enquiry. The client refined it:

1. **The child may never be registered** — being assessed several times is normal, so nothing may require a
   learner/parent account. The enquiry is the only record.
2. **Booking starts on the public landing page** — a parent can request the assessment from `/` without an
   account. The school then contacts them by **email or WhatsApp**.
3. **Admin/CEO sets up the meeting** — they create the Google Meet/Zoom link and **invite the tutor** who
   will run the assessment.
4. **Three parties join the call** — administrator/CEO, the tutor and the parent. The tutor and the
   school see the join link on their own screens; the **parent receives it by email or WhatsApp** and
   joins as a guest (no portal).
5. Booking therefore creates an `assessment_sessions` row **and** sends the link to the guardian; details
   recorded now: `scheduled_at`, `duration_minutes`, `platform`, `meeting_link`, `tutor_id`, status.

### 2.4 Screens, routes and permissions

| Route | Screen | Who |
|---|---|---|
| `admin.enquiries.index` | Pipeline board/list: status tabs (New, Contacted, Booked, Awaiting placement, Enrolled, Declined), search by child/guardian phone, source filter, "days since contact" chip, counters | CEO + administrators |
| `admin.enquiries.create` | 30-second capture form: child name, guardian name + phone, source, notes. **No account required** — this is the WhatsApp replacement | CEO + administrators |
| `admin.enquiries.show` | The one screen that matters: child/guardian card, timeline (`enquiry_notes`), "Copy WhatsApp message" button, book-assessment form, assessment result form, convert button | CEO + administrators |
| `admin.assessments.index` | Today / upcoming / past assessments, tutor filter, no-show flagging | CEO + administrators |
| `tutor.assessments.index` | *My assessments*: today's call with a Join button, then "record outcome" form | Tutors (own rows only) |
| `POST /book-assessment` (public, guest) | Landing-page request form (child, guardian name/phone/email, preferred slot, notes, honeypot, rate-limited). Creates an enquiry with `source = website` and notifies every administrator/CEO — **no account** | Visitors |
| `admin.assessments.index` (implemented) | Today / Upcoming / Past / All with counts, tutor filter and a Join button per row | CEO + administrators |
| `POST admin/enquiries/{enquiry}/assessments` (implemented) | Booking form on the enquiry screen: tutor, date/time, duration, platform, Meet/Zoom link, guardian email → `assessment_booked`, tutor notification, guardian email | CEO + administrators |
| `admin.enquiries.convert` | Conversion wizard (§2.5) | CEO + administrators |

Sidebar: **People → Admissions** (admin/CEO) and **Teaching → Assessments** (tutors) — one line each
in the `$navSections` definition in `layouts/sidebar.blade.php`.

Privacy: an enquiry is *not* a user, so learners and parents have no access at all. Guardian phone
numbers are only visible to CEO/administrators and the assigned tutor.

### 2.5 The conversion wizard (the step that pays for the feature)

`GET/POST admin/enquiries/{enquiry}/convert` — a 4-step form on one page (all pre-filled from the
enquiry + assessment):

1. **Child** — name, DOB, grade level, (optional) email; otherwise **username** (auto-suggested, per
   note 12) — this is why 12 must land first.
2. **Parent** — match an existing parent by email or phone (`ParentModel` lookup) or create one
   (name, **email required**, phone). Matches are shown with their other children so siblings are
   obvious. **`guardian_email` is mandatory here**: an enquiry may be captured with a phone number
   only, but the wizard refuses to create the child until a guardian email exists — that is the
   client-confirmed rule from note 12 (Invariant G, §1.4.3a).
3. **Placement** — class(es) from `recommended_class_id`, with the capacity rule already in place
   (`max_students` nullable).
4. **Confirm** — one transaction:
   - create/link `users` (parent, learner) + `parents` / `learners` rows, using the same provisioning
     rules as §1.4.3b: parent **username + email + password**, child **username + password + optional
     email**, unique username, `must_change_password = true` on both new accounts;
   - `learners.parent_id`, `class_learner` enrolments;
   - send `AccountProvisionedMail` to the guardian (and to the child's own email when supplied) with
     the usernames, the temporary password and the class list — no password is ever shown to the child
     in the UI;
   - `enquiry.status = enrolled`, `converted_learner_id`, `converted_at`;
   - timeline + activity log entries.

On first sign-in both new accounts land on the forced password-change screen (§1.4.3c), so the
conversion wizard never leaves a family on a school-known password.

Hard rules: **idempotent** (a second submit returns the already-converted learner), refuses when the
enquiry is `declined`, uses `DB::transaction()`, and never creates a duplicate parent for an email
that already exists.

### 2.6 Notifications, reminders and the no-cron constraint

- **Booking** → in-app `Notification` (type `reminder`) to the tutor + an `enquiry_notes` row + an
  **email to the guardian** with the join link (`AssessmentScheduledMail`). The enquiry screen also
  offers a pre-filled `wa.me` message containing the date, time and link.
- **Guardian** → no SMS/WhatsApp API exists, so the screen provides a **pre-filled `wa.me` deep link**
  ("Hi {guardian}, your child's assessment is on {date} at {time}: {link}"). This matches how the
  school already works and needs no third-party account.
- **Reminders** → the deployment has **no cron and no queue** (`DEPLOYMENT.md`), so:
  - v1: "Today's assessments" panel on the CEO/admin dashboard and the tutor dashboard (same pattern
    as the learner "Now / Up next" panel from note 3) — zero infrastructure;
  - v2 (optional, if the school later adds cPanel cron): `esh:assessment-reminders` command + one
    `$schedule->command(...)->dailyAt('08:00')` line in `app/Console/Kernel.php`, gated by
    `reminder_sent_at` so it never double-sends. **Do not ship a reminder that silently depends on a
    cron that does not exist.**
- **After conversion** → the normal parent/learner messaging and notification system takes over.

### 2.7 Validation rules worth spelling out

- `scheduled_at` `after:now`; `duration_minutes` 15–120; `meeting_link` `nullable|url` and
  `required_unless:platform,phone,in_person`.
- `guardian_phone` normalised (+234…), duplicate-phone warnings link to the existing enquiry instead
  of blocking (siblings share a phone).
- `guardian_email` `nullable|email` at capture, but **required before conversion** (Invariant G): the
  wizard shows "Add the guardian's email to create the account" and no child is created without it.
- `outcome` required to complete an assessment; `recommended_class_id` required to reach `placed`.
- A `no_show` assessment cannot be edited to `completed` — book a new one (keeps the history honest
  and gives the school a no-show rate).
- Declining/archiving requires a reason; enquiries are never hard-deleted from the UI.

### 2.8 Phasing — simple to complex, each phase usable on its own

| Phase | Deliverable | Effort (approx.) | Value |
|---|---|---|---|
| **P1** | Enquiries: capture, pipeline list, detail + timeline, dashboard counters | S — ~1–2 dev days | Kills the WhatsApp spreadsheet immediately; no auth/account changes |
| **P2** | Book an assessment (tutor, date/time, platform, link), tutor notification, WhatsApp copy button, "today's assessments" panels | S — ~1–2 days | The video-call workflow becomes assigned and visible |
| **P3** | Record the result (outcome, strengths/focus areas, recommended class, notes, optional report file) | S/M — ~1–2 days | The assessment becomes a document the school and parent can rely on |
| **P4** | Conversion wizard → parent + learner accounts + enrolment (needs note 12) | M — ~2–3 days | Removes the manual account setup that causes most data-entry errors |
| **P5** | Optional: cron reminders, public parent status page, Google Meet/Zoom auto-link creation | M — varies | Convenience only; defer until the school asks |

Ship P1 alone if the client wants something live next week; P1–P3 need no auth work at all.

### 2.9 Test plan

`tests/Feature/AdmissionsTest.php` (feature, following `ClientAdditionsTest` style):

- CEO/admin can create an enquiry without any user account; a tutor/parent/learner gets 403.
- Reference is unique and generated; duplicate guardian phone links, not blocks.
- Status transitions: valid path passes; skipping (`new → enrolled`) is refused; `declined` demands a
  reason; every transition writes a timeline row + `activity_logs` row.
- Booking validation: past date rejected, link required for online platforms, optional for phone.
- Tutor sees only their own assessments and can record an outcome; cannot book for another tutor.
- `assessed` requires an outcome; `placed` requires a recommended class.
- Conversion: creates parent + learner + enrolment in one go, sets `converted_learner_id`, mails the
  parent (`Mail::fake()`), is idempotent on a second submit, reuses an existing parent by email, and
  gives a child without email a username (integration with note 12).
- Dashboard counters: new/booked/awaiting-placement numbers match the rows.
- Smoke test that every new screen renders for an administrator (mirrors the existing render test).

### 2.10 Open decisions I need from the client

| # | Question | My recommendation |
|---|---|---|
| D1 | Who runs the assessment — any tutor, or a designated admissions tutor? | CEO/admin books **any** tutor; the tutor list decides who is available |
| D2 | Does the assessment cost money? | **Free** for now; if it becomes paid, record it through the existing parent payment form and add an `assessment_fee` reference later |
| D3 | Where do Meet/Zoom links come from? | **Paste manually** (same as today's class scheduling). Automatic creation needs Google/Zoom OAuth credentials and is a separate paid integration — related to deferred note 11 |
| D4 | Can a child be assessed more than once? | **Yes** — `assessment_sessions` keeps full history; only the latest completed one drives placement |
| D5 | Do guardians get a portal during admissions? | **No** — WhatsApp link + welcome mail on conversion. A public status page is P5 |
| D6 | Consent for a child to attach to a parent's email (note 12) | The guardian email is **mandatory** (client-confirmed), so this is now only about the *confirmation step*: optional parent-confirmation link before linking. Can ship in a later phase if they want it tighter |
| D7 | What happens to declined/archived enquiries? | Kept forever for reporting; hidden from the default pipeline view |

---

## 3. Why 12 lands before 13

Note 13's conversion step produces exactly the account note 12 describes (a child with no mailbox,
signing in by username). Building 13 first would mean hard-coding an email requirement and then
re-doing the wizard. Recommended order:

1. Note 12 schema + login (no reset changes yet) → can be released on its own.
2. Note 13 P1–P3 (no account creation at all).
3. Note 12 reset-by-username + admin screens + backfill.
4. Note 13 P4 conversion wizard (now simple, because usernames already exist).

Each of those four steps is independently shippable and testable, which is also how the rest of this
project has been delivered (`PLAN_CLIENT_ADDITIONS.md` phases).

## 4. What I need to start

**Confirmed already (29 Oct 2026):** note 11 deferred; guardian email mandatory; usernames unique and
linked to the guardian (Invariant G, §1.4.3a); admin provisions parents/children with a password that
is emailed and must be changed at first sign-in (§1.4.3b–c); delivery uses the child's own email when
present, otherwise the guardian's; the child's email stays optional on the register page.

Still needed:

- A yes/no on **D1–D5 and D7** above (defaults are in the recommended column, so silence = proceed
  with those). D6 is now only about an optional guardian-consent link.
- Three small confirmations on the provisioning flow:
  1. **Forced change scope** — parent + learner accounts only (my recommendation), or every
     administrator-created account including staff?
  2. **Password entry** — should the password field default to a **Generate**d strong password (my
     recommendation, admin can overwrite it), or always be typed by the admin?
  3. **Creation mail recipients** — guardian **always** plus the child's own email when present (my
     recommendation, matches "details go to the father's account"), or the child only when they have
     an email?
  4. **Self-registration guardian** — the guardian account created automatically during a child's
     registration also gets a system-generated password, so the design forces a change on their first
     sign-in too (consistent with the "someone else set the password" rule). Say if you would rather
     leave those accounts untouched.
- Confirmation of the **username rule** (§1.4.7) — especially "administrator-only rename".
- Confirmation that **children existing today** should be backfilled with generated usernames
  (`firstname_lastname`) rather than being asked to choose one at next login, and that legacy learners
  without a guardian will be fixed from the `esh:audit-guardian-links` report before that backfill.
- Whether existing accounts should be left alone (flag `false`, my recommendation) or forced through the
  change screen once as a security reset — that is a one-time decision, not a code default.
