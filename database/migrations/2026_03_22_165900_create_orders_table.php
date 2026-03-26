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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Status tracking
            $table->string('order_number')->unique(); // e.g., ORD-2024-0001
            $table->enum('status', ['pending', 'processing', 'delivered', 'completed', 'cancelled', 'refunded'])->default('pending');

            // Financials
            $table->decimal('subtotal', 12, 2); // Sum of items before discounts/tax
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('shipping_cost', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2); // Final amount paid

            // Snapshots (Keep these even if user changes profile later)
            $table->string('shipping_address')->nullable(); // Stores name, street, city, zip
            $table->string('payment_method')->nullable(); // e.g., 'Stripe', 'PayPal'
            $table->string('payment_status')->default('unpaid');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
