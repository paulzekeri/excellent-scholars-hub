-- ===========================================================================
--  Share one test password across every account (UAT / staging only)
-- ===========================================================================
--  Use this on a TEST database where nobody wants to remember a password per
--  role. The default section copies the bcrypt hash that the admin account
--  already logs in with, so every account can be opened with the admin's
--  existing password - the plaintext is never needed, bcrypt compares hashes.
--
--  How to run (live/UAT, phpMyAdmin): select the database, open the SQL tab,
--  paste this file and press Go. From a shell: mysql -u USER -p DB < this.sql
--
--  NEVER run this against production data.
--  A fresh import of users.sql will overwrite these hashes again, so re-run it
--  after every data import.
-- ===========================================================================

START TRANSACTION;

-- 1. Take the hash of the first admin account.
--    If your admin login is not on the "admin" role, replace the WHERE clause
--    with: WHERE u.email = 'admin@esh.com'
SET @shared_hash = (
    SELECT u.password
    FROM users u
    JOIN roles r ON r.id = u.role_id
    WHERE r.slug = 'admin'
    ORDER BY u.id
    LIMIT 1
);

SELECT IF(@shared_hash IS NULL,
          'No admin account found - NOTHING was changed',
          CONCAT('Admin hash loaded: ', @shared_hash)) AS step_1;

-- 2. Give every account that same hash, make sure it can sign in and drop any
--    stale "remember me" tokens. The WHERE guard keeps the UPDATE a no-op when
--    step 1 could not find an admin instead of writing NULL passwords.
UPDATE users
   SET password       = @shared_hash,
       is_active      = 1,
       remember_token = NULL,
       updated_at     = NOW()
 WHERE @shared_hash IS NOT NULL;

-- 3. Verify: one single hash across all rows, everyone active.
--    distinct_passwords = 1  -> the change landed, every account now takes the
--                               administrator password. If a non-admin login
--                               STILL fails, the password was never the
--                               problem: check the DIAGNOSTIC block at the
--                               bottom of this file and storage/logs/laravel.log.
--    distinct_passwords > 1  -> the update did not reach this database (wrong
--                               database selected, or the users.sql import
--                               loaded different hashes afterwards).
SELECT COUNT(*)                 AS users_total,
       COUNT(DISTINCT password) AS distinct_passwords,
       SUM(is_active = 1)       AS active_users
  FROM users;

COMMIT;

-- ===========================================================================
--  OPTION 2 - known password instead of the admin's
-- ===========================================================================
--  If you would rather use a password you can hand to testers, uncomment the
--  two statements below. This hash is bcrypt (cost 10) of:  EshUat2026
--
--  UPDATE users
--     SET password       = '$2y$10$/9ogpyL7yni5apG0GvEQyuGk2yO1OIoG8L8R.xBBvY.CFzLixC1JC',
--         is_active      = 1,
--         remember_token = NULL,
--         updated_at     = NOW();
--
--  SELECT COUNT(*) AS users_total, COUNT(DISTINCT password) AS distinct_passwords FROM users;

-- ===========================================================================
--  DIAGNOSTIC - "the password is right but the page still fails"
-- ===========================================================================
--  Learner/tutor dashboards expect a learners/tutors/parents profile row for
--  the signed-in user. A missing profile throws a 500 *after* a successful
--  password check, which looks exactly like a broken login. Run this to list
--  such accounts (it should return no rows on a consistent database):
--
--  SELECT u.id, u.email, r.slug,
--         (SELECT COUNT(*) FROM learners l WHERE l.user_id = u.id) AS learner_rows,
--         (SELECT COUNT(*) FROM tutors   t WHERE t.user_id = u.id) AS tutor_rows,
--         (SELECT COUNT(*) FROM parents  p WHERE p.user_id = u.id) AS parent_rows
--    FROM users u
--    JOIN roles r ON r.id = u.role_id
--   WHERE (r.slug = 'learner' AND NOT EXISTS (SELECT 1 FROM learners l WHERE l.user_id = u.id))
--      OR (r.slug = 'tutor'   AND NOT EXISTS (SELECT 1 FROM tutors   t WHERE t.user_id = u.id))
--      OR (r.slug = 'parent'  AND NOT EXISTS (SELECT 1 FROM parents  p WHERE p.user_id = u.id));
