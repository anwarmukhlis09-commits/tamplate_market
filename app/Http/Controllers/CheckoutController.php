<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Template;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Checkout Controller — semua proses WAJIB scope ke {id} template
 * untuk menghindari data bentrok/tertukar antar template.
 */
class CheckoutController extends Controller
{
    /**
     * Tampilkan halaman checkout untuk template tertentu.
     * Route: GET /checkout/{id}
     */
    public function show(Request $request, int $id): Response|RedirectResponse
    {
        $template = Template::where('status', 'published')->findOrFail($id);
        $user = $request->user();

        // Admin bypass: skip form checkout, auto-create completed order + redirect
        // ke template detail (yang punya tombol Download). Konsisten dengan admin
        // experience (semua fitur gratis) + audit trail di DB.
        if ($user && $user->isAdmin()) {
            $this->autoCreateAdminOrder($user, $template);
            // Pakai route() ke template download — admin otomatis ke halaman
            // download, atau fallback ke template detail URL. Route /template/{id}
            // adalah closure tanpa name, jadi pakai URL path langsung.
            return redirect("/template/{$template->id}")
                ->with('info', 'Admin mode: akses template gratis.');
        }

        // Auto-add template ke cart. Alur normal: user dari editor/download
        // di-redirect ke /checkout/{id} — di titik ini user BELUM add-to-cart
        // via endpoint /cart/{id}. Tanpa auto-add, PaymentController::process
        // akan reject dengan 403 "template tidak ada di keranjang" karena
        // validasi cart membership (lihat fix A6 security).
        $cart = (array) $request->session()->get('cart', []);
        if (! in_array($template->id, $cart, true)) {
            $cart[] = $template->id;
            $request->session()->put('cart', $cart);
        }

        return Inertia::render('Checkout/Show', [
            'template' => $this->transformTemplate($template),
            'auth' => ['user' => $user],
        ]);
    }

    /**
     * Proses order: buat Order baru (status=pending) & redirect ke payment.
     * Route: POST /checkout/{id}
     */
    public function process(Request $request, int $id): RedirectResponse
    {
        $template = Template::where('status', 'published')->findOrFail($id);
        $user = $request->user();

        // Admin bypass: skip payment flow, auto-create completed order + redirect
        // ke success page (yang akan auto-trigger download).
        if ($user && $user->isAdmin()) {
            $order = $this->autoCreateAdminOrder($user, $template);
            return redirect()->route('payment.success', ['order' => $order->order_id])
                ->with('success', 'Admin mode: template otomatis terbuka.');
        }

        // Safety: kalau show() di-skip (mis. POST langsung), tetap add ke cart
        $cart = (array) $request->session()->get('cart', []);
        if (! in_array($template->id, $cart, true)) {
            $cart[] = $template->id;
            $request->session()->put('cart', $cart);
        }

        if (Order::isUserPaid($user->id, $template->id)) {
            $existingOrder = Order::where('user_id', $user->id)
                ->where('template_id', $template->id)
                ->where('status', 'completed')
                ->first();
            return redirect()->route('payment.success', ['order' => $existingOrder->order_id])
                ->with('info', 'Anda sudah membeli template ini.');
        }

        $orderId = 'ORD-' . now()->format('Ymd') . '-' . $template->id . '-' . $user->id;

        Order::updateOrCreate(
            [
                'user_id' => $user->id,
                'template_id' => $template->id,
            ],
            [
                'order_id' => $orderId,
                'status' => 'pending',
                'amount' => $template->price,
            ]
        );

        return redirect()->route('payment.show', ['order' => $orderId])
            ->with('success', 'Order dibuat. Silakan pilih metode pembayaran.');
    }

    /**
     * Auto-create completed order untuk admin (audit trail + muncul di
     * /admin/transactions dengan payment_method='admin_bypass').
     * Idempotent: kalau sudah ada order untuk user+template, no-op.
     */
    private function autoCreateAdminOrder(User $user, Template $template): Order
    {
        return Order::updateOrCreate(
            ['user_id' => $user->id, 'template_id' => $template->id],
            [
                'order_id' => 'ADMIN-' . now()->format('Ymd') . '-' . $template->id . '-' . $user->id,
                'status' => 'completed',
                'amount' => 0,
                'payment_method' => 'admin_bypass',
                'paid_at' => now(),
            ]
        );
    }

    private function transformTemplate(Template $t): array
    {
        return [
            'id' => $t->id,
            'name' => $t->name,
            'slug' => $t->slug,
            'category' => $t->category,
            'price' => $t->price,
            'discountPrice' => $t->discount_price,
            'shortDesc' => $t->short_desc,
            'imageUrl' => $t->preview_image ? asset('storage/' . $t->preview_image) : null,
            'showcaseImageUrl' => $t->showcase_image ? asset('storage/' . $t->showcase_image) : null,
            'rating' => (float) $t->rating,
            'sold' => $t->sold_count,
        ];
    }
}