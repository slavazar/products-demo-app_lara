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
        Schema::create('user_product_images', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id'); // Index for faster lookups
            $table->string('image_path'); // Path to the image file
            $table->string('image_name')->nullable(); // Original filename
            $table->string('alt_text')->nullable(); // Alt text for accessibility
            $table->integer('sort_order')->default(0); // For ordering images
            $table->boolean('is_primary')->default(false); // Mark as primary image
            $table->string('mime_type')->nullable(); // Image MIME type
            $table->integer('file_size')->nullable(); // File size in bytes
            $table->json('metadata')->nullable(); // Additional metadata (dimensions, etc.)
            $table->timestamps();

            // Indexes
            $table->index('product_id');
            $table->index(['product_id', 'sort_order']);
            $table->index(['product_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_product_images');
    }
};
