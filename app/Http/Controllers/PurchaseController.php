<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Purchase Controller — purchase history per-template.
 */
class PurchaseController extends Controller
{
    public function show(Request $request, int $id): Response
    {
        $template = Template::findOrFail($id);
        $user = $request->user();

        // Cari order yang completed untuk user ini dan template ini
        $order = \App\Models\Order::where('user_id', $user->id)
            ->where('template_id', $template->id)
            ->where('status', 'completed')
            ->first();

        // Jika user bukan admin DAN tidak punya order yang completed untuk template ini, reject!
        if (!$order && !$user->isAdmin()) {
            abort(403, 'Anda belum membeli template ini.');
        }

        // Kalau admin bypass, kita bisa dapet order dummy atau cari order admin_bypass
        if (!$order && $user->isAdmin()) {
            // Cek apakah ada order completed admin_bypass, kalau ga ada bikin dummy
            $order = \App\Models\Order::where('user_id', $user->id)
                ->where('template_id', $template->id)
                ->first();
            
            if (!$order) {
                $order = new \App\Models\Order([
                    'order_id' => 'ADMIN-DUMMY-' . $template->id . '-' . $user->id,
                    'paid_at' => now(),
                    'status' => 'completed',
                    'amount' => 0,
                    'payment_method' => 'admin_bypass',
                ]);
            }
        }

        return Inertia::render('Client/PurchaseDetail', [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'category' => $template->category,
                'price' => $template->price,
            ],
            'purchase' => [
                'orderId' => $order->order_id,
                'date' => $order->paid_at ? $order->paid_at->format('Y-m-d') : ($order->created_at ? $order->created_at->format('Y-m-d') : now()->format('Y-m-d')),
                'status' => $order->status,
            ],
        ]);
    }
}
