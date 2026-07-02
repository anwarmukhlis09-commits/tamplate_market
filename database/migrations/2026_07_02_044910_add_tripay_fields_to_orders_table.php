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
        Schema::table('orders', function (Blueprint $table) {
            // Tripay transaction metadata — disimpan saat createTransaction sukses,
            // dipakai Waiting.vue untuk tampilkan VA/QR + link checkout Tripay.
            $table->string('tripay_reference')->nullable()->after('payment_method');
            $table->string('tripay_checkout_url', 500)->nullable()->after('tripay_reference');
            $table->string('tripay_pay_code')->nullable()->after('tripay_checkout_url');
            $table->string('tripay_pay_url', 500)->nullable()->after('tripay_pay_code');
            $table->text('tripay_qr_string')->nullable()->after('tripay_pay_url');

            // Countdown expiry (Tripay default: 24 jam setelah create).
            $table->timestamp('expired_at')->nullable()->after('paid_at');

            // Raw payload dari Tripay callback untuk audit/debug.
            $table->json('callback_payload')->nullable()->after('expired_at');

            $table->index('tripay_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['tripay_reference']);
            $table->dropColumn([
                'tripay_reference',
                'tripay_checkout_url',
                'tripay_pay_code',
                'tripay_pay_url',
                'tripay_qr_string',
                'expired_at',
                'callback_payload',
            ]);
        });
    }
};