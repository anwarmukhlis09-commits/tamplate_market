<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
    templates: { type: Array, default: () => [] },
});

// ── Category pills
const categories = [
    { name: 'Minimalis', icon: 'M4 6h16M4 12h10M4 18h7', count: 24 },
    { name: 'Modern', icon: 'M13 10V3L4 14h7v7l9-11h-7z', count: 38 },
    { name: 'Gaming', icon: 'M15 7h2a4 4 0 014 4v2a4 4 0 01-4 4h-2v-2h2a2 2 0 002-2v-2a2 2 0 00-2-2h-2V7zM9 7H7a4 4 0 00-4 4v2a4 4 0 004 4h2v-2H7a2 2 0 01-2-2v-2a2 2 0 012-2h2V7z', count: 18 },
    { name: 'Hotel', icon: 'M3 21h18M3 7v14M21 7v14M6 21V11h12v10M9 7V3h6v4M9 11h.01M15 11h.01M9 15h.01M15 15h.01', count: 22 },
    { name: 'Sekolah', icon: 'M12 14l9-5-9-5-9 5 9 5zM12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z', count: 12 },
    { name: 'Voucher', icon: 'M2 9V7a2 2 0 012-2h16a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4zM9 9h6', count: 31 },
    { name: 'Cafe', icon: 'M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8zM6 1v3M10 1v3M14 1v3', count: 15 },
    { name: 'ISP', icon: 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M2 8.82a15 15 0 0120 0M5 12.859a10 10 0 0114 0M8.5 16.429a5 5 0 017 0', count: 9 },
];

// ── Top creators
const topCreators = [
    { name: 'Studio Mikro', avatar: 'SM', templates: 24, sales: 1200, color: 'from-indigo-500 to-violet-500' },
    { name: 'Nanda Pixel', avatar: 'NP', templates: 18, sales: 940, color: 'from-rose-500 to-pink-500' },
    { name: 'Rizky Dev', avatar: 'RD', templates: 15, sales: 720, color: 'from-emerald-500 to-teal-500' },
    { name: 'Andi Craft', avatar: 'AC', templates: 12, sales: 580, color: 'from-amber-500 to-orange-500' },
];

// ── Advantages
const advantages = [
    { title: 'Responsive', desc: 'Tampil sempurna di semua ukuran layar.', icon: 'M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z' },
    { title: 'Mudah Diedit', desc: 'Edit warna, teks, dan logo tanpa coding.', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
    { title: 'Kompatibel RouterOS', desc: 'Berjalan di MikroTik v6 dan v7.', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { title: 'Aman', desc: 'Dukungan CHAP, MAC binding, HTTPS.', icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' },
    { title: 'Dukungan Penuh', desc: 'Tim support siap membantu 24/7.', icon: 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z' },
    { title: 'Update Berkala', desc: 'Template selalu diperbarui dan ditingkatkan.', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' },
];

// ── Stats
const stats = [
    { value: '500+', label: 'Template' },
    { value: '12K+', label: 'Pelanggan' },
    { value: '4.9/5', label: 'Rating' },
    { value: '24/7', label: 'Support' },
];

// ── Cara Kerja steps
const steps = [
    { title: 'Pilih Template', desc: 'Browse katalog, filter berdasarkan kategori, dan pilih template yang paling cocok untuk bisnis Anda.', icon: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z' },
    { title: 'Preview Demo', desc: 'Lihat live preview di device mobile maupun desktop sebelum membeli. Tidak ada kejutan.', icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' },
    { title: 'Checkout', desc: 'Pembayaran aman via Midtrans, DANA, OVO, GoPay, dan transfer bank.', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
    { title: 'Download File ZIP', desc: 'Setelah pembayaran dikonfirmasi, file template langsung tersedia untuk didownload.', icon: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4' },
    { title: 'Upload ke MikroTik', desc: 'Extract file, upload ke MikroTik via Winbox atau Files menu. Selesai dalam 5 menit.', icon: 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12' },
    { title: 'Template Siap Digunakan', desc: 'Login hotspot Anda langsung tampil premium dan profesional. Pelanggan terkesan!', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
];

// ── Helpers
function formatPrice(p) { return 'Rp ' + Number(p).toLocaleString('id-ID'); }
function getBadgeClass(b) {
    const m = {
        'Best Seller': 'bg-amber-50 text-amber-700 border-amber-200',
        'Baru': 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'Populer': 'bg-rose-50 text-rose-700 border-rose-200',
        'Sale': 'bg-red-50 text-red-600 border-red-200',
        'Trending': 'bg-violet-50 text-violet-700 border-violet-200',
        'Premium': 'bg-gradient-to-r from-amber-400 to-orange-500 text-white border-transparent shadow-sm',
        'Gratis': 'bg-emerald-500 text-white border-transparent shadow-sm',
    };
    return m[b] || 'bg-slate-50 text-slate-600 border-slate-200';
}
function getGradient(seed) {
    const gradients = [
        'from-indigo-500 via-purple-500 to-pink-500',
        'from-blue-500 via-indigo-500 to-violet-500',
        'from-emerald-400 via-teal-500 to-cyan-500',
        'from-orange-400 via-rose-500 to-pink-500',
        'from-violet-500 via-purple-500 to-fuchsia-500',
        'from-cyan-400 via-blue-500 to-indigo-500',
    ];
    let h = 0;
    for (let i = 0; i < (seed || 'x').length; i++) h = (h * 31 + seed.charCodeAt(i)) & 0xfffffff;
    return gradients[h % gradients.length];
}
</script>

<template>

<Head title="MarketTemplate — Marketplace Template Hotspot MikroTik #1 di Indonesia" />

<MarketplaceLayout>
    <div class="min-h-screen bg-white text-slate-900 antialiased" style="font-family: 'Inter', 'Poppins', ui-sans-serif, system-ui, sans-serif;">

    <!-- ═══════════════ HERO SECTION ═══════════════ -->
    <section class="relative pt-32 pb-20 sm:pt-40 sm:pb-28 overflow-hidden">

        <!-- Background decoration -->
        <div class="absolute inset-0 pointer-events-none -z-10" aria-hidden="true">
            <div class="absolute -top-40 -right-40 w-[500px] h-[500px] rounded-full bg-gradient-to-br from-indigo-200 via-indigo-50 to-transparent opacity-60 blur-3xl"></div>
            <div class="absolute -bottom-20 -left-40 w-[400px] h-[400px] rounded-full bg-gradient-to-tr from-violet-200 via-purple-50 to-transparent opacity-50 blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:24px_24px] opacity-30"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 lg:gap-20 items-center">

                <!-- LEFT: TEXT -->
                <div class="text-center lg:text-left">

                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-indigo-50 border border-indigo-100 rounded-full text-sm font-medium text-indigo-700 mb-7">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                        </span>
                        Marketplace Template #1 di Indonesia
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.08] mb-6">
                        Template Hotspot <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600">MikroTik</span> Premium untuk Bisnis Anda
                    </h1>

                    <p class="text-lg sm:text-xl text-slate-500 leading-relaxed max-w-xl mx-auto lg:mx-0 mb-9">
                        Siap pakai untuk ISP, hotel, kafe, sekolah, dan hotspot voucher. Tinggal upload ke MikroTik tanpa perlu coding.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center lg:items-start gap-3 mb-12">
                        <Link href="/katalog" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 text-base font-semibold text-white bg-gradient-to-r from-indigo-600 to-violet-600 rounded-xl hover:from-indigo-700 hover:to-violet-700 shadow-xl shadow-indigo-200 transition-all hover:shadow-2xl hover:shadow-indigo-300 hover:-translate-y-0.5">
                            Lihat Template
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </Link>
                        <a href="/template/1" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 text-base font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:border-slate-300 hover:bg-slate-50 transition-all shadow-sm">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Preview Demo
                        </a>
                    </div>

                    <!-- Trust indicators -->
                    <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 mb-12">
                        <li class="flex items-center gap-2.5 text-sm text-slate-600">
                            <span class="shrink-0 w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="font-medium">Siap Upload ke MikroTik</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm text-slate-600">
                            <span class="shrink-0 w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="font-medium">Responsive di Mobile &amp; Desktop</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm text-slate-600">
                            <span class="shrink-0 w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="font-medium">Mudah Dikustomisasi</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm text-slate-600">
                            <span class="shrink-0 w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="font-medium">Update &amp; Support Berkala</span>
                        </li>
                    </ul>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 pt-8 border-t border-slate-200/80">
                        <div v-for="s in stats" :key="s.label" class="text-center lg:text-left">
                            <div class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ s.value }}</div>
                            <div class="text-xs text-slate-500 mt-1 font-medium uppercase tracking-wide">{{ s.label }}</div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: PREMIUM TEMPLATE MOCKUPS (floating cards) -->
                <div class="relative h-[480px] sm:h-[540px]">

                    <!-- Background glow -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="w-[380px] h-[380px] bg-gradient-to-br from-indigo-400 to-violet-500 rounded-full opacity-20 blur-3xl"></div>
                    </div>

                    <!-- Center main mockup (largest, premium hotspot template) -->
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 sm:w-72 z-20">
                        <div class="bg-slate-900 rounded-[2.2rem] p-2.5 shadow-2xl ring-1 ring-white/10">
                            <div class="relative bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 rounded-[1.8rem] aspect-[9/16] overflow-hidden flex flex-col">
                                <!-- Best badge -->
                                <div class="absolute top-3 right-3 px-2 py-1 bg-emerald-500/95 backdrop-blur rounded-md text-[9px] font-bold uppercase tracking-wider text-white z-10">Best</div>
                                <!-- Mini status bar -->
                                <div class="flex items-center justify-between px-5 pt-3.5 text-white/80 text-[10px] font-semibold">
                                    <span>9:41</span>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M17.778 8.222c-4.296-4.296-11.26-4.296-15.556 0A1 1 0 01.808 6.808c5.076-5.077 13.308-5.077 18.384 0a1 1 0 01-1.414 1.414zM14.95 11.05a7 7 0 00-9.9 0 1 1 0 01-1.414-1.414 9 9 0 0112.728 0 1 1 0 01-1.414 1.414zM12.12 13.88a3 3 0 00-4.242 0 1 1 0 01-1.415-1.415 5 5 0 017.072 0 1 1 0 01-1.415 1.415zM9 16a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                                    </div>
                                </div>
                                <!-- Content -->
                                <div class="flex-1 px-5 pt-6 pb-5 text-white flex flex-col items-center text-center justify-center">
                                    <div class="w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-md flex items-center justify-center mb-3 ring-1 ring-white/20">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M2 8.82a15 15 0 0120 0M5 12.859a10 10 0 0114 0"/></svg>
                                    </div>
                                    <p class="text-[9px] uppercase tracking-widest opacity-70 mb-1">Selamat Datang</p>
                                    <h3 class="text-base font-bold mb-0.5">Hotspot Login</h3>
                                    <p class="text-[9px] opacity-80 mb-3">Premium Template</p>
                                    <div class="w-full max-w-[160px] space-y-1.5">
                                        <div class="h-7 bg-white/15 backdrop-blur rounded-md border border-white/20"></div>
                                        <div class="h-7 bg-white/15 backdrop-blur rounded-md border border-white/20"></div>
                                        <div class="h-8 bg-white text-indigo-600 rounded-md font-bold text-[10px] flex items-center justify-center mt-2">Login</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating card: Hotel (top-left, behind) -->
                    <div class="absolute top-4 left-0 w-36 sm:w-40 z-10 transform -rotate-6 hover:rotate-0 transition-transform duration-500">
                        <div class="bg-slate-900 rounded-[1.5rem] p-2 shadow-xl ring-1 ring-white/10">
                            <div class="bg-gradient-to-br from-emerald-400 via-teal-500 to-cyan-600 rounded-[1.1rem] aspect-[9/16] flex flex-col items-center justify-center p-3 text-white relative">
                                <div class="absolute top-2 right-2 px-1.5 py-0.5 bg-white/25 backdrop-blur rounded text-[8px] font-bold tracking-wider">PRO</div>
                                <div class="w-8 h-8 rounded-lg bg-white/20 backdrop-blur flex items-center justify-center mb-1.5 text-xs font-bold">H</div>
                                <p class="text-[11px] font-bold">Hotel</p>
                                <p class="text-[8px] opacity-80">Premium</p>
                            </div>
                        </div>
                    </div>

                    <!-- Floating card: Cafe (top-right, behind) -->
                    <div class="absolute top-12 right-0 w-36 sm:w-40 z-10 transform rotate-6 hover:rotate-0 transition-transform duration-500">
                        <div class="bg-slate-900 rounded-[1.5rem] p-2 shadow-xl ring-1 ring-white/10">
                            <div class="bg-gradient-to-br from-rose-400 via-pink-500 to-fuchsia-600 rounded-[1.1rem] aspect-[9/16] flex flex-col items-center justify-center p-3 text-white relative">
                                <div class="absolute top-2 right-2 px-1.5 py-0.5 bg-white/25 backdrop-blur rounded text-[8px] font-bold tracking-wider">NEW</div>
                                <div class="w-8 h-8 rounded-lg bg-white/20 backdrop-blur flex items-center justify-center mb-1.5 text-xs font-bold">C</div>
                                <p class="text-[11px] font-bold">Cafe</p>
                                <p class="text-[8px] opacity-80">Voucher</p>
                            </div>
                        </div>
                    </div>

                    <!-- Floating card: Voucher (bottom-left) -->
                    <div class="absolute bottom-4 left-8 w-32 sm:w-36 z-10 transform rotate-3 hover:-rotate-2 transition-transform duration-500">
                        <div class="bg-slate-900 rounded-[1.4rem] p-2 shadow-xl ring-1 ring-white/10">
                            <div class="bg-gradient-to-br from-amber-400 via-orange-500 to-red-500 rounded-[1rem] aspect-[9/16] flex flex-col items-center justify-center p-2 text-white">
                                <div class="w-7 h-7 rounded-md bg-white/20 backdrop-blur flex items-center justify-center mb-1.5">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4V6z"/></svg>
                                </div>
                                <p class="text-[10px] font-bold">Voucher</p>
                            </div>
                        </div>
                    </div>

                    <!-- Floating card: Gaming (bottom-right) -->
                    <div class="absolute bottom-8 right-8 w-32 sm:w-36 z-10 transform -rotate-3 hover:rotate-2 transition-transform duration-500">
                        <div class="bg-slate-900 rounded-[1.4rem] p-2 shadow-xl ring-1 ring-white/10">
                            <div class="bg-gradient-to-br from-violet-500 via-purple-600 to-indigo-700 rounded-[1rem] aspect-[9/16] flex flex-col items-center justify-center p-2 text-white">
                                <div class="w-7 h-7 rounded-md bg-white/20 backdrop-blur flex items-center justify-center mb-1.5">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M11 17a1 1 0 102 0v-1a1 1 0 10-2 0v1zM5 17a1 1 0 102 0v-1a1 1 0 10-2 0v1zm5-15a7 7 0 00-7 7c0 2 .5 3.5 1.5 5L3 17h14l-1.5-3c1-1.5 1.5-3 1.5-5a7 7 0 00-7-7z"/></svg>
                                </div>
                                <p class="text-[10px] font-bold">Gaming</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════ CATEGORIES ═══════════════ -->
    <section id="kategori" class="py-12 sm:py-16 bg-slate-50/50 border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between mb-8">
                <div>
                    <p class="text-sm font-semibold text-indigo-600 uppercase tracking-wider mb-2">Kategori Populer</p>
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">Jelajahi berdasarkan kebutuhan</h2>
                </div>
                <a href="/katalog" class="hidden sm:inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                    Lihat semua
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <div class="flex flex-wrap gap-2.5">
                <a v-for="cat in categories" :key="cat.name" href="/katalog" class="group inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 rounded-full text-sm font-medium text-slate-700 hover:border-indigo-500 hover:bg-indigo-50 hover:text-indigo-700 transition-all shadow-sm">
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="cat.icon"/></svg>
                    {{ cat.name }}
                    <span class="text-xs text-slate-400 group-hover:text-indigo-500">({{ cat.count }})</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ═══════════════ TEMPLATE GRID + SIDEBAR ═══════════════ -->
    <section id="templates" class="py-16 sm:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-10">

                <!-- LEFT: TEMPLATE GRID -->
                <div>
                    <div class="flex items-end justify-between mb-6">
                        <div>
                            <p class="text-sm font-semibold text-indigo-600 uppercase tracking-wider mb-2">Template Terbaru</p>
                            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">Pilihan terbaik minggu ini</h2>
                        </div>
                        <div class="hidden sm:flex items-center gap-2">
                            <button class="px-3 py-1.5 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-lg hover:border-slate-300">Terbaru</button>
                            <button class="px-3 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100 rounded-lg">Populer</button>
                            <button class="px-3 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100 rounded-lg">Harga</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                        <Link v-for="t in templates.slice(0, 9)" :key="t.id" :href="'/template/' + t.id" class="group bg-white rounded-2xl border border-slate-200 overflow-hidden hover:border-slate-300 hover:shadow-xl hover:shadow-slate-200/50 transition-all duration-300">
                            <!-- Thumbnail -->
                            <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
                                <div v-if="t.imageUrl" class="w-full h-full">
                                    <img :src="t.imageUrl" :alt="t.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy" />
                                </div>
                                <div v-else class="w-full h-full bg-gradient-to-br" :class="getGradient(t.name)">
                                    <div class="w-full h-full flex flex-col items-center justify-center text-white p-4">
                                        <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center mb-2">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M2 8.82a15 15 0 0120 0M5 12.859a10 10 0 0114 0"/></svg>
                                        </div>
                                        <p class="text-sm font-bold text-center">{{ t.name }}</p>
                                    </div>
                                </div>
                                <!-- Badge -->
                                <div v-if="t.badge" class="absolute top-3 left-3">
                                    <span class="inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-full border" :class="getBadgeClass(t.badge)">{{ t.badge }}</span>
                                </div>
                                <!-- Quick preview on hover -->
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-white/20 backdrop-blur-md rounded-lg">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Preview
                                    </span>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-2 mb-1.5">
                                    <h3 class="font-bold text-slate-900 text-sm leading-snug line-clamp-1 group-hover:text-indigo-600 transition-colors">{{ t.name }}</h3>
                                    <span class="text-sm font-extrabold text-indigo-600 shrink-0">{{ formatPrice(t.discountPrice || t.price) }}</span>
                                </div>
                                <p class="text-xs text-slate-500 mb-3">oleh <span class="font-medium text-slate-700">MarketTemplate Studio</span></p>
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-1 text-amber-500">
                                        <svg class="w-3.5 h-3.5 fill-amber-400" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        <span class="font-semibold text-slate-700">{{ t.rating || '4.8' }}</span>
                                        <span class="text-slate-400">({{ t.sold || 0 }})</span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 uppercase tracking-wider">{{ t.category }}</span>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div v-if="templates.length === 0" class="text-center py-16 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <p class="text-slate-500">Belum ada template yang dipublikasikan.</p>
                    </div>

                    <div class="mt-8 text-center">
                        <Link href="/katalog" class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:border-slate-300 hover:bg-slate-50 transition-all shadow-sm">
                            Lihat semua template
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </Link>
                    </div>
                </div>

                <!-- RIGHT: BANTUAN SIDEBAR -->
                <aside id="bantuan-sidebar" class="space-y-5">

                    <!-- Help CTA -->
                    <div class="bg-gradient-to-br from-indigo-600 to-violet-600 rounded-2xl p-6 text-white shadow-xl shadow-indigo-200/50 relative overflow-hidden">
                        <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                        <div class="relative">
                            <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <h3 class="font-bold text-lg mb-1">Butuh Bantuan?</h3>
                            <p class="text-sm text-white/80 mb-4">Tim support kami siap membantu Anda 24/7 via WhatsApp.</p>
                            <a href="https://wa.me/6281234567890" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white text-indigo-600 rounded-lg text-sm font-semibold hover:bg-indigo-50 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                                Chat WhatsApp
                            </a>
                        </div>
                    </div>

                    <!-- FAQ -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-5">
                        <h3 class="font-bold text-slate-900 text-sm mb-4">FAQ Singkat</h3>
                        <ul class="space-y-3 text-sm">
                            <li>
                                <p class="font-semibold text-slate-800">Cara install?</p>
                                <p class="text-slate-500 text-xs mt-0.5">Upload file ke MikroTik via Winbox Files menu.</p>
                            </li>
                            <li>
                                <p class="font-semibold text-slate-800">Support RouterOS v6/v7?</p>
                                <p class="text-slate-500 text-xs mt-0.5">Ya, semua template support kedua versi.</p>
                            </li>
                            <li>
                                <p class="font-semibold text-slate-800">Bisa request custom?</p>
                                <p class="text-slate-500 text-xs mt-0.5">Bisa! Hubungi kami via WhatsApp untuk diskusi.</p>
                            </li>
                        </ul>
                    </div>

                    <!-- Trust -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-5">
                        <h3 class="font-bold text-slate-900 text-sm mb-4">Lisensi &amp; Kebijakan</h3>
                        <ul class="space-y-2.5 text-xs">
                            <li class="flex items-start gap-2 text-slate-600">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Update gratis 1 tahun</span>
                            </li>
                            <li class="flex items-start gap-2 text-slate-600">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Lisensi untuk 1 bisnis</span>
                            </li>
                            <li class="flex items-start gap-2 text-slate-600">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Garansi uang kembali 7 hari</span>
                            </li>
                            <li class="flex items-start gap-2 text-slate-600">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Support instalasi via WhatsApp</span>
                            </li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <!-- ═══════════════ CARA KERJA ═══════════════ -->
    <section id="cara-kerja" class="py-16 sm:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
                <p class="text-sm font-semibold text-indigo-600 uppercase tracking-wider mb-2">Cara Kerja</p>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-3">Dari Pilih Template sampai Live, 6 Langkah Mudah</h2>
                <p class="text-slate-500 text-lg">Tidak perlu coding. Tidak perlu pusing. Semua sudah kami siapkan.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 relative">
                <!-- Connector line (desktop only) -->
                <div class="hidden lg:block absolute top-12 left-[16%] right-[16%] h-0.5 bg-gradient-to-r from-indigo-200 via-violet-200 to-pink-200 -z-0"></div>

                <div v-for="(step, i) in steps" :key="step.title" class="relative bg-white border border-slate-200 rounded-2xl p-6 hover:border-indigo-200 hover:shadow-xl transition-all group">
                    <!-- Step number -->
                    <div class="absolute -top-4 -left-2 w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white flex items-center justify-center font-extrabold text-sm shadow-lg shadow-indigo-200 ring-4 ring-white">
                        {{ i + 1 }}
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center mb-4 group-hover:bg-indigo-100 transition-colors">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="step.icon"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900 text-lg mb-1.5">{{ step.title }}</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">{{ step.desc }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════ ADVANTAGES ═══════════════ -->
    <section id="keunggulan" class="py-16 sm:py-20 bg-slate-50/50 border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <p class="text-sm font-semibold text-indigo-600 uppercase tracking-wider mb-2">Keunggulan</p>
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-3">Mengapa MarketTemplate?</h2>
                <p class="text-slate-500">Template berkualitas premium dengan dukungan terbaik untuk bisnis Anda.</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
                <div v-for="a in advantages" :key="a.title" class="bg-white rounded-2xl p-6 border border-slate-200 hover:border-indigo-200 hover:shadow-lg transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center mb-4 group-hover:bg-indigo-100 transition-colors">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="a.icon"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900 mb-1.5">{{ a.title }}</h3>
                    <p class="text-sm text-slate-500">{{ a.desc }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════ FOOTER ═══════════════ -->
    <footer id="bantuan" class="bg-slate-900 text-slate-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 mb-10">
                <!-- Newsletter -->
                <div>
                    <div class="flex items-center gap-2.5 mb-4">
                        <img src="/images/logo.png" alt="MarketTemplate" class="h-10 w-auto brightness-0 invert" />
                    </div>
                    <h3 class="text-white font-bold text-xl mb-2">Dapatkan update & promo</h3>
                    <p class="text-slate-400 text-sm mb-4 max-w-md">Template baru, diskon eksklusif, dan tips MikroTik langsung ke email Anda.</p>
                    <form class="flex gap-2 max-w-md">
                        <input type="email" placeholder="email@anda.com" class="flex-1 px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-sm text-white placeholder:text-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none" />
                        <button type="button" class="px-4 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors">Subscribe</button>
                    </form>
                </div>

                <!-- Links -->
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-white font-semibold text-sm mb-4">Bantuan</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white transition-colors">Cara Order</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Instalasi</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">FAQ</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Kontak Support</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold text-sm mb-4">Kebijakan</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white transition-colors">Syarat &amp; Ketentuan</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Kebijakan Privasi</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Kebijakan Refund</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Lisensi</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm">
                <p class="text-slate-500">© 2026 MarketTemplate.id — Semua hak dilindungi.</p>
                <div class="flex items-center gap-4 text-slate-500">
                    <a href="#" class="hover:text-white transition-colors">Twitter</a>
                    <a href="#" class="hover:text-white transition-colors">Instagram</a>
                    <a href="#" class="hover:text-white transition-colors">GitHub</a>
                </div>
            </div>
        </div>
    </footer>
    </div>
</MarketplaceLayout>
</template>
