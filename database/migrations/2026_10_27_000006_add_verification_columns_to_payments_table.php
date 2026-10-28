<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parents now record their own payments (a parent usually pays for two or
 * three children, so it is quicker than the CEO entering a hundred), and the
 * CEO only confirms the money with the bank.
 *
 * A parent submission therefore arrives as `pending`; verifying it flips the
 * status to `completed` (which is what clears the balance) and stamps who
 * confirmed it and when.
 */
class AddVerificationColumnsToPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('status');
            $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');

            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['verified_at', 'verified_by']);
        });
    }
}
