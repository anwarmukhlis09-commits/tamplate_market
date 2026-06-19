<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Payment Controller — payment gateway simulation.
 * Route {order} berisi order ID, scope ke order spesifik.
 *
 * SECURITY: Pada produksi, /process TIDAK boleh dipakai untuk mark-as-paid dari
 * client. Payment callback HARUS datang dari server gateway (Midtrans webhook)
 * yang sudah verify signature & status transaksi. Endpoint ini sementara
 * untuk simulasi & dev — TETAP ada validasi ownership supaya attacker tidak
 * bisa mark template ID sembarang sebagai paid.
 */
class PaymentController extends Controller
{
    /**
     * Tampilkan halaman payment untuk order tertentu.
     * Route: GET /payment/{order}
     */
    public function show(Request $request, string $order): Response
    {
        // Pastikan order milik user yang sedang login
        if (! $this->orderBelongsToUser($order, $request->user()?->id)) {
            abort(403, 'Order tidak ditemukan atau bukan milik Anda.');
        }

        return Inertia::render('Payment/Show', [
            'orderId' => $order,
            'user' => $request->user(),
        ]);
    }

    /**
     * Proses payment (simulasi — produksi: integrasikan Midtrans callback).
     * Route: POST /payment/{order}/process
     */
    public function process(Request $request, string $order): RedirectResponse
    {
        // Placeholder: integrasikan dengan Midtrans API
        // $response = Midtrans::charge([...]);
        // Pada produksi, payment status di-set dari webhook Midtrans, BUKAN dari
        // request ini. Endpoint ini sementara diizinkan dengan strict validation
        // supaya user demo bisa alur lengkap.

        if (! preg_match('/^ORD-\d{8}-(\d+)-(\d+)$/', $order, $m)) {
            abort(400, 'Format order ID tidak valid.');
        }

        $templateId = (int) $m[1];
        $orderUserId = (int) $m[2];

        // Validasi 1: order harus milik user yang sedang login
        if (! $request->user() || (int) $request->user()->id !== $orderUserId) {
            abort(403, 'Order ini bukan milik Anda.');
        }

        // Validasi 2: template harus exist
        if (! \App\Models\Template::where('id', $templateId)->exists()) {
            abort(404, 'Template tidak ditemukan.');
        }

        // Validasi 3: pastikan template ada di cart user (anti-spam mark-paid
        // untuk template yang user tidak niat beli)
        $cart = (array) $request->session()->get('cart', []);
        if (! in_array($templateId, $cart, true)) {
            abort(403, 'Template ini tidak ada di keranjang Anda.');
        }

        // Tandai sebagai paid di session
        $paid = (array) $request->session()->get('paid_templates', []);
        if (! in_array($templateId, $paid, true)) {
            $paid[] = $templateId;
            $request->session()->put('paid_templates', $paid);
        }

        // Bersihkan dari cart
        $cart = array_values(array_diff($cart, [$templateId]));
        $request->session()->put('cart', $cart);

        return redirect()->route('payment.success', ['order' => $order])
            ->with('success', 'Pembayaran berhasil!');
    }

    /**
     * Tampilkan halaman success setelah payment.
     * Route: GET /payment/{order}/success
     */
    public function success(Request $request, string $order): Response
    {
        // Validasi ownership (template info bersifat publik, tapi order tidak)
        $template = ['id' => null, 'name' => 'Template', 'slug' => null];
        $templateId = null;
        if (preg_match('/^ORD-\d{8}-(\d+)-(\d+)$/', $order, $m)) {
            $templateId = (int) $m[1];
            $orderUserId = (int) $m[2];

            // Ownership check: hanya owner order yang bisa lihat success page detail
            if (! $request->user() || (int) $request->user()->id !== $orderUserId) {
                abort(403, 'Order ini bukan milik Anda.');
            }

            $t = \App\Models\Template::find($templateId);
            if ($t) {
                $template = [
                    'id' => $t->id,
                    'name' => $t->name,
                    'slug' => $t->slug,
                ];
            }
        }

        // canEdit: true kalau template ada di paid_templates session user
        $paid = (array) $request->session()->get('paid_templates', []);
        $canEdit = $templateId !== null && in_array($templateId, $paid, true);

        return Inertia::render('Payment/Success', [
            'orderId' => $order,
            'template' => $template,
            'canEdit' => $canEdit,
        ]);
    }

    /**
     * Cek apakah order (ORD-...) milik user tertentu.
     */
    private function orderBelongsToUser(string $order, ?int $userId): bool
    {
        if (! $userId) {
            return false;
        }
        if (preg_match('/^ORD-\d{8}-(\d+)-(\d+)$/', $order, $m)) {
            return (int) $m[2] === $userId;
        }
        return false;
    }
}
