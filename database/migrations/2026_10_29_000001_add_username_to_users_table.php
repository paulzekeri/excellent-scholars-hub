<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Note 12 — a child who has no mailbox signs in with a username, and any account
 * the school provisions must choose its own password at first sign-in.
 *
 * `username` is the unique login handle, `must_change_password` marks a
 * temporary (school-set) password, and `email` becomes nullable so siblings can
 * share the guardian's address instead of creating fake ones.
 *
 * doctrine/dbal is not installed, so the nullability change is a driver-aware
 * raw statement — the same technique already used by
 * 2026_10_27_000002_make_max_students_nullable_on_classes_table.
 */
class AddUsernameToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique()->after('name');
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->timestamp('password_changed_at')->nullable()->after('must_change_password');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN email DROP NOT NULL');
        }
        // sqlite (not used by this project's test setup) cannot alter a column
        // without doctrine/dbal; the MySQL/PostgreSQL paths cover deployments.
    }

    public function down()
    {
        $driver = Schema::getConnection()->getDriverName();

        // Restore NOT NULL: give any email-less learner a placeholder address so
        // the constraint can be applied again without losing the account.
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("UPDATE users SET email = CONCAT('user', id, '@placeholder.local') WHERE email IS NULL");
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement("UPDATE users SET email = 'user' || id || '@placeholder.local' WHERE email IS NULL");
            DB::statement('ALTER TABLE users ALTER COLUMN email SET NOT NULL');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'must_change_password', 'password_changed_at']);
        });
    }
}
