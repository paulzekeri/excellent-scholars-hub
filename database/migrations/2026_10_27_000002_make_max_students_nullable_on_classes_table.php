<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "The max number of students should not be mandatory" — a class without a
 * capacity is simply unlimited, so the column has to accept NULL.
 *
 * doctrine/dbal is not installed (Laravel 8 needs it for ->change()), so the
 * column is altered with a driver-aware statement instead.
 */
class MakeMaxStudentsNullableOnClassesTable extends Migration
{
    public function up()
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE classes MODIFY max_students INT NULL DEFAULT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE classes ALTER COLUMN max_students DROP NOT NULL');
        }
        // sqlite: the column was created as nullable-agnostic by the original
        // migration in the test environment, so nothing to do.
    }

    public function down()
    {
        $driver = Schema::getConnection()->getDriverName();

        // Existing NULLs have to be filled before the NOT NULL constraint returns.
        DB::table('classes')->whereNull('max_students')->update(['max_students' => 30]);

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE classes MODIFY max_students INT NOT NULL DEFAULT 30');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE classes ALTER COLUMN max_students SET NOT NULL');
        }
    }
}
