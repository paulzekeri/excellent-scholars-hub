<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Classroom library" — the school keeps its videos, PDFs and documents in Google
 * Drive and shares the folder/file links through the class WhatsApp group today.
 * Tutors and administrators now attach those links to a class here, and the
 * learners enrolled in that class open them from My Learning → Class Library.
 */
class CreateClassLibraryResourcesTable extends Migration
{
    public function up()
    {
        Schema::create('class_library_resources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('created_by')->nullable()
                ->comment('Administrator, CEO or tutor who added the link');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url', 2048);
            $table->string('type')->default('link')->comment('link, video, pdf, document');
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('class_library_resources');
    }
}
