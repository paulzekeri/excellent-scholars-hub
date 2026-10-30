<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One bank transfer can pay for several classes at once.
 *
 * A parent who transfers ₦300,000 for two classes records one installment per
 * class (each with its own fee and balance) but the bank shows a single
 * reference, so the same reference must be allowed on those rows. The unique
 * index becomes a plain lookup index; the payment form still rejects a repeat
 * of a reference on a single-installment submission.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_reference_unique');
            $table->index('reference', 'payments_reference_index');
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_reference_index');
            $table->unique('reference', 'payments_reference_unique');
        });
    }
};
