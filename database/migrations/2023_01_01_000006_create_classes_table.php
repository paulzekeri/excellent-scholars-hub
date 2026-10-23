<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClassesTable extends Migration
{
    public function up()
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('tutor_id');
            $table->string('grade_level')->nullable();
            $table->string('subject')->nullable();
            $table->integer('max_students')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('tutor_id')->references('id')->on('tutors')->onDelete('cascade');
        });

        Schema::create('class_learner', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('learner_id');
            $table->timestamps();
            
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->foreign('learner_id')->references('id')->on('learners')->onDelete('cascade');
            $table->unique(['class_id', 'learner_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('class_learner');
        Schema::dropIfExists('classes');
    }
}
