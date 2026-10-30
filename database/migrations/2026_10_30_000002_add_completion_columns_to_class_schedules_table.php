<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Note 5 — an administrator (or the CEO) can tick a scheduled class as
 * completed, and the monthly tutor report counts classes actually taken. The
 * audit columns record who ticked it and when; reopening clears them.
 */
class AddCompletionColumnsToClassSchedulesTable extends Migration
{
    public function up()
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('status');
            $table->unsignedBigInteger('completed_by')->nullable()->after('completed_at');

            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropForeign(['completed_by']);
            $table->dropColumn(['completed_at', 'completed_by']);
        });
    }
}
