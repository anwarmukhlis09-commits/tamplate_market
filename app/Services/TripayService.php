<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TripayService
{
    protected $apiUrl;
    protected $merchantCode;
    protected $apiKey;
    protected $privateKey;

    public function __construct()
    {
        $this->apiUrl = config('tripay.api_url');
        $this->merchantCode = config('tripay.merchant_code');
        $this->apiKey = config('tripay.api_key');
        $this->privateKey = config('tripay.private_key');
    }

    /**
     * Get available payment channels
     */
    public function getPaymentChannels()
    {
        $response = Http::withToken($this->apiKey)
            ->get($this->apiUrl . '/merchant/payment-channel');

        if ($response->successful()) {
            return $response->json('data');
        }

        Log::error('Tripay getPaymentChannels error: ' . $response->body());
        return [];
    }

    /**
     * Create closed payment transaction
     */
    public function createTransaction(string $method, string $merchantRef, int $amount, array $customer, array $orderItems, string $returnUrl)
    {
        $signature = hash_hmac('sha256', $this->merchantCode . $merchantRef . $amount, $this->privateKey);

        // Tripay expired_time: WAJIB Unix timestamp (bukan seconds-from-now).
        // Kita set 24 jam dari sekarang. Tripay hitung mundur dari waktu server mereka.
        $expiredTimestamp = time() + (24 * 60 * 60);

        $payload = [
            'method'         => $method,
            'merchant_ref'   => $merchantRef,
            'amount'         => $amount,
            'customer_name'  => $customer['name'],
            'customer_email' => $customer['email'],
            'customer_phone' => $customer['phone'],
            'order_items'    => $orderItems,
            'return_url'     => $returnUrl,
            'expired_time'   => $expiredTimestamp,
            'signature'      => $signature
        ];

        $response = Http::withToken($this->apiKey)
            ->post($this->apiUrl . '/transaction/create', $payload);

        if ($response->successful()) {
            $data = $response->json('data');
            // Normalisasi expired_time (Unix timestamp dari Tripay) ke Carbon
            // untuk disimpan di kolom expired_at (DATETIME).
            $rawExpiry = $data['expired_time'] ?? null;
            if (is_numeric($rawExpiry)) {
                $data['expired_at_normalized'] = \Carbon\Carbon::createFromTimestamp((int) $rawExpiry);
            }
            return $data;
        }

        Log::error('Tripay createTransaction error: ' . $response->body());
        throw new \Exception('Gagal membuat transaksi ke payment gateway. ' . $response->json('message'));
    }

    /**
     * Validate incoming webhook signature
     */
    public function validateSignature(string $signature, string $jsonPayload): bool
    {
        $generatedSignature = hash_hmac('sha256', $jsonPayload, $this->privateKey);
        return hash_equals($generatedSignature, $signature);
    }

    /**
     * Terapkan status dari Tripay callback ke order di DB.
     * Dipakai oleh callback (real) dan simulateCallback (debug).
     *
     * Return true kalau ada perubahan status yang terjadi.
     */
    public function applyStatusToOrder(Order $order, string $tripayStatus, array $callbackPayload = []): bool
    {
        $payload = array_merge($callbackPayload, [
            'applied_at' => now()->toIso8601String(),
        ]);

        if ($tripayStatus === 'PAID' && $order->status !== 'completed') {
            $order->update([
                'status' => 'completed',
                'paid_at' => now(),
                'callback_payload' => $payload,
            ]);
            return true;
        }

        if (in_array($tripayStatus, ['EXPIRED', 'FAILED'], true) && $order->status === 'pending') {
            $order->update([
                'status' => strtolower($tripayStatus),
                'callback_payload' => $payload,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Simulasi callback Tripay — hanya untuk development/testing.
     * Di-gate oleh APP_DEBUG supaya tidak bisa dipakai di production.
     *
     * Return true kalau simulasi berhasil diterapkan.
     */
    public function simulateCallback(string $orderId, string $status = 'PAID'): bool
    {
        // Hard gate: JANGAN PERNAH aktif di production.
        if (! config('app.debug')) {
            Log::warning('simulateCallback dipanggil tapi APP_DEBUG=false. Ditolak.');
            return false;
        }

        $order = Order::where('order_id', $orderId)->first();
        if (! $order) {
            return false;
        }

        return $this->applyStatusToOrder($order, $status, [
            'merchant_ref' => $order->order_id,
            'simulated' => true,
            'simulated_at' => now()->toIso8601String(),
        ]);
    }
}
