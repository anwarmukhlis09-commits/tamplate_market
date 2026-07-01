<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique()->comment('Format: ORD-YYYYMMDD-{template_id}-{user_id}');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('completed')->comment('pending|completed|cancelled|failed');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->default('simulated');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Index untuk query cepat "user X sudah beli template Y?"
            $table->unique(['user_id', 'template_id'], 'uniq_user_template_order');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};