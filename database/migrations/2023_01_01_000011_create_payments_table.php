<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('learner_id')->nullable();
            $table->string('payment_type')->comment('tuition, registration, material, other');
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('NGN');
            $table->string('payment_method')->nullable()->comment('cash, transfer, card, etc');
            $table->string('reference')->unique()->nullable();
            $table->string('status')->default('pending')->comment('pending, completed, failed, refunded');
            $table->text('notes')->nullable();
            $table->date('payment_date');
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('learner_id')->references('id')->on('learners')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
