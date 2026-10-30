<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Note 7 — learning goals can also be shared with the learner themselves, not
 * just with the parent and/or another tutor.
 */
class AddLearnerSharingToLearningGoals extends Migration
{
    public function up()
    {
        Schema::table('learning_goals', function (Blueprint $table) {
            $table->boolean('shared_with_learner')->default(false)->after('shared_with_tutor');
        });
    }

    public function down()
    {
        Schema::table('learning_goals', function (Blueprint $table) {
            $table->dropColumn('shared_with_learner');
        });
    }
}
