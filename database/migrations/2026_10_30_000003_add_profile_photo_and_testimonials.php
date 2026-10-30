<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Note 10 — self-service profiles (name, email, photo) and member testimonials
 * that the CEO approves before they appear on the landing page.
 */
class AddProfilePhotoAndTestimonials extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('phone');
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->text('body');
            $table->boolean('publish_photo')->default(false);
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index('status');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('testimonials');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
}
