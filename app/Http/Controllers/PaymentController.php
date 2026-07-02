<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\TripayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    protected $tripayService;

    public function __construct(TripayService $tripayService)
    {
        $this->tripayService = $tripayService;
    }

    /**
     * Tampilkan halaman payment untuk order tertentu.
     * Route: GET /payment/{order}
     */
    public function show(Request $request, string $order)
    {
        // Pastikan order ada di DB dan milik user yang sedang login
        $orderModel = Order::where('order_id', $order)->where('user_id', $request->user()?->id)->first();

        if (! $orderModel) {
            abort(403, 'Order tidak ditemukan atau bukan milik Anda.');
        }

        if ($orderModel->status === 'completed') {
            return redirect()->route('payment.success', ['order' => $order]);
        }

        // Kalau order sudah kadaluarsa / gagal, arahkan ke failed page
        if (in_array($orderModel->status, ['expired', 'failed'], true)) {
            return redirect()->route('payment.failed', ['order' => $order]);
        }

        // Ambil payment channels dari Tripay
        $channels = $this->tripayService->getPaymentChannels();

        return Inertia::render('Payment/Show', [
            'orderId' => $order,
            'user' => $request->user(),
            'channels' => $channels,
            'amount' => $orderModel->amount,
        ]);
    }

    /**
     * Proses payment: buat transaksi Tripay dan redirect ke checkout URL.
     * Route: POST /payment/{order}/process
     */
    public function process(Request $request, string $order): RedirectResponse
    {
        $request->validate([
            'method' => 'required|string',
            'phone'  => 'required|string|min:10|max:15',
        ]);

        $orderModel = Order::where('order_id', $order)->where('user_id', $request->user()?->id)->first();

        if (! $orderModel) {
            abort(403, 'Order tidak ditemukan atau bukan milik Anda.');
        }

        if ($orderModel->status === 'completed') {
            return redirect()->route('payment.success', ['order' => $order]);
        }

        $template = $orderModel->template;

        // Normalisasi phone ke format 62xxx (Tripay requirement)
        $phone = preg_replace('/\D/', '', $request->input('phone'));
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (! str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        $customer = [
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'phone' => $phone,
        ];

        $orderItems = [
            [
                'sku' => $template->slug,
                'name' => $template->name,
                'price' => (int) $orderModel->amount,
                'quantity' => 1,
            ]
        ];

        // Return URL → Waiting.vue, BUKAN langsung success. Status real-time
        // diputuskan dari polling di Waiting page, bukan dari return_url.
        $returnUrl = route('payment.waiting', ['order' => $order]);

        try {
            $transaction = $this->tripayService->createTransaction(
                $request->input('method'),
                $orderModel->order_id,
                (int) $orderModel->amount,
                $customer,
                $orderItems,
                $returnUrl
            );

            // Simpan payment method + metadata Tripay ke DB.
            // expired_at_normalized sudah dinormalisasi ke Carbon timestamp oleh service.
            $expiredAt = $transaction['expired_at_normalized'] ?? null;

            $orderModel->update([
                'payment_method' => $request->input('method'),
                'tripay_reference' => $transaction['reference'] ?? null,
                'tripay_checkout_url' => $transaction['checkout_url'] ?? null,
                'tripay_pay_code' => $transaction['pay_code'] ?? null,
                'tripay_pay_url' => $transaction['pay_url'] ?? null,
                'tripay_qr_string' => $transaction['qr_string'] ?? ($transaction['qr_url'] ?? null),
                'expired_at' => $expiredAt,
            ]);

            return redirect($transaction['checkout_url']);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Halaman menunggu pembayaran — polling status real-time.
     * Route: GET /payment/{order}/waiting
     */
    public function waiting(Request $request, string $order): Response|RedirectResponse
    {
        $orderModel = Order::where('order_id', $order)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Shortcut: kalau sudah terminal, langsung redirect
        if ($orderModel->status === 'completed') {
            return redirect()->route('payment.success', ['order' => $order]);
        }
        if (in_array($orderModel->status, ['expired', 'failed'], true)) {
            return redirect()->route('payment.failed', ['order' => $order]);
        }

        return Inertia::render('Payment/Waiting', [
            'orderId' => $order,
            'amount' => (int) $orderModel->amount,
            'paymentMethod' => $orderModel->payment_method,
            'expiredAt' => $orderModel->expired_at?->toIso8601String(),
            'tripayReference' => $orderModel->tripay_reference,
            'tripayCheckoutUrl' => $orderModel->tripay_checkout_url,
            'tripayPayCode' => $orderModel->tripay_pay_code,
            'tripayQrString' => $orderModel->tripay_qr_string,
            'debugEnabled' => config('app.debug'),
            'template' => [
                'id' => $orderModel->template->id,
                'name' => $orderModel->template->name,
            ],
        ]);
    }

    /**
     * Endpoint polling status (return JSON).
     * Route: GET /payment/{order}/status
     */
    public function status(Request $request, string $order): JsonResponse
    {
        $orderModel = Order::where('order_id', $order)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'status' => $orderModel->status,
            'expired_at' => $orderModel->expired_at?->toIso8601String(),
            'is_expired' => $orderModel->isExpired(),
            'paid_at' => $orderModel->paid_at?->toIso8601String(),
        ]);
    }

    /**
     * Halaman pembayaran gagal / kadaluarsa.
     * Route: GET /payment/{order}/failed
     */
    public function failed(Request $request, string $order): \Symfony\Component\HttpFoundation\Response
    {
        $orderModel = Order::where('order_id', $order)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return Inertia::render('Payment/Failed', [
            'orderId' => $order,
            'status' => $orderModel->status,
            'amount' => (int) $orderModel->amount,
            'paymentMethod' => $orderModel->payment_method,
            'template' => [
                'id' => $orderModel->template->id,
                'name' => $orderModel->template->name,
            ],
        ]);
    }

    /**
     * Simulasi callback (HANYA untuk development/testing).
     * Route: POST /payment/{order}/simulate-callback
     * Refused kalau APP_DEBUG=false.
     */
    public function simulateCallback(Request $request, string $order): RedirectResponse
    {
        if (! config('app.debug')) {
            abort(403, 'Simulasi callback hanya tersedia di mode development.');
        }

        $request->validate([
            'status' => 'required|in:PAID,EXPIRED,FAILED',
        ]);

        $orderModel = Order::where('order_id', $order)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $ok = $this->tripayService->simulateCallback(
            $orderModel->order_id,
            $request->input('status')
        );

        if (! $ok) {
            return back()->withErrors(['error' => 'Gagal simulasi callback (order tidak ditemukan atau status tidak valid).']);
        }

        return back()->with('info', "Simulasi callback " . $request->input('status') . " berhasil — order sekarang: " . strtolower($request->input('status')));
    }

    /**
     * Webhook callback dari Tripay.
     * Route: POST /api/tripay/callback
     *
     * Catatan: middleware group = 'api' (NO session, NO CSRF).
     * Jadi cleanup cart TIDAK dilakukan di sini — dipindah ke success()
     * yang jalan di web group (session aktif).
     */
    public function callback(Request $request)
    {
        $jsonPayload = $request->getContent();
        $signature = $request->server('HTTP_X_CALLBACK_SIGNATURE');

        if (! $this->tripayService->validateSignature($signature, $jsonPayload)) {
            Log::warning('Tripay callback: invalid signature');
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        $data = json_decode($jsonPayload);

        if (! isset($data->merchant_ref, $data->status)) {
            return response()->json(['success' => false, 'message' => 'Payload tidak lengkap'], 400);
        }

        $order = Order::where('order_id', $data->merchant_ref)->first();
        if (! $order) {
            Log::warning('Tripay callback: order not found', ['ref' => $data->merchant_ref]);
            return response()->json(['success' => false, 'message' => 'Order tidak ditemukan'], 404);
        }

        $this->tripayService->applyStatusToOrder($order, $data->status, (array) $data);

        return response()->json(['success' => true]);
    }

    /**
     * Tampilkan halaman success setelah payment.
     * Route: GET /payment/{order}/success
     */
    public function success(Request $request, string $order): Response
    {
        $orderModel = Order::where('order_id', $order)->where('user_id', $request->user()?->id)->firstOrFail();

        // Cart cleanup dipindah dari callback() ke sini karena success() ada di
        // web group (session aktif), sedangkan callback() ada di api group (no session).
        if ($orderModel->status === 'completed') {
            $cart = (array) $request->session()->get('cart', []);
            if (in_array($orderModel->template_id, $cart, true)) {
                $cart = array_values(array_diff($cart, [$orderModel->template_id]));
                $request->session()->put('cart', $cart);
            }
        }

        $template = [
            'id' => $orderModel->template->id,
            'name' => $orderModel->template->name,
            'slug' => $orderModel->template->slug,
        ];

        $canEdit = $orderModel->status === 'completed';

        return Inertia::render('Payment/Success', [
            'orderId' => $order,
            'template' => $template,
            'canEdit' => $canEdit,
        ]);
    }
}