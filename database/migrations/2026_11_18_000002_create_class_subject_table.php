<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which subjects a class teaches, and who takes each one.
 *
 * One row per subject per class, so:
 *   - Test Class WAEC → Mathematics (John Tutor)
 *   - Test Class WAEC → Physics     (John Tutor)
 *   - Test Class WAEC → English     (another tutor)
 *
 * `tutor_id` is nullable because a subject can be added before its teacher is
 * decided. The unique index stops the same subject being added twice.
 */
class CreateClassSubjectTable extends Migration
{
    public function up()
    {
        Schema::create('class_subject', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('tutor_id')->nullable();
            $table->timestamps();

            $table->unique(['class_id', 'subject_id']);
            $table->index('tutor_id');

            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            // Removing a tutor leaves the subject in place, unassigned.
            $table->foreign('tutor_id')->references('id')->on('tutors')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('class_subject');
    }
}
