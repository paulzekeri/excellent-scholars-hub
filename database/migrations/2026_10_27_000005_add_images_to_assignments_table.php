<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Teachers to be able to upload pictures with assignment" — up to six images
 * per assignment, stored as a JSON array of public-disk paths.
 */
class AddImagesToAssignmentsTable extends Migration
{
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->json('images')->nullable()->after('file_path');
        });
    }

    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('images');
        });
    }
}
