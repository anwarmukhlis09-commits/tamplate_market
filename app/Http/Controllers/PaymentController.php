<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Payment Controller — payment gateway simulation.
 * Route {order} berisi order ID, scope ke order spesifik.
 */
class PaymentController extends Controller
{
    /**
     * Tampilkan halaman payment untuk order tertentu.
     * Route: GET /payment/{order}
     */
    public function show(Request $request, string $order): Response
    {
        return Inertia::render('Payment/Show', [
            'orderId' => $order,
            'user' => $request->user(),
        ]);
    }

    /**
     * Proses payment (simulasi — produksi: integrasikan Midtrans).
     * Route: POST /payment/{order}/process
     */
    public function process(Request $request, string $order): RedirectResponse
    {
        // Placeholder: integrasikan dengan Midtrans API
        // $response = Midtrans::charge([...]);

        // Parse template_id dari order ID (format: ORD-YYYYMMDD-{template_id}-{user_id})
        // lalu simpan di session paid_templates supaya download diizinkan.
        if (preg_match('/^ORD-\d{8}-(\d+)-\d+$/', $order, $m)) {
            $templateId = (int) $m[1];
            $paid = (array) $request->session()->get('paid_templates', []);
            if (! in_array($templateId, $paid, true)) {
                $paid[] = $templateId;
                $request->session()->put('paid_templates', $paid);
            }
        }

        return redirect()->route('payment.success', ['order' => $order])
            ->with('success', 'Pembayaran berhasil!');
    }

    /**
     * Tampilkan halaman success setelah payment.
     * Route: GET /payment/{order}/success
     */
    public function success(Request $request, string $order): Response
    {
        // Parse template_id dari order ID (format: ORD-YYYYMMDD-{template_id}-{user_id})
        $template = ['id' => null, 'name' => 'Template', 'slug' => null];
        if (preg_match('/^ORD-\d{8}-(\d+)-\d+$/', $order, $m)) {
            $t = \App\Models\Template::find((int) $m[1]);
            if ($t) {
                $template = [
                    'id' => $t->id,
                    'name' => $t->name,
                    'slug' => $t->slug,
                ];
            }
        }

        return Inertia::render('Payment/Success', [
            'orderId' => $order,
            'template' => $template,
            'canEdit' => $request->user() && (int) ($request->user()->id ?? 0) > 0,
        ]);
    }
}
