<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Learning goals are to be shared to individual parents / individual learners /
 * individual tutors" — one row per person a goal was shared with, so the school
 * can pick exactly who sees a goal instead of switching a whole role on.
 *
 * The legacy shared_with_parent / shared_with_learner / shared_with_tutor flags
 * stay in place: they still mean "share with everyone this goal is about".
 */
class CreateLearningGoalSharesTable extends Migration
{
    public function up()
    {
        Schema::create('learning_goal_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learning_goal_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role_slug')->comment('parent, learner or tutor');
            $table->unsignedBigInteger('shared_by')->nullable();
            $table->timestamps();

            $table->foreign('learning_goal_id')->references('id')->on('learning_goals')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('shared_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['learning_goal_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('learning_goal_shares');
    }
}
