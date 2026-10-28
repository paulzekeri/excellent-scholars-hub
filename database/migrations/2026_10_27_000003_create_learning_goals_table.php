<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tutors upload learning goals; administrators and the CEO review them and
 * decide which ones are shared with the parent and/or the tutor.
 */
class CreateLearningGoalsTable extends Migration
{
    public function up()
    {
        Schema::create('learning_goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tutor_id');
            $table->unsignedBigInteger('learner_id')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('term')->nullable();
            $table->string('target')->nullable()->comment('What success looks like');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('status')->default('active')->comment('active, achieved, archived');
            $table->boolean('shared_with_parent')->default(false);
            $table->boolean('shared_with_tutor')->default(false);
            $table->unsignedBigInteger('shared_by')->nullable();
            $table->timestamp('shared_at')->nullable();
            $table->timestamps();

            $table->foreign('tutor_id')->references('id')->on('tutors')->onDelete('cascade');
            $table->foreign('learner_id')->references('id')->on('learners')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('set null');
            $table->foreign('shared_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['learner_id', 'shared_with_parent']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('learning_goals');
    }
}
