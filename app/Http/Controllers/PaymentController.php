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

        return redirect()->route('payment.success', ['order' => $order])
            ->with('success', 'Pembayaran berhasil!');
    }

    /**
     * Tampilkan halaman success setelah payment.
     * Route: GET /payment/{order}/success
     */
    public function success(Request $request, string $order): Response
    {
        return Inertia::render('Payment/Success', [
            'orderId' => $order,
            'template' => ['id' => null, 'name' => 'Template Premium'], // Placeholder
        ]);
    }
}
