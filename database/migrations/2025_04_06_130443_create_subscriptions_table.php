<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->string('paystack_subscription_code')->nullable();
            $table->string('paystack_customer_code')->nullable();
            $table->string('authorization_code')->nullable();
            $table->string('status')->default('active');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->dateTime('next_payment_date')->nullable();
            $table->string('billing_cycle');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
