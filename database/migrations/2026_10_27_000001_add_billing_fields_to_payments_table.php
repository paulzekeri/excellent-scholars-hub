<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payments were one flat row per receipt. The client asked for part payments,
 * a receipt image/PDF per payment and the ability to allocate a payment to a
 * term and/or a class. Each row is now one installment; the fee it belongs to
 * is described by (learner_id, term, class_id) and capped by `total_fee`.
 */
class AddBillingFieldsToPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('term')->nullable()->after('payment_type');
            $table->unsignedBigInteger('class_id')->nullable()->after('learner_id');
            // The full fee for this term/class. Null keeps the old "standalone
            // payment, nothing to balance" behaviour.
            $table->decimal('total_fee', 10, 2)->nullable()->after('amount');
            $table->string('receipt_path')->nullable()->after('notes');
            $table->string('receipt_name')->nullable()->after('receipt_path');

            $table->foreign('class_id')->references('id')->on('classes')->onDelete('set null');
            $table->index(['learner_id', 'term', 'class_id'], 'payments_billing_index');
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_billing_index');
            $table->dropForeign(['class_id']);
            $table->dropColumn(['term', 'class_id', 'total_fee', 'receipt_path', 'receipt_name']);
        });
    }
}
