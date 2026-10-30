<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Note 13 phase 2 — booking the video-call assessment.
 *
 * A child may be assessed several times without ever registering, so a session
 * belongs to the *enquiry*, never to a learner. The school creates the
 * Meet/Zoom link, assigns the tutor, and the guardian receives the link by
 * email/WhatsApp and joins as a guest.
 *
 * `preferred_at` records the slot a parent asks for on the public landing page;
 * it is only a preference — the school confirms the final `scheduled_at`.
 */
class CreateAssessmentSessionsTable extends Migration
{
    public function up()
    {
        Schema::create('assessment_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enquiry_id');
            $table->unsignedBigInteger('tutor_id');
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('platform', 20)->default('google_meet');
            $table->string('meeting_link')->nullable();
            $table->string('status', 20)->default('scheduled');

            // Filled in when the tutor records the result (phase 3).
            $table->string('outcome', 20)->nullable();
            $table->unsignedBigInteger('recommended_class_id')->nullable();
            $table->string('recommended_level')->nullable();
            $table->text('strengths')->nullable();
            $table->text('focus_areas')->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->unsignedBigInteger('conducted_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            $table->index('enquiry_id');
            $table->index(['tutor_id', 'scheduled_at']);
            $table->index('status');

            $table->foreign('enquiry_id')->references('id')->on('enquiries')->onDelete('cascade');
            $table->foreign('tutor_id')->references('id')->on('tutors')->onDelete('cascade');
            $table->foreign('recommended_class_id')->references('id')->on('classes')->onDelete('set null');
            $table->foreign('conducted_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('enquiries', function (Blueprint $table) {
            $table->dateTime('preferred_at')->nullable()->after('guardian_email');
        });
    }

    public function down()
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropColumn('preferred_at');
        });

        Schema::dropIfExists('assessment_sessions');
    }
}
