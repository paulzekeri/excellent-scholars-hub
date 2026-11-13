# The maintenance / super-user account

An account with the **CEO role** and the **`is_hidden`** flag: it can see and do
everything an administrator/CEO can (admin area, every list, reports and the
CEO-only payments), while no other user can see that it exists.

## What “hidden” means

| Area | Behaviour |
|---|---|
| Admin → Users (list, search, role counts) | Account never appears |
| Admin dashboard (total users, recent users) | Not counted, not listed |
| Messages (every role's contact picker) | Not offered as a contact |
| Broadcasts (audience fan-out) | Never receives a copy |
| Notifications (single user *or* all users) | Never a recipient |
| Enquiry "assign to" dropdown | Not listed |
| Payment / public-booking staff notifications | Skipped |
| Admin → Users edit / delete / reset password | **404** — it looks like a missing record |
| Password-reset email (forgot password) | Never sent; the form answers exactly as for any address |
| Login, admin area, payments, every page | Works normally (CEO privileges) |

The hidden account itself still appears in **its own** top-bar/sidebar (it is the
signed-in user) and its actions are written to `activity_logs` like anyone
else's. Avoid sending messages *from* it — recipients would see the display name.

## Create it (once per environment)

```bash
php artisan esh:create-super-admin \
  --name="Maintenance" \
  --email="maintenance@your-school.org" \
  --password="a-long-unique-password"
```

- Run it on **local**, **UAT** and **production** separately, each with its own
  credentials.
- No email is sent when the account is created, and none can be sent to it later.
- Re-running the same command with the same `--email` **rotates** the password of
  the existing hidden account (it never creates a duplicate).
- Sign in normally at `/login` with that email and password.

Useful flags: `--role` is not offered on purpose (it is always `ceo`). Password
can also be typed interactively if you omit `--password`.

### Create automatically at deploy (optional)

Every deploy runs `php artisan migrate --force --seed`, and the seeder creates the
hidden account **once** when these are set in the server's `.env`:

```ini
SUPER_ADMIN_NAME="Maintenance"
SUPER_ADMIN_EMAIL=maintenance@your-school.org
SUPER_ADMIN_PASSWORD=a-long-unique-password
```

Then `php artisan config:cache` (the deploy does it) and the next deploy creates
it. If the email already exists, nothing is overwritten — so a password rotated
with `esh:create-super-admin` later stays as you set it. Leave the three values
blank to skip this entirely.

## Direct SQL (alternative)

Prefer the command, but a direct insert works on any environment:

```sql
-- 1. Generate a bcrypt hash for the password (cost 12, like Laravel):
--    php -r "echo password_hash('YourStrongPassword', PASSWORD_BCRYPT, ['cost' => 12]), PHP_EOL;"

INSERT INTO users
    (name, username, email, password, role_id, is_active, is_hidden,
     must_change_password, created_at, updated_at)
VALUES
    ('Maintenance', NULL, 'maintenance@your-school.org',
     '$2y$12$REPLACE_WITH_THE_GENERATED_HASH',
     (SELECT id FROM roles WHERE slug = 'ceo'),
     1, 1, 0, NOW(), NOW());
```

Requirements: the `users.is_hidden` column (migration
`2026_11_06_000001_add_is_hidden_to_users_table`) and a `roles` row with
`slug = 'ceo'` (created by the migrations/command). `username` may stay NULL —
the account signs in with its email address.

## Remove or disable it

```sql
-- remove every maintenance account
DELETE FROM users WHERE is_hidden = 1;

-- or just lock it
UPDATE users SET is_active = 0 WHERE is_hidden = 1;
```

## Why not a new role?

`is_hidden` + the existing `ceo` role means every permission check, report and
CEO-only screen keeps working without touching the role matrix. Any future
`isCeo()` check automatically applies to the maintenance account; nothing has to
be special-cased.

## Tests

`tests/Feature/SuperAdminTest.php` covers: the command creates a hidden CEO that
can open the admin area and payments; rotating the password; absence from the
users list, role counts, dashboard totals, messaging contacts and notification
fan-out; 404 when another administrator tries to edit/delete/reset it; and that
password-reset mail is never sent to it (while a normal account still works).
