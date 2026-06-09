<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="Content-Security-Policy" content="frame-ancestors 'none'">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Tidak Ditemukan — MarketTemplate</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gradient-to-br from-[#0F172A] via-[#312E81] to-[#6D28D9] flex items-center justify-center p-6 antialiased" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

    <div class="w-full max-w-2xl bg-white/95 backdrop-blur-xl rounded-[32px] shadow-2xl p-10 sm:p-14 text-center">

        <!-- Logo -->
        <div class="flex justify-center mb-6">
            <img src="/images/logo.png" alt="MarketTemplate" class="h-14 w-auto" />
        </div>

        <!-- 404 illustration -->
        <div class="relative w-32 h-32 mx-auto mb-6">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-3xl opacity-20 blur-2xl"></div>
            <div class="relative w-full h-full bg-gradient-to-br from-indigo-600 to-violet-600 rounded-3xl flex items-center justify-center shadow-xl shadow-indigo-200/50">
                <span class="text-white font-extrabold text-5xl tracking-tighter">404</span>
            </div>
        </div>

        <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-3">Halaman Tidak Ditemukan</h1>
        <p class="text-slate-500 mb-8 max-w-md mx-auto">
            Maaf, halaman yang Anda cari tidak ada atau sudah dipindahkan. Coba kembali ke beranda atau jelajahi katalog template kami.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="/" class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Beranda
            </a>
            <a href="/katalog" class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-indigo-600 bg-white border-2 border-indigo-200 rounded-xl hover:bg-indigo-50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Jelajahi Katalog
            </a>
        </div>

        <div class="mt-8 pt-6 border-t border-slate-100">
            <p class="text-xs text-slate-400">
                Butuh bantuan? Hubungi kami di
                <a href="https://wa.me/6281234567890" class="text-indigo-600 font-medium hover:underline">WhatsApp Support</a>
            </p>
        </div>
    </div>

</body>
</html>
