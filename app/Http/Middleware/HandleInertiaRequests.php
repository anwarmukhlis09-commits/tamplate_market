<?php

namespace App\Http\Middleware;

use App\Models\Order;
use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $isAdmin = $user && $user->isAdmin();

        // Daftar ID template yang sudah dibeli user.
        // SINGLE SOURCE OF TRUTH: dari DB (table orders), BUKAN session.
        // Alasan: session hilang saat logout/login ulang, DB persistent.
        // Fallback ke session untuk backward compat (kalau orders table belum ada).
        //
        // Admin bypass: anggap admin sudah "punya" semua template published.
        // isPaid() di Vue akan return true untuk semua template IDs → badge
        // "Sudah Dibeli" muncul, tombol "Beli" tersembunyi, "Download" muncul.
        $paidTemplates = [];
        if ($isAdmin) {
            // Eager load only IDs (lightweight query — no full model load)
            $paidTemplates = Template::where('status', 'published')
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();
        } elseif ($user) {
            try {
                $paidTemplates = Order::getPaidTemplateIds($user->id);
            } catch (\Throwable $e) {
                $paidTemplates = (array) $request->session()->get('paid_templates', []);
            }
        } else {
            // Guest: pakai session kalau ada (mis. demo flow tanpa login)
            $paidTemplates = (array) $request->session()->get('paid_templates', []);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                // Global flag untuk Vue — admin bisa akses semua fitur gratis,
                // skip form payment, dll. Cek di Vue: $page.props.auth.isAdmin
                'isAdmin' => (bool) $isAdmin,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            // Daftar ID template yang sudah dibeli user (atau semua untuk admin).
            // Frontend pakai ini untuk ganti tombol "Beli" → "Sudah Dibeli" / "Edit".
            'paidTemplates' => $paidTemplates,
        ];
    }
}