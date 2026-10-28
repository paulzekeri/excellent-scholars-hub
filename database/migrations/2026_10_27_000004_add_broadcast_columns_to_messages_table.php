<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categorised messaging: one broadcast writes a `messages` row per recipient,
 * tagged with the audience it came from so the sender can report on it and the
 * recipient can see why they received it.
 */
class AddBroadcastColumnsToMessagesTable extends Migration
{
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('audience')->nullable()->after('body')
                ->comment('parents, tutors, learners, everyone, class');
            $table->unsignedBigInteger('class_id')->nullable()->after('audience');
            $table->string('batch')->nullable()->after('class_id')
                ->comment('Groups the rows created by one broadcast');

            $table->foreign('class_id')->references('id')->on('classes')->onDelete('set null');
            $table->index('batch');
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['batch']);
            $table->dropForeign(['class_id']);
            $table->dropColumn(['audience', 'class_id', 'batch']);
        });
    }
}
