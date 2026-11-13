<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The schedules screens now open on a single week and sort by start_time, so
 * that column gets an index instead of scanning the whole table.
 */
class AddStartTimeIndexToClassSchedulesTable extends Migration
{
    public function up()
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->index('start_time');
        });
    }

    public function down()
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropIndex(['start_time']);
        });
    }
}
