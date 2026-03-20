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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Links this payment to the specific order
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');

            $table->string('transaction_id')->unique(); // ID from Stripe/PayPal/etc.
            $table->string('payment_method'); // e.g., 'credit_card', 'paypal'
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('USD');
            $table->string('status'); // 'succeeded', 'pending', 'failed'
            $table->json('payload')->nullable(); // Store the full API response for debugging
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
