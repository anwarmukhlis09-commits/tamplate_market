<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminTransactionController extends Controller
{
    /**
     * Halaman index admin transaksi.
     * Route: GET /admin/transactions
     */
    public function index(Request $request): Response
    {
        $orders = $this->buildOrdersQuery($request)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Transactions', [
            'transactions' => $orders,
            'stats' => $this->computeStats(),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * JSON endpoint untuk polling stats real-time (tiap 5 detik dari frontend).
     * Route: GET /admin/transactions/stats
     */
    public function stats(): JsonResponse
    {
        return response()->json($this->computeStats());
    }

    /**
     * Bangun query orders dengan filter search + status + eager-load relasi.
     * Reuse pattern dari AdminTemplateController::index (addcslashes LIKE escape
     * + where closure multi-column + status whitelist).
     */
    private function buildOrdersQuery(Request $request)
    {
        $query = Order::query()
            ->with(['user:id,name,email', 'template:id,name,slug,category'])
            ->latest('created_at');

        if ($search = trim((string) $request->query('search'))) {
            $q = addcslashes($search, '%_\\');
            $query->where(function ($sql) use ($q) {
                $sql->where('order_id', 'like', "%{$q}%")
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                    ->orWhereHas('template', fn($t) => $t->where('name', 'like', "%{$q}%"));
            });
        }

        if ($status = $request->query('status')) {
            // Whitelist status supaya user tidak bisa filter dengan nilai ngawur
            $allowed = ['pending', 'completed', 'expired', 'failed'];
            if (in_array($status, $allowed, true)) {
                $query->where('status', $status);
            }
        }

        return $query;
    }

    /**
     * Hitung ringkasan untuk 4 stat cards.
     * - total_revenue: SUM amount dari order completed
     * - paid_count: COUNT order completed
     * - pending_count: COUNT order pending
     * - failed_count: COUNT order failed/expired (gabung biar cuma 4 card)
     * - last_updated: timestamp untuk client display
     *
     * Catatan: pakai (clone $base) sebelum where() karena query builder mutable —
     * tanpa clone, where chain akan nempel dan reused query akan salah.
     */
    private function computeStats(): array
    {
        $base = Order::query();

        return [
            'total_revenue' => (float) (clone $base)->where('status', 'completed')->sum('amount'),
            'paid_count'    => (clone $base)->where('status', 'completed')->count(),
            'pending_count' => (clone $base)->where('status', 'pending')->count(),
            'failed_count'  => (clone $base)->whereIn('status', ['failed', 'expired'])->count(),
            'last_updated'  => now()->toIso8601String(),
        ];
    }
}