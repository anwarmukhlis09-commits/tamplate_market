<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->default('modern');
            $table->text('short_desc')->nullable();
            $table->text('long_desc')->nullable();
            $table->integer('price')->default(0);
            $table->integer('discount_price')->nullable();
            $table->string('badge')->nullable();
            $table->json('features')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('allow_edit_before_checkout')->default(true);
            $table->string('preview_image')->nullable();
            $table->json('preview_gradients')->nullable();
            $table->string('zip_file')->nullable();
            $table->integer('sold_count')->default(0);
            $table->decimal('rating', 2, 1)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
