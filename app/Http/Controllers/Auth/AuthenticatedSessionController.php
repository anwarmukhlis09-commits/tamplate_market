<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

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
