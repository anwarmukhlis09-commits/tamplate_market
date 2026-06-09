<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        TokenMismatchException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Handle CSRF token mismatch (419 Page Expired) gracefully.
        // Redirect user ke halaman login dengan flash message,
        // supaya tidak menampilkan error page Laravel yang jelek.
        $this->renderable(function (TokenMismatchException $e, Request $request) {
            // Bersihkan session & cookie untuk hindari loop
            $request->session()->flush();
            $request->session()->regenerate();

            // Untuk request JSON, return 419
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi telah berakhir. Silakan login kembali.'], 419);
            }

            // Untuk request web, redirect ke login dengan flash
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Sesi Anda telah berakhir atau token tidak valid. Silakan login kembali.'])
                ->with('session_expired', true);
        });
    }
}
