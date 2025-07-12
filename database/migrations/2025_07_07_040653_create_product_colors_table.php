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
        // If this is a new table creation
        if (!Schema::hasTable('product_colors')) {
            Schema::create('product_colors', function (Blueprint $table) {
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('color_id');
                $table->string('image_url')->nullable(); // New column for the image URL
                $table->timestamps();

                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('color_id')->references('id')->on('colors')->onDelete('cascade');

                $table->primary(['product_id', 'color_id']);
            });
        } else {
            // If the table already exists, add the column
            if (!Schema::hasColumn('product_colors', 'image_url')) {
                Schema::table('product_colors', function (Blueprint $table) {
                    $table->string('image_url')->nullable()->after('color_id'); // Add after color_id
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If this was a new table creation
        if (Schema::hasTable('product_colors') && !Schema::hasColumn('product_colors', 'image_url')) {
            Schema::dropIfExists('product_colors');
        } else {
            // If we just added the column
            if (Schema::hasColumn('product_colors', 'image_url')) {
                Schema::table('product_colors', function (Blueprint $table) {
                    $table->dropColumn('image_url');
                });
            }
        }
    }
};
