<?php

namespace App\Http\Controllers;

use App\Models\Template;
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
    public function show(Request $request, int $id): Response
    {
        $template = Template::where('status', 'published')->findOrFail($id);

        return Inertia::render('Checkout/Show', [
            'template' => $this->transformTemplate($template),
            'auth' => ['user' => $request->user()],
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

        // Placeholder: kalau ada model Order, simpan di sini
        // Order::create([...])
        // Untuk sekarang, simulate order ID dan redirect ke payment
        $orderId = 'ORD-' . now()->format('Ymd') . '-' . $template->id . '-' . $user->id;

        return redirect()->route('payment.show', ['order' => $orderId])
            ->with('success', 'Order dibuat. Silakan selesaikan pembayaran.');
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
            'rating' => (float) $t->rating,
            'sold' => $t->sold_count,
        ];
    }
}
