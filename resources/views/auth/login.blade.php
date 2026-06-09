<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="Content-Security-Policy" content="frame-ancestors 'none'">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/blade.js'])
</head>

<body class="min-h-screen bg-gradient-to-br from-[#0F172A] via-[#312E81] to-[#6D28D9] flex items-center justify-center p-6">

    <div class="w-full max-w-6xl bg-white/95 backdrop-blur-xl rounded-[32px] shadow-2xl p-5 grid grid-cols-1 md:grid-cols-2 gap-5">

        <!-- LEFT FORM -->
        <div class="flex flex-col justify-center px-8 py-12 md:px-20">
            <div class="text-center mb-10">
                <div class="mx-auto mb-4 w-14 h-14 rounded-2xl bg-gradient-to-br from-[#5B4CF0] to-[#8B5CF6] flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-indigo-500/30">
                    MT
                </div>

                <h1 class="text-3xl font-bold text-slate-900 mb-3">
                    Selamat Datang Kembali!
                </h1>

                <p class="text-sm text-slate-500">
                    Masuk untuk mengelola template hotspot Anda
                </p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            {{-- Banner session expired (dari CSRF handler) --}}
            @if (session('session_expired'))
                <div class="mb-4 flex items-start gap-2.5 p-3.5 bg-amber-50 border border-amber-200 rounded-xl">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="text-xs text-amber-800">
                        <strong class="font-semibold">Sesi Anda telah berakhir.</strong>
                        <span class="block mt-0.5 text-amber-700">Silakan login kembali untuk melanjutkan.</span>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">
                        Email
                    </label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Masukkan email Anda"
                        class="w-full rounded-xl border border-slate-200 bg-white px-5 py-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                    >

                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div x-data="{ showPassword: false }">
                    <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">
                        Password
                    </label>

                    <div class="relative">
                        <input
                            id="password"
                            x-ref="passwordInput"
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan password Anda"
                            class="w-full rounded-xl border border-slate-200 bg-white pl-5 pr-12 py-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                        >

                        <button
                            type="button"
                            @click="showPassword = !showPassword; $nextTick(() => $refs.passwordInput.focus())"
                            :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                            :title="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                            class="absolute inset-y-0 right-0 z-10 flex items-center justify-center w-12 text-slate-400 hover:text-indigo-600 active:text-indigo-700 transition-colors rounded-r-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                        >
                            {{-- Eye (show) --}}
                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>

                            {{-- Eye-slash (hide) --}}
                            <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-slate-500">
                        <input
                            type="checkbox"
                            name="remember"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        Ingat saya
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm font-medium text-indigo-600 hover:underline">
                            Lupa password?
                        </a>
                    @endif
                </div>

                <button
                    type="submit"
                    class="w-full rounded-xl bg-indigo-600 py-4 text-white font-semibold shadow-lg shadow-indigo-500/30 hover:bg-indigo-700 transition"
                >
                    Masuk
                </button>
            </form>

            <p class="mt-8 text-center text-sm text-slate-500">
                Belum punya akun?
                <a href="{{ route('register') }}" class="inline-block font-semibold text-indigo-600 hover:text-indigo-800 underline underline-offset-2 cursor-pointer select-none py-1">
                    Daftar sekarang
                </a>
            </p>
        </div>

        <!-- RIGHT IMAGE -->
        <div class="hidden md:block relative rounded-[26px] overflow-hidden min-h-[600px] bg-cover bg-center"
             style="background-image: url('/images/login-illustration.jpg');">

            <div class="absolute inset-0 bg-gradient-to-b from-black/5 via-transparent to-black/30"></div>

            <div class="absolute bottom-10 left-10 right-10 text-white">
                <p class="text-3xl font-bold leading-tight">
                    Template Login Hotspot Profesional
                </p>
                <p class="text-sm opacity-90 mt-3">
                    Siap pakai untuk MikroTik, Hotel, Cafe, ISP dan Voucher WiFi.
                </p>
            </div>
        </div>

    </div>

</body>
</html>