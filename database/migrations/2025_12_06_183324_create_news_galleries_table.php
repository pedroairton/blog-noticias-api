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
        Schema::create('news_galleries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained()->onDelete('cascade');
            
            $table->string('image_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('caption')->nullable();
            $table->string('alt_text')->nullable();

            $table->integer('position')->default(0);
            $table->string('placeholder_in_content')->nullable();

            $table->string('original_name');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->json('dimensions')->nullable();
            $table->timestamps();

            $table->index('news_id');
            $table->index('position');
            $table->index(['news_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_galleries');
    }
};
