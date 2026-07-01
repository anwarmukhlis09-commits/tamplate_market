<?php

namespace App\Http\Middleware;

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
        // Daftar ID template yang sudah dibeli user.
        // SINGLE SOURCE OF TRUTH: dari DB (table orders), BUKAN session.
        // Alasan: session hilang saat logout/login ulang, DB persistent.
        // Fallback ke session untuk backward compat (kalau orders table belum ada).
        $paidTemplates = [];
        $user = $request->user();
        if ($user) {
            try {
                $paidTemplates = \App\Models\Order::getPaidTemplateIds($user->id);
            } catch (\Throwable $e) {
                // Table belum ada (sebelum migration jalan) — fallback ke session
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
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            // Daftar ID template yang sudah dibeli user.
            // Frontend pakai ini untuk ganti tombol "Beli" → "Sudah Dibeli" / "Edit".
            'paidTemplates' => $paidTemplates,
        ];
    }
}
