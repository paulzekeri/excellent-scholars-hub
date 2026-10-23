<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceTable extends Migration
{
    public function up()
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_schedule_id');
            $table->unsignedBigInteger('learner_id');
            $table->string('status')->default('present')->comment('present, absent, late, excused');
            $table->text('notes')->nullable();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();
            
            $table->foreign('class_schedule_id')->references('id')->on('class_schedules')->onDelete('cascade');
            $table->foreign('learner_id')->references('id')->on('learners')->onDelete('cascade');
            $table->unique(['class_schedule_id', 'learner_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance');
    }
}
