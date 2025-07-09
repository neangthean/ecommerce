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
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('subTitle')->nullable();
                $table->decimal('discount', 5, 2)->default(0.00);
                $table->decimal('price', 8, 2)->default(0.00);
                $table->string('product_image')->nullable();
                $table->unsignedBigInteger('category_id');
                $table->timestamps();

                // Foreign key constraint for category_id
                // Ensure 'categories' table exists before running this migration
                $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
