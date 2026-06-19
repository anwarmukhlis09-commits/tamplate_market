<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     *
     * Urutan prioritas redirect setelah login:
     *   1) `redirect()->intended()` — URL yang coba diakses sebelum auth
     *      (kalau user ke middleware-protected page, Laravel simpan di session).
     *      Kalau intended URL = HOME (/dashboard), TIDAK pakai — anggap
     *      fallback default, lanjut ke #2.
     *   2) Field `redirect` dari form (current page user buka via ?redirect=).
     *      Validasi: hanya path internal, anti open redirect.
     *   3) Fallback ke RouteServiceProvider::HOME = /dashboard.
     *
     * Tujuan: kalau user login dari beranda (tidak ada intended URL),
     * gunakan field `redirect` dari form (= '/') supaya tetap di beranda.
     * Kalau user login dari /dashboard (intended URL), tetap ke /dashboard.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // PENTING: baca intended URL DULU sebelum regenerate session —
        // regenerate() menghapus semua data session termasuk url.intended.
        $intended = $request->session()->get('url.intended');

        $request->session()->regenerate();

        // #1: Cek intended URL (kalau ada DAN BUKAN fallback default /dashboard)
        if ($intended && $intended !== route('dashboard') && $intended !== url('/dashboard')) {
            return redirect()->to($intended);
        }

        // #2: Field redirect dari form (current page, ?redirect= atau window fallback)
        $redirect = $request->input('redirect');
        if (is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect()->to($redirect);
        }

        // #3: Fallback ke /dashboard
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * Bersihkan session + CSRF token + remember-me cookie supaya bersih total
     * dan user bisa login kembali tanpa 419 Page Expired.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // Invalidate session total + regenerate CSRF token (Laravel default)
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Hapus XSRF-TOKEN & remember-me cookie untuk pastikan bersih
        $response = redirect('/');
        $response->withCookie(cookie()->forget('XSRF-TOKEN'));
        $response->withCookie(cookie()->forget(Auth::guard('web')->getRecallerName()));

        return $response;
    }
}
