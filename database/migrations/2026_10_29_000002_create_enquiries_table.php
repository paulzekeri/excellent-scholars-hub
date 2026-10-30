<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Note 13 (phase 1) — the admissions pipeline starts before a learner exists.
 *
 * An enquiry is the child/guardian details captured from a WhatsApp message or
 * a phone call, plus the state of the conversation. `enquiry_notes` is the
 * timeline that replaces digging through a chat. No learner, parent or user row
 * is created until the conversion step (phase 4).
 */
class CreateEnquiriesTable extends Migration
{
    public function up()
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->nullable()->unique();
            $table->string('child_name');
            $table->date('child_dob')->nullable();
            $table->string('child_grade_level')->nullable();
            $table->string('guardian_name');
            $table->string('guardian_phone', 30);
            $table->string('guardian_email')->nullable();
            $table->string('source', 20)->default('whatsapp');
            $table->string('status', 30)->default('new');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('converted_learner_id')->nullable()->unique();
            $table->timestamp('converted_at')->nullable();
            $table->text('declined_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('source');
            $table->index('guardian_phone');

            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('converted_learner_id')->references('id')->on('learners')->onDelete('set null');
        });

        Schema::create('enquiry_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enquiry_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type', 20)->default('note');
            $table->text('body');
            $table->timestamps();

            $table->index(['enquiry_id', 'created_at']);

            $table->foreign('enquiry_id')->references('id')->on('enquiries')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('enquiry_notes');
        Schema::dropIfExists('enquiries');
    }
}
