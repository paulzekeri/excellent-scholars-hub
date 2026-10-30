<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Note 8 — the school can write the weekly affirmation instead of relying on the
 * built-in rotation. The latest active post is shown on every dashboard; older
 * posts stay in the history and can be switched off or deleted.
 */
class CreateAffirmationsTable extends Migration
{
    public function up()
    {
        Schema::create('affirmations', function (Blueprint $table) {
            $table->id();
            $table->text('body');
            $table->unsignedBigInteger('author_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'published_at']);

            $table->foreign('author_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('affirmations');
    }
}
