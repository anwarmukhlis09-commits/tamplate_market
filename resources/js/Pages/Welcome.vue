<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { templates as templatesData } from '@/data/templates.js';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});

const templates = ref(templatesData.slice(0, 6));

const features = [
    { icon: 'palette', title: 'Desain Premium', desc: 'Tampilan modern & profesional untuk meningkatkan kepercayaan pelanggan WiFi Anda.' },
    { icon: 'devices', title: 'Fully Responsive', desc: 'Tampil sempurna di semua perangkat — HP, tablet, laptop, maupun desktop.' },
    { icon: 'bolt', title: 'Instalasi Mudah', desc: 'Cukup upload ke MikroTik, salin file, dan langsung berfungsi tanpa konfigurasi rumit.' },
    { icon: 'shield', title: 'Login Aman', desc: 'Dukungan penuh untuk login via voucher, username/password, dan MAC address.' },
    { icon: 'search', title: 'SEO Friendly', desc: 'Halaman login dioptimasi agar mudah terindeks dan ditemukan di mesin pencari.' },
    { icon: 'headset', title: 'Support Prioritas', desc: 'Bantuan instalasi gratis, konsultasi, dan update template berkala untuk semua pelanggan.' },
];

const testimonials = [
    { name: 'Ahmad Fauzi', role: 'Pemilik Warkop WiFi', text: 'Template-nya keren! Pelanggan saya jadi betah karena tampilan login WiFi terlihat profesional dan mahal.', rating: 5 },
    { name: 'Dian Permata', role: 'Network Admin Hotel', text: 'Instalasinya sangat cepat, supportnya ramah dan responsif. Login hotspot hotel saya sekarang terlihat premium.', rating: 5 },
    { name: 'Rudi Hartono', role: 'Teknisi Freelance', text: 'Langganan di sini buat semua client. Banyak pilihan template dan harganya sangat terjangkau.', rating: 5 },
];

const faq = [
    { q: 'Apakah template bisa dipasang sendiri?', a: 'Tentu! Setiap template dilengkapi panduan instalasi bergambar. Rata-rata hanya perlu 5 menit sampai berfungsi.' },
    { q: 'Support untuk MikroTik versi berapa?', a: 'Semua template kami mendukung MikroTik RouterOS versi 6 dan versi 7. Kompatibel dengan semua tipe RB.' },
    { q: 'Apakah bisa request desain custom?', a: 'Bisa! Silakan hubungi kami untuk mendiskusikan kebutuhan desain custom sesuai brand dan bisnis Anda.' },
    { q: 'Bagaimana cara mendapatkan template?', a: 'Setelah pembayaran terkonfirmasi, file template langsung dikirim ke email Anda beserta link download-nya.' },
];

function formatPrice(p) { return 'Rp ' + p.toLocaleString('id-ID'); }
function getBadgeClass(b) {
    const m = { 'Best Seller': 'bg-amber-50 text-amber-700 border-amber-200', 'Baru': 'bg-emerald-50 text-emerald-700 border-emerald-200', 'Populer': 'bg-rose-50 text-rose-700 border-rose-200', 'Sale': 'bg-red-50 text-red-600 border-red-200', 'Seasonal': 'bg-orange-50 text-orange-700 border-orange-200', 'Trending': 'bg-violet-50 text-violet-700 border-violet-200' };
    return m[b] || 'bg-slate-50 text-slate-600 border-slate-200';
}
function featIcon(icon) {
    const m = { palette: 'M4 16l3-5 2.5 3.5L12 10l4 6H4zM19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z', devices: 'M4 6h16M4 12h16m-7 6h7', bolt: 'M13 10V3L4 14h7v7l9-11h-7z', shield: 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z', search: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', headset: 'M3 11v2a9 9 0 0018 0v-2m-3 4.5A2.5 2.5 0 0115.5 13h-1a1.5 1.5 0 01-1.5-1.5v-2A1.5 1.5 0 0114.5 8h1A2.5 2.5 0 0118 10.5m-12 0A2.5 2.5 0 018.5 8h1A1.5 1.5 0 0111 9.5v2A1.5 1.5 0 019.5 13h-1A2.5 2.5 0 016 10.5z' };
    return m[icon] || '';
}
</script>

