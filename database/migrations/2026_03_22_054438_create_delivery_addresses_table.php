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
        // Schema::create('delivery_addresses', function (Blueprint $table) {
        //     $table->id();
        //     $table->timestamps();
        // });
        Schema::create('delivery_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');

            // The "Human" part
            $table->string('address_name')->default('Home');
            $table->string('formatted_address'); // The full string from Google

            // The "Machine" part (Coordinates)
            // Use decimal(10, 8) and (11, 8) for precision
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Snapshot of specific parts
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('google_place_id')->nullable(); // Important for re-verifying

            // $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_addresses');
    }
};
