<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="Content-Security-Policy" content="frame-ancestors 'none'">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terjadi Kesalahan — MarketTemplate</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gradient-to-br from-[#0F172A] via-[#312E81] to-[#6D28D9] flex items-center justify-center p-6 antialiased" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

    <div class="w-full max-w-2xl bg-white/95 backdrop-blur-xl rounded-[32px] shadow-2xl p-10 sm:p-14 text-center">

        <div class="flex justify-center mb-6">
            <img src="/images/logo.png" alt="MarketTemplate" class="h-14 w-auto" />
        </div>

        <div class="relative w-32 h-32 mx-auto mb-6">
            <div class="absolute inset-0 bg-gradient-to-br from-rose-500 to-orange-500 rounded-3xl opacity-20 blur-2xl"></div>
            <div class="relative w-full h-full bg-gradient-to-br from-rose-500 to-orange-500 rounded-3xl flex items-center justify-center shadow-xl shadow-rose-200/50">
                <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
        </div>

        <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-3">Terjadi Kesalahan Server</h1>
        <p class="text-slate-500 mb-8 max-w-md mx-auto">
            Maaf, ada masalah teknis di sisi kami. Tim kami sudah diberitahu dan sedang menangani. Silakan coba lagi beberapa saat.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="/" class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Beranda
            </a>
            <a href="https://wa.me/6281234567890" class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-emerald-600 bg-white border-2 border-emerald-200 rounded-xl hover:bg-emerald-50 transition-all">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                Hubungi Support
            </a>
        </div>
    </div>

</body>
</html>
