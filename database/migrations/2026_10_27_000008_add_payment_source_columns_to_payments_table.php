<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records *how* the money reached the school.
 *
 * `payment_method` already existed but only held a free-text value. The parent
 * form now offers bank transfer, cash and (later) card, so a transfer also keeps
 * the school account it was paid into. `paid_into` is a snapshot ("GTBank ·
 * 0123456789") so deleting an account never rewrites history.
 *
 * `gateway` / `gateway_reference` are reserved for the Paystack card flow: the
 * gateway confirms the money itself, so those rows arrive already completed.
 */
class AddPaymentSourceColumnsToPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_account_id')->nullable()->after('payment_method');
            $table->string('paid_into')->nullable()->after('payment_account_id');
            $table->string('gateway')->nullable()->after('paid_into');
            $table->string('gateway_reference')->nullable()->after('gateway');

            $table->foreign('payment_account_id')->references('id')->on('payment_accounts')->onDelete('set null');
            $table->index('gateway_reference');
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['gateway_reference']);
            $table->dropForeign(['payment_account_id']);
            $table->dropColumn(['payment_account_id', 'paid_into', 'gateway', 'gateway_reference']);
        });
    }
}