<template>
    <Head title="MarketTemplate — Jual Template Login Hotspot MikroTik" />

    <div class="min-h-screen bg-white antialiased">

        <!-- ═══════════════ NAVBAR ═══════════════ -->
        <header class="fixed top-0 inset-x-0 z-50 bg-white/80 backdrop-blur-xl border-b border-slate-200/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <Link href="/" class="flex items-center gap-2.5 shrink-0 group">
                        <div class="w-9 h-9 bg-gradient-to-br from-indigo-600 to-violet-600 rounded-xl flex items-center justify-center shadow-md shadow-indigo-200 group-hover:shadow-lg group-hover:scale-105 transition-all">
                            <span class="text-white font-extrabold text-sm">MT</span>
                        </div>
                        <span class="font-bold text-lg text-slate-900 tracking-tight">Market<span class="text-indigo-600">Template</span></span>
                    </Link>
                    <nav class="hidden md:flex items-center gap-1">
                        <Link href="/katalog" class="px-3.5 py-2 text-sm font-medium text-slate-500 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">Katalog</Link>
                        <a href="#templates" class="px-3.5 py-2 text-sm font-medium text-slate-500 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">Template</a>
                        <a href="#features" class="px-3.5 py-2 text-sm font-medium text-slate-500 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">Fitur</a>
                        <a href="#testimonial" class="px-3.5 py-2 text-sm font-medium text-slate-500 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">Testimoni</a>
                    </nav>
                    <div v-if="canLogin" class="flex items-center gap-2.5">
                        <template v-if="$page.props.auth.user">
                            <Link :href="route('dashboard')" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm transition-all">Dashboard</Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="px-3.5 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Login</Link>
                            <Link v-if="canRegister" :href="route('register')" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-md shadow-indigo-200 transition-all">Register</Link>
                        </template>
                    </div>
                </div>
            </div>
        </header>

        <!-- ═══════════════ HERO SECTION ═══════════════ -->
        <section class="relative pt-28 pb-16 sm:pt-36 sm:pb-24 overflow-hidden">

            <!-- Background decoration -->
            <div class="absolute inset-0 pointer-events-none -z-10" aria-hidden="true">
                <!-- Top-right blur -->
                <div class="absolute -top-40 -right-40 w-[500px] h-[500px] rounded-full bg-gradient-to-br from-indigo-100 via-indigo-50 to-transparent opacity-60 blur-3xl"></div>
                <!-- Bottom-left blur -->
                <div class="absolute -bottom-20 -left-40 w-[400px] h-[400px] rounded-full bg-gradient-to-tr from-violet-100 via-purple-50 to-transparent opacity-50 blur-3xl"></div>
                <!-- Subtle dot grid -->
                <div class="absolute inset-0 bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:20px_20px] opacity-40"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">

                    <!-- ===== LEFT: TEXT ===== -->
                    <div class="text-center lg:text-left">

                        <!-- Live badge -->
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-indigo-50 border border-indigo-100 rounded-full text-sm font-medium text-indigo-700 mb-6">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                            </span>
                            Template Hotspot #1 di Indonesia
                        </div>

                        <!-- Headline -->
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.08] mb-6">
                            Login <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600">Hotspot</span> Profesional, Tanpa Ribet
                        </h1>

                        <!-- Subheadline -->
                        <p class="text-lg sm:text-xl text-slate-500 leading-relaxed max-w-xl mx-auto lg:mx-0 mb-8">
                            Koleksi template login MikroTik premium untuk kafe, hotel, sekolah, dan bisnis Anda. Tinggal upload, langsung pakai.
                        </p>

                        <!-- CTA Row -->
                        <div class="flex flex-col sm:flex-row items-center lg:items-start gap-3 mb-10">
                            <Link href="/katalog" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3.5 text-base font-semibold text-white bg-gradient-to-r from-indigo-600 to-violet-600 rounded-xl hover:from-indigo-700 hover:to-violet-700 shadow-xl shadow-indigo-200 transition-all hover:shadow-2xl hover:shadow-indigo-300 hover:-translate-y-0.5 gap-2">
                                Jelajahi Katalog
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </Link>
                            <a href="#templates" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3.5 text-base font-semibold text-slate-700 bg-white border-2 border-slate-200 rounded-xl hover:border-slate-300 hover:bg-slate-50 transition-all gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Lihat Demo
                            </a>
                        </div>

                        <!-- Trust / Stats row -->
                        <div class="flex flex-col sm:flex-row items-center lg:items-start gap-8 pt-6 border-t border-slate-100">

                            <!-- Avatar group -->
                            <div class="flex items-center">
                                <div class="flex -space-x-2.5">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 ring-2 ring-white flex items-center justify-center text-white text-xs font-bold">AF</div>
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 ring-2 ring-white flex items-center justify-center text-white text-xs font-bold">DP</div>
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 ring-2 ring-white flex items-center justify-center text-white text-xs font-bold">RH</div>
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-violet-400 to-purple-500 ring-2 ring-white flex items-center justify-center text-white text-xs font-bold">+</div>
                                </div>
                                <div class="ml-3 text-sm">
                                    <div class="font-bold text-slate-900">500+</div>
                                    <div class="text-slate-400 text-xs">Pelanggan Aktif</div>
                                </div>
                            </div>

                            <!-- Mini stats -->
                            <div class="flex items-center gap-6">
                                <div class="text-center">
                                    <div class="text-lg font-extrabold text-slate-900">4.9</div>
                                    <div class="text-xs text-slate-400 flex items-center gap-0.5 justify-center">
                                        <svg v-for="i in 5" :key="i" class="w-3 h-3 text-amber-400 fill-amber-400" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-extrabold text-slate-900">100+</div>
                                    <div class="text-xs text-slate-400">Template</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-extrabold text-slate-900">24/7</div>
                                    <div class="text-xs text-slate-400">Support</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== RIGHT: MOCKUP PREVIEW ===== -->
                    <div class="relative flex justify-center lg:justify-end">
                        <!-- Glow behind mockup -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-72 h-72 bg-gradient-to-br from-indigo-400 to-violet-500 rounded-full opacity-20 blur-3xl"></div>
                        </div>

                        <!-- Phone mockup frame -->
                        <div class="relative z-10">
                            <!-- Outer phone frame -->
                            <div class="w-[280px] sm:w-[320px] bg-slate-900 rounded-[3rem] p-3 shadow-2xl shadow-slate-400/30 ring-1 ring-slate-300/20">
                                <!-- Notch -->
                                <div class="flex justify-center mb-3">
                                    <div class="w-20 h-5 bg-slate-800 rounded-full"></div>
                                </div>
                                <!-- Screen -->
                                <div class="bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-700 rounded-[2rem] overflow-hidden aspect-[9/16] relative">
                                    <!-- Template content mockup -->
                                    <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center">
                                        <!-- Logo placeholder -->
                                        <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.07c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0"/></svg>
                                        </div>
                                        <!-- Business name -->
                                        <div class="w-32 h-4 bg-white/30 rounded-full mb-2"></div>
                                        <!-- Running text -->
                                        <div class="w-40 h-2.5 bg-white/15 rounded-full mb-6"></div>
                                        <!-- Login form mockup -->
                                        <div class="w-full space-y-2.5">
                                            <div class="w-full h-9 bg-white/15 rounded-xl"></div>
                                            <div class="w-full h-9 bg-white/15 rounded-xl"></div>
                                            <div class="w-full h-9 bg-white/30 rounded-xl mt-2 flex items-center justify-center">
                                                <span class="text-white/80 text-xs font-semibold">Login Hotspot</span>
                                            </div>
                                        </div>
                                        <!-- Voucher cards -->
                                        <div class="grid grid-cols-3 gap-2 mt-5 w-full">
                                            <div class="h-14 bg-white/10 rounded-xl"></div>
                                            <div class="h-14 bg-white/10 rounded-xl"></div>
                                            <div class="h-14 bg-white/15 rounded-xl relative">
                                                <span class="absolute -top-1.5 right-1 text-[7px] font-bold text-white bg-amber-400 px-1.5 py-0.5 rounded">BEST</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Home bar -->
                                <div class="flex justify-center mt-3">
                                    <div class="w-24 h-1 bg-slate-700 rounded-full"></div>
                                </div>
                            </div>

                            <!-- Floating badge: Preview -->
                            <div class="absolute -top-3 -right-3 bg-white rounded-2xl shadow-lg border border-slate-100 px-3.5 py-2 flex items-center gap-2 text-xs font-semibold text-slate-700 animate-[bounce_3s_ease-in-out_infinite]">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Live Preview
                            </div>

                            <!-- Floating badge: MikroTik -->
                            <div class="absolute -bottom-2 -left-2 bg-white rounded-xl shadow-lg border border-slate-100 px-3 py-1.5 flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                                <div class="w-2 h-2 bg-emerald-500 rounded-full"></div>
                                MikroTik v6 & v7 Ready
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════ TRUST BAR ═══════════════ -->
        <div class="border-y border-slate-100 bg-slate-50/50 py-8 px-4">
            <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-center gap-6 sm:gap-12 text-center">
                <div class="flex items-center gap-2 text-sm text-slate-400 font-medium">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Garansi 7 Hari Uang Kembali
                </div>
                <div class="flex items-center gap-2 text-sm text-slate-400 font-medium">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Update Gratis Seumur Hidup
                </div>
                <div class="flex items-center gap-2 text-sm text-slate-400 font-medium">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    Support Prioritas 24/7
                </div>
            </div>
        </div>

        <!-- ═══════════════ TEMPLATE GRID ═══════════════ -->
        <section id="templates" class="py-20 sm:py-24 bg-slate-50 px-4">
            <div class="max-w-7xl mx-auto">
                <div class="text-center max-w-2xl mx-auto mb-14">
                    <span class="inline-block px-3 py-1 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-full uppercase tracking-wide mb-4">Template Terpopuler</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-4">Pilih Template Hotspot Anda</h2>
                    <p class="text-lg text-slate-500">Koleksi template login hotspot MikroTik terbaik. Semua responsive, mudah dipasang, dan siap digunakan.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link v-for="tpl in templates" :key="tpl.id" :href="'/template/' + tpl.id" class="group flex flex-col bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-xl hover:border-indigo-200 hover:-translate-y-1 transition-all duration-300">
                        <div :class="tpl.image" class="relative h-48 flex items-center justify-center shrink-0">
                            <span class="text-white/15 text-8xl font-black select-none">{{ tpl.name.charAt(0) }}</span>
                            <span v-if="tpl.badge" class="absolute top-3 right-3 inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg border" :class="getBadgeClass(tpl.badge)">{{ tpl.badge }}</span>
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center">
                                <span class="px-5 py-2.5 bg-white/90 backdrop-blur text-slate-900 font-semibold text-sm rounded-xl opacity-0 group-hover:opacity-100 transition-all translate-y-2 group-hover:translate-y-0 shadow-lg">Lihat Detail</span>
                            </div>
                        </div>
                        <div class="flex flex-col flex-1 p-5">
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <h3 class="font-bold text-slate-900 leading-snug">{{ tpl.name }}</h3>
                                <span class="font-extrabold text-indigo-600 text-sm whitespace-nowrap">{{ formatPrice(tpl.price) }}</span>
                            </div>
                            <div class="flex flex-wrap gap-1.5 mb-auto">
                                <span v-for="f in tpl.features.slice(0, 3)" :key="f" class="text-xs text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg font-medium">{{ f }}</span>
                            </div>
                            <div class="mt-4 pt-4 border-t border-slate-100">
                                <span class="block w-full py-2.5 text-sm font-semibold text-center text-indigo-600 bg-indigo-50 rounded-xl group-hover:bg-indigo-100 transition-colors">Beli Sekarang</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <div class="text-center mt-12">
                    <Link href="/katalog" class="inline-flex items-center px-6 py-3 text-sm font-semibold text-slate-700 bg-white border-2 border-slate-200 rounded-xl hover:border-indigo-300 hover:text-indigo-600 transition-all gap-2">
                        Lihat Semua Template
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ═══════════════ FEATURES ═══════════════ -->
        <section id="features" class="py-20 sm:py-24 px-4">
            <div class="max-w-7xl mx-auto">
                <div class="text-center max-w-2xl mx-auto mb-14">
                    <span class="inline-block px-3 py-1 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-full uppercase tracking-wide mb-4">Mengapa Kami</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-4">Dibuat untuk Kemudahan Anda</h2>
                    <p class="text-lg text-slate-500">Kami berkomitmen memberikan template berkualitas tinggi dengan pengalaman terbaik.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="feat in features" :key="feat.title" class="group p-6 sm:p-8 rounded-2xl border border-slate-200 hover:border-indigo-200 hover:shadow-lg hover:bg-slate-50/50 transition-all">
                        <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-5 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="featIcon(feat.icon)"/></svg>
                        </div>
                        <h3 class="font-bold text-slate-900 text-lg mb-2">{{ feat.title }}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">{{ feat.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════ CTA BANNER ═══════════════ -->
        <section class="py-16 px-4">
            <div class="max-w-5xl mx-auto bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-700 rounded-3xl p-10 sm:p-14 md:p-16 text-center relative overflow-hidden shadow-2xl shadow-indigo-300/40">
                <div class="absolute top-0 right-0 w-96 h-96 bg-white/[0.05] rounded-full -translate-y-1/2 translate-x-1/2"></div>
                <div class="absolute bottom-0 left-0 w-72 h-72 bg-white/[0.05] rounded-full translate-y-1/2 -translate-x-1/2"></div>
                <div class="absolute top-1/2 left-1/4 w-48 h-48 bg-white/[0.03] rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                <div class="relative z-10">
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-4">Siap Upgrade Tampilan Hotspot Anda?</h2>
                    <p class="text-indigo-100 text-lg max-w-xl mx-auto mb-8">Dapatkan diskon <strong class="text-white">20%</strong> untuk pembelian pertama. Gunakan kode <code class="px-2.5 py-1 bg-white/20 rounded-lg text-white font-bold text-sm">HOTSPOT20</code></p>
                    <Link href="/katalog" class="inline-flex items-center px-8 py-3.5 text-base font-semibold text-indigo-700 bg-white rounded-xl hover:bg-slate-100 shadow-xl transition-all hover:-translate-y-0.5 hover:shadow-2xl gap-2">
                        Belanja Sekarang
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ═══════════════ TESTIMONI ═══════════════ -->
        <section id="testimonial" class="py-20 sm:py-24 bg-slate-50 px-4">
            <div class="max-w-7xl mx-auto">
                <div class="text-center max-w-2xl mx-auto mb-14">
                    <span class="inline-block px-3 py-1 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-full uppercase tracking-wide mb-4">Testimoni</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-4">Dipercaya Ratusan Pelanggan</h2>
                    <p class="text-lg text-slate-500">Bergabung dengan 500+ pemilik bisnis yang telah menggunakan template kami.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="t in testimonials" :key="t.name" class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 hover:shadow-lg transition-shadow">
                        <div class="flex gap-1 mb-4 text-amber-400">
                            <svg v-for="i in t.rating" :key="i" class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        </div>
                        <blockquote class="text-slate-600 text-sm leading-relaxed mb-5">"{{ t.text }}"</blockquote>
                        <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-violet-500 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0">{{ t.name.charAt(0) }}</div>
                            <div>
                                <div class="font-semibold text-slate-900 text-sm">{{ t.name }}</div>
                                <div class="text-xs text-slate-500">{{ t.role }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════ FAQ ═══════════════ -->
        <section id="faq" class="py-20 sm:py-24 px-4">
            <div class="max-w-3xl mx-auto">
                <div class="text-center mb-14">
                    <span class="inline-block px-3 py-1 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-full uppercase tracking-wide mb-4">FAQ</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-4">Pertanyaan Umum</h2>
                </div>
                <div class="space-y-3">
                    <details v-for="(item, i) in faq" :key="i" class="group bg-white border border-slate-200 rounded-2xl transition-all duration-200">
                        <summary class="flex items-center justify-between cursor-pointer font-semibold text-slate-900 list-none px-6 py-5 hover:text-indigo-600 transition-colors">
                            {{ item.q }}
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="px-6 pb-5">
                            <p class="text-slate-500 text-sm leading-relaxed">{{ item.a }}</p>
                        </div>
                    </details>
                </div>
            </div>
        </section>

        <!-- ═══════════════ FOOTER ═══════════════ -->
        <footer class="bg-slate-900 text-slate-400 py-14 px-4">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-10">
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-2.5 mb-3">
                            <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-violet-500 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold text-sm">MT</span>
                            </div>
                            <span class="font-bold text-lg text-white tracking-tight">Market<span class="text-indigo-400">Template</span></span>
                        </div>
                        <p class="text-sm text-slate-400 max-w-sm leading-relaxed">Penyedia template login hotspot MikroTik terbaik di Indonesia. Desain profesional, pemasangan mudah, harga terjangkau.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white text-sm mb-4">Menu</h4>
                        <ul class="space-y-2.5 text-sm">
                            <li><Link href="/katalog" class="text-slate-400 hover:text-white transition-colors">Katalog Template</Link></li>
                            <li><a href="#features" class="text-slate-400 hover:text-white transition-colors">Fitur Unggulan</a></li>
                            <li><a href="#testimonial" class="text-slate-400 hover:text-white transition-colors">Testimoni</a></li>
                            <li><a href="#faq" class="text-slate-400 hover:text-white transition-colors">FAQ</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white text-sm mb-4">Kontak</h4>
                        <ul class="space-y-2.5 text-sm text-slate-400">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                support@markettemplate.id
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                WA: 0812-XXXX-XXXX
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-slate-800 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-500">
                    <p>&copy; {{ new Date().getFullYear() }} MarketTemplate. All rights reserved.</p>
                    <p>Laravel v{{ laravelVersion }} &middot; PHP v{{ phpVersion }}</p>
                </div>
            </div>
        </footer>
    </div>
</template>
