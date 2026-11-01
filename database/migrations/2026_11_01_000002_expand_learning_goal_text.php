<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Learning goals should be able to take enough text" — the goal itself moved
 * from a 255-character title to a free text area. doctrine/dbal is not
 * installed (Laravel 8 needs it for ->change()), so the column is altered with
 * a driver-aware statement, the same way max_students was made nullable.
 */
class ExpandLearningGoalText extends Migration
{
    public function up()
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE learning_goals MODIFY title TEXT NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE learning_goals ALTER COLUMN title TYPE TEXT');
        }
        // sqlite stores strings without a hard length, so nothing to do.
    }

    public function down()
    {
        $driver = Schema::getConnection()->getDriverName();

        // Truncate so the shorter column accepts every row again.
        DB::table('learning_goals')
            ->whereRaw('LENGTH(title) > 255')
            ->update(['title' => DB::raw('SUBSTR(title, 1, 255)')]);

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE learning_goals MODIFY title VARCHAR(255) NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE learning_goals ALTER COLUMN title TYPE VARCHAR(255)');
        }
    }
}
