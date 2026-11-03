<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parents describe each payment line in their own words ("First term tuition",
 * "Registration for JSS2 Coding", "Textbook"). The purpose is separate from
 * `notes`, which the CEO uses for a rejection reason.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('purpose', 500)->nullable()->after('payment_type');
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });
    }
};
