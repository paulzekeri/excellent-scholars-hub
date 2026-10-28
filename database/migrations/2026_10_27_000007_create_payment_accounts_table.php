<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The bank accounts the school receives fees into.
 *
 * The CEO adds them once and parents see them on the payment form, so nobody has
 * to retype (or guess) an account number on WhatsApp. An account is only shown
 * while it is active; switching it off hides it from parents without losing the
 * payments that were already made into it.
 */
class CreatePaymentAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number')->unique();
            $table->text('instructions')->nullable()->comment('Shown to parents, e.g. use the child\'s name as narration');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_accounts');
    }
}
