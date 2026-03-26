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
        // Check if the table already exists to avoid errors on re-run
        if (!Schema::hasTable('colors')) {
            Schema::create('colors', function (Blueprint $table) {
                $table->id(); // Auto-incrementing primary key for colors
                $table->string('name')->unique(); // Unique name for the color (e.g., 'red', 'blue')
                $table->timestamps(); // created_at and updated_at columns
            });
            // Schema::create('colors', function (Blueprint $table) {
            //     $table->id();
            //     $table->string('name'); // e.g., "Red", "Midnight Blue"
            //     $table->string('hex_code')->nullable(); // e.g., "#FF0000" (Useful for Flutter UI)
            //     $table->timestamps();
            // });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};
