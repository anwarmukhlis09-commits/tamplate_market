<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    template: { type: Object, default: null },
    canLogin: Boolean,
});

const template = computed(() => props.template);
const activePreview = ref(0);
const isZoomed = ref(false);
const previewMode = ref('desktop'); // 'mobile' | 'desktop'
const activeEditorTab = ref('umum');

function formatPrice(p) { return 'Rp ' + Number(p).toLocaleString('id-ID'); }

function getTemplateGradient(name) {
    const gradients = [
        'from-indigo-500 via-purple-500 to-pink-500',
        'from-blue-500 via-indigo-500 to-violet-500',
        'from-emerald-400 via-teal-500 to-cyan-500',
        'from-orange-400 via-rose-500 to-pink-500',
        'from-violet-500 via-purple-500 to-fuchsia-500',
        'from-cyan-400 via-blue-500 to-indigo-500',
    ];
    let h = 0;
    for (let i = 0; i < (name || 'x').length; i++) h = (h * 31 + name.charCodeAt(i)) & 0xfffffff;
    return gradients[h % gradients.length];
}

function shareTemplate() {
    if (navigator.share) { navigator.share({ title: template.value?.name + ' — MarketTemplate', url: window.location.href }); }
    else { navigator.clipboard.writeText(window.location.href); alert('Link berhasil disalin!'); }
}

const editorTabs = [
    { value: 'umum', label: 'Umum', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z' },
    { value: 'logo', label: 'Logo & Branding', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { value: 'warna', label: 'Warna', icon: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-1.657 1.657m-4.99-1.243a2 2 0 01-1.414 0l-1.414-1.414a2 2 0 010-2.828l1.414-1.414a2 2 0 011.414 0l1.414 1.414a2 2 0 010 2.828l-1.414 1.414a2 2 0 01-1.414 0z' },
    { value: 'login', label: 'Halaman Login', icon: 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1' },
    { value: 'voucher', label: 'Paket Voucher', icon: 'M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4V6z' },
    { value: 'background', label: 'Background', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { value: 'footer', label: 'Footer', icon: 'M4 5h16M4 9h16M4 13h16M4 17h10' },
    { value: 'css', label: 'Custom CSS', icon: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4' },
];

const sellingPoints = [
    { icon: '⚡', title: '1 Klik Upload ke MikroTik', desc: 'Cukup upload file ZIP, langsung aktif. Tidak perlu konfigurasi manual.' },
    { icon: '👁️', title: 'Live Preview Realtime', desc: 'Lihat perubahan desain secara instan saat mengedit di editor.' },
    { icon: '📱', title: 'Mobile & Desktop Responsive', desc: 'Tampil sempurna di semua ukuran layar — HP, tablet, laptop, desktop.' },
    { icon: '🛡️', title: 'Aman & Terpercaya', desc: 'Dilengkapi CHAP, MAC binding, dan HTTPS. Aman untuk produksi.' },
];

const beforeFeatures = [
    { text: 'Editor terkunci', muted: true },
    { text: 'Hanya bisa preview template', muted: true },
];

const afterFeatures = [
    { text: 'Semua fitur editor aktif', highlight: true },
    { text: 'Preview realtime', highlight: true },
    { text: 'Upload logo', highlight: true },
    { text: 'Edit warna', highlight: true },
    { text: 'Edit voucher', highlight: true },
    { text: 'Download ZIP', highlight: true },
];

const lockedFeatures = [
    { label: 'Upload Logo', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { label: 'Ubah Warna', icon: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-1.657 1.657m-4.99-1.243a2 2 0 01-1.414 0l-1.414-1.414a2 2 0 010-2.828l1.414-1.414a2 2 0 011.414 0l1.414 1.414a2 2 0 010 2.828l-1.414 1.414a2 2 0 01-1.414 0z' },
    { label: 'Ubah Nama ISP', icon: 'M3 5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm2 0h14v3H5V5zm0 5h4v3H5v-3zm6 0h8v3h-8v-3zm-6 5h4v3H5v-3zm6 0h8v3h-8v-3z' },
    { label: 'Paket Voucher', icon: 'M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4V6z' },
    { label: 'Upload Background', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { label: 'Custom CSS', icon: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4' },
    { label: 'Download ZIP', icon: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4' },
];
</script>

<template>
<Head :title="template ? template.name + ' — MarketTemplate' : 'Template Tidak Ditemukan'" />

<div class="min-h-screen bg-slate-50 antialiased" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

    <!-- ════════════════ NAVBAR ════════════════ -->
    <header class="sticky top-0 z-50 bg-white/85 backdrop-blur-xl border-b border-slate-200/60 shadow-sm shadow-slate-200/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-6 h-16">
                <Link href="/" class="flex items-center gap-2.5 shrink-0 group">
                    <img src="/images/logo.png" alt="MarketTemplate" class="h-10 w-auto group-hover:scale-105 transition-transform" />
                </Link>
                <nav class="hidden md:flex items-center gap-1">
                    <Link href="/" class="px-3.5 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">Beranda</Link>
                    <Link href="/katalog" class="px-3.5 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">Template</Link>
                    <a href="/#cara-kerja" class="px-3.5 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">Cara Kerja</a>
                    <a href="/#bantuan" class="px-3.5 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">Bantuan</a>
                </nav>
                <div class="flex items-center gap-2.5 shrink-0">
                    <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')" class="hidden sm:inline-flex px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-200 transition-all">Sign In</Link>
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-100 rounded-xl transition-colors">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center text-white text-sm font-bold">
                            {{ $page.props.auth.user.name?.charAt(0) || 'U' }}
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </header>

    <!-- ════════════════ NOT FOUND ════════════════ -->
    <div v-if="!template" class="max-w-2xl mx-auto px-4 py-32 text-center">
        <div class="text-6xl mb-5">😕</div>
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Template Tidak Ditemukan</h1>
        <p class="text-slate-500 mb-8">Template yang Anda cari tidak tersedia atau sudah dihapus.</p>
        <Link href="/katalog" class="inline-flex items-center px-6 py-3 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-sm transition-colors">← Kembali ke Katalog</Link>
    </div>

    <!-- ════════════════ CONTENT ════════════════ -->
    <div v-else class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-slate-400 mb-6">
            <Link href="/" class="hover:text-indigo-600 transition-colors">Beranda</Link>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <Link href="/katalog" class="hover:text-indigo-600 transition-colors">Template</Link>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-700 font-semibold truncate">{{ template.name }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

            <!-- ════ LEFT: PREVIEW GALLERY ════ -->
            <div class="lg:col-span-3 space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                    <!-- Main preview -->
                    <div class="relative aspect-[4/3] overflow-hidden" :class="getTemplateGradient(template.name)" @click="isZoomed = true">
                        <div class="absolute inset-6 sm:inset-10 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 flex flex-col items-center justify-center p-6 text-white">
                            <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center mb-3 ring-1 ring-white/20">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M2 8.82a15 15 0 0120 0M5 12.859a10 10 0 0114 0"/></svg>
                            </div>
                            <p class="text-[10px] uppercase tracking-widest opacity-80 mb-0.5">Selamat Datang</p>
                            <p class="text-xl font-bold mb-1 text-center">{{ template.name }}</p>
                            <p class="text-[10px] opacity-80 mb-4">Premium Hotspot Template</p>
                            <div class="w-full max-w-[200px] space-y-2">
                                <div class="h-9 bg-white/15 backdrop-blur rounded-lg border border-white/20"></div>
                                <div class="h-9 bg-white/15 backdrop-blur rounded-lg border border-white/20"></div>
                                <div class="h-10 bg-white text-indigo-700 rounded-lg font-bold text-xs flex items-center justify-center mt-2">LOGIN</div>
                            </div>
                        </div>
                        <!-- Floating MikroTik badge -->
                        <div class="absolute top-4 left-4 inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-900/85 backdrop-blur rounded-lg text-[10px] font-bold text-white">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM10 18a8 8 0 100-16 8 8 0 000 16z"/></svg>
                            MikroTik
                        </div>
                        <div class="absolute top-4 right-4 inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-amber-500/95 backdrop-blur rounded-lg text-[10px] font-bold text-white">
                            ⭐ Premium
                        </div>
                        <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <button @click.stop="previewMode = 'mobile'" :class="previewMode === 'mobile' ? 'bg-white text-slate-900' : 'bg-black/40 text-white hover:bg-black/60'" class="backdrop-blur px-3 py-1.5 rounded-full text-xs font-semibold transition-colors">📱 Mobile</button>
                                <button @click.stop="previewMode = 'desktop'" :class="previewMode === 'desktop' ? 'bg-white text-slate-900' : 'bg-black/40 text-white hover:bg-black/60'" class="backdrop-blur px-3 py-1.5 rounded-full text-xs font-semibold transition-colors">🖥️ Desktop</button>
                            </div>
                            <span class="bg-black/40 backdrop-blur px-3 py-1.5 rounded-full text-xs font-medium text-white">Klik untuk zoom</span>
                        </div>
                    </div>
                    <!-- Thumbnail carousel -->
                    <div class="flex gap-2 p-3 border-t border-slate-100 bg-slate-50/50 overflow-x-auto">
                        <button v-for="i in 5" :key="i" @click="activePreview = i - 1"
                            :class="['bg-gradient-to-br', getTemplateGradient(template.name + i), 'w-16 h-12 rounded-lg border-2 transition-all shrink-0', activePreview === i - 1 ? 'border-indigo-600 ring-2 ring-indigo-200 shadow-sm' : 'border-slate-200 opacity-70 hover:opacity-100']" />
                    </div>
                </div>
            </div>

            <!-- ════ RIGHT: INFO & PRICE ════ -->
            <div class="lg:col-span-2 space-y-5">
                <!-- Header info -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold bg-gradient-to-r from-amber-400 to-orange-500 text-white rounded-lg uppercase tracking-wider">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            Premium
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold bg-slate-100 text-slate-700 rounded-md capitalize">{{ template.category }}</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight mb-3">{{ template.name }}</h1>
                    <p class="text-slate-500 text-sm leading-relaxed mb-5">{{ template.longDesc || template.shortDesc }}</p>

                    <!-- Stats row -->
                    <div class="grid grid-cols-3 gap-3 pb-5 mb-5 border-b border-slate-100">
                        <div class="text-center">
                            <div class="flex items-center justify-center gap-1 text-amber-500 mb-1">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <span class="font-extrabold text-slate-900 text-sm">{{ template.rating }}</span>
                            </div>
                            <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Rating</p>
                        </div>
                        <div class="text-center">
                            <p class="font-extrabold text-slate-900 text-sm mb-1">{{ template.sold || 0 }}</p>
                            <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Sales</p>
                        </div>
                        <div class="text-center">
                            <p class="font-extrabold text-slate-900 text-sm mb-1">{{ Math.round((template.sold || 0) * 3.2) }}</p>
                            <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Downloads</p>
                        </div>
                    </div>

                    <!-- Price + CTA -->
                    <div class="flex items-end gap-2 mb-5">
                        <span class="text-4xl font-extrabold text-indigo-600 tracking-tight">{{ formatPrice(template.price) }}</span>
                        <span v-if="template.discountPrice && template.discountPrice > template.price" class="text-base text-slate-400 line-through mb-1">{{ formatPrice(template.discountPrice) }}</span>
                        <span v-if="template.discountPrice && template.discountPrice > template.price" class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md mb-1">HEMAT</span>
                    </div>

                    <button class="w-full py-4 text-base font-bold text-white bg-gradient-to-r from-indigo-600 to-violet-600 rounded-2xl hover:from-indigo-700 hover:to-violet-700 shadow-xl shadow-indigo-200 transition-all hover:-translate-y-0.5 hover:shadow-2xl flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Beli Sekarang
                    </button>
                    <Link :href="'/template/' + template.id + '/preview'" class="mt-2.5 w-full py-3.5 text-sm font-semibold text-indigo-600 bg-white border-2 border-indigo-200 rounded-2xl hover:bg-indigo-50 transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Live Preview
                    </Link>

                    <div class="flex gap-2 mt-3">
                        <button @click="shareTemplate" class="flex-1 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                            Bagikan
                        </button>
                        <button class="flex-1 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            Wishlist
                        </button>
                    </div>
                </div>

                <!-- Meta info -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <h3 class="font-bold text-slate-900 text-sm mb-3">Informasi Template</h3>
                    <dl class="space-y-2.5 text-xs">
                        <div class="flex justify-between"><dt class="text-slate-500">Kompatibilitas</dt><dd class="font-semibold text-slate-800">RouterOS v6 &amp; v7</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Lisensi</dt><dd class="font-semibold text-slate-800">1 Bisnis, Seumur Hidup</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Update Terakhir</dt><dd class="font-semibold text-slate-800">{{ template.updatedAt }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Update Berikutnya</dt><dd class="font-semibold text-slate-800">Gratis 1 tahun</dd></div>
                    </dl>
                </div>

                <!-- Feature badges -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <h3 class="font-bold text-slate-900 text-sm mb-3">Fitur</h3>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="f in template.features" :key="f" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold bg-indigo-50 text-indigo-700 rounded-lg">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            {{ f }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════ EDITOR SECTION ════════════════ -->
        <section class="mt-16">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <p class="text-sm font-semibold text-indigo-600 uppercase tracking-wider mb-2">Editor Template</p>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-3">Kustomisasi Template dengan Mudah</h2>
                <p class="text-slate-500">Editor visual yang powerful — tanpa coding. Atur warna, logo, voucher, dan lainnya.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- ════ LEFT: LOCKED FEATURES CARD ════ -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-slate-900 text-lg">Template Editor</h3>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold bg-amber-100 text-amber-800 rounded-full">
                            🔒 Locked
                        </span>
                    </div>
                    <p class="text-sm text-slate-500 mb-5">Editor tersedia setelah pembelian.</p>

                    <ul class="space-y-2.5 mb-6">
                        <li v-for="f in lockedFeatures" :key="f.label" class="flex items-center gap-2.5 text-sm text-slate-400">
                            <span class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="f.icon"/></svg>
                            </span>
                            <span>🔒 {{ f.label }}</span>
                        </li>
                    </ul>

                    <button class="w-full py-3.5 text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-violet-600 rounded-xl hover:from-indigo-700 hover:to-violet-700 shadow-lg shadow-indigo-200 transition-all hover:-translate-y-0.5">
                        🔓 Beli untuk Membuka Editor
                    </button>

                    <div class="mt-5 p-4 bg-gradient-to-br from-indigo-50 to-violet-50 border border-indigo-100 rounded-xl">
                        <p class="text-xs text-slate-600 leading-relaxed">
                            <span class="font-bold text-indigo-700">💎 Premium benefit:</span>
                            Setelah pembelian Anda dapat mengkustomisasi template dan mengunduh file siap upload ke MikroTik.
                        </p>
                    </div>
                </div>

                <!-- ════ RIGHT: COMPARISON BEFORE/AFTER ════ -->
                <div class="lg:col-span-2 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Before -->
                        <div class="bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="w-8 h-8 rounded-lg bg-slate-200 flex items-center justify-center text-slate-500 text-xs font-bold">✕</span>
                                <h4 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Sebelum Beli</h4>
                            </div>
                            <ul class="space-y-2.5">
                                <li v-for="f in beforeFeatures" :key="f.text" class="flex items-start gap-2 text-sm text-slate-400">
                                    <svg class="w-4 h-4 text-slate-300 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <span class="line-through">{{ f.text }}</span>
                                </li>
                            </ul>
                        </div>
                        <!-- After -->
                        <div class="bg-gradient-to-br from-indigo-50 to-violet-50 border-2 border-indigo-200 rounded-2xl p-5 relative overflow-hidden">
                            <div class="absolute -top-8 -right-8 w-24 h-24 bg-indigo-200/30 rounded-full blur-2xl"></div>
                            <div class="flex items-center gap-2 mb-4 relative">
                                <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center text-white text-xs font-bold">✓</span>
                                <h4 class="font-bold text-indigo-700 text-sm uppercase tracking-wider">Sesudah Beli</h4>
                            </div>
                            <ul class="space-y-2.5 relative">
                                <li v-for="f in afterFeatures" :key="f.text" class="flex items-start gap-2 text-sm text-slate-800 font-medium">
                                    <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    {{ f.text }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ════════════════ EDITOR PREVIEW (after purchase) ════════════════ -->
        <section class="mt-12">
            <div class="text-center max-w-2xl mx-auto mb-6">
                <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Tampilan Editor (Preview)</h2>
                <p class="text-sm text-slate-500 mt-1.5">Setelah pembelian, Anda akan mendapatkan akses ke editor ini.</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden">
                <!-- Editor chrome -->
                <div class="bg-slate-100 border-b border-slate-200 px-4 py-2.5 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-red-400"></span>
                    <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                    <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                    <div class="flex-1 mx-3 bg-white border border-slate-200 rounded-md px-3 py-1 text-[10px] text-slate-400 flex items-center gap-1.5">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        markettemplate.id/editor/{{ template.slug || template.id }}
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-[180px_1fr_240px]">
                    <!-- Left sidebar -->
                    <aside class="bg-slate-50 border-r border-slate-200 p-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-2">Menu Editor</p>
                        <ul class="space-y-1">
                            <li v-for="tab in editorTabs" :key="tab.value">
                                <button @click="activeEditorTab = tab.value"
                                    :class="['w-full flex items-center gap-2.5 px-3 py-2 text-xs font-medium rounded-lg text-left transition-colors', activeEditorTab === tab.value ? 'bg-indigo-100 text-indigo-700' : 'text-slate-600 hover:bg-slate-100']">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="tab.icon"/></svg>
                                    {{ tab.label }}
                                </button>
                            </li>
                        </ul>
                    </aside>

                    <!-- Center: live preview -->
                    <div class="p-6 bg-slate-100 flex items-center justify-center min-h-[340px]">
                        <div class="bg-white rounded-2xl shadow-2xl ring-4 ring-slate-200 max-w-[220px] w-full aspect-[9/16] overflow-hidden">
                            <div class="w-full h-full bg-gradient-to-br flex flex-col items-center justify-center p-4 text-white" :class="getTemplateGradient(template.name)">
                                <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center mb-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M2 8.82a15 15 0 0120 0M5 12.859a10 10 0 0114 0"/></svg>
                                </div>
                                <p class="text-[9px] uppercase tracking-widest opacity-80 mb-0.5">Selamat Datang</p>
                                <p class="text-sm font-bold mb-0.5 text-center">{{ template.name }}</p>
                                <p class="text-[9px] opacity-80 mb-2">Live Preview</p>
                                <div class="w-full max-w-[140px] space-y-1">
                                    <div class="h-5 bg-white/15 backdrop-blur rounded border border-white/20"></div>
                                    <div class="h-5 bg-white/15 backdrop-blur rounded border border-white/20"></div>
                                    <div class="h-6 bg-white text-indigo-700 rounded text-[8px] font-bold flex items-center justify-center mt-1.5">LOGIN</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: voucher settings -->
                    <aside class="bg-white border-l border-slate-200 p-4">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Paket Voucher</p>
                        <div class="space-y-3 mb-5">
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-bold text-slate-700">1 Jam</span>
                                    <span class="text-[10px] text-slate-400">Rp 2K</span>
                                </div>
                                <div class="h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full w-3/4 bg-indigo-500"></div>
                                </div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-bold text-slate-700">5 Jam</span>
                                    <span class="text-[10px] text-slate-400">Rp 5K</span>
                                </div>
                                <div class="h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full w-1/2 bg-violet-500"></div>
                                </div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-bold text-slate-700">24 Jam</span>
                                    <span class="text-[10px] text-slate-400">Rp 10K</span>
                                </div>
                                <div class="h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full w-1/4 bg-pink-500"></div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <button class="w-full py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                Preview Fullscreen
                            </button>
                            <button class="w-full py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Reset
                            </button>
                            <button class="w-full py-2.5 text-xs font-bold text-white bg-gradient-to-r from-indigo-600 to-violet-600 rounded-lg hover:from-indigo-700 hover:to-violet-700 transition-colors flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Download ZIP
                            </button>
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        <!-- ════════════════ SELLING POINTS ════════════════ -->
        <section class="mt-16 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div v-for="sp in sellingPoints" :key="sp.title" class="bg-white border border-slate-200 rounded-2xl p-6 hover:border-indigo-200 hover:shadow-lg transition-all">
                <div class="text-3xl mb-3">{{ sp.icon }}</div>
                <h3 class="font-bold text-slate-900 mb-1.5">{{ sp.title }}</h3>
                <p class="text-sm text-slate-500 leading-relaxed">{{ sp.desc }}</p>
            </div>
        </section>
    </div>

    <!-- ════════════════ ZOOM MODAL ════════════════ -->
    <Teleport to="body">
        <div v-if="isZoomed" class="fixed inset-0 z-[100] bg-black/95 flex items-center justify-center p-4 sm:p-8 cursor-zoom-out" @click="isZoomed = false">
            <div class="w-full max-w-5xl h-[60vh] sm:h-[75vh] rounded-3xl shadow-2xl overflow-hidden bg-gradient-to-br flex items-center justify-center" :class="getTemplateGradient(template.name)">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 p-10 text-white text-center">
                    <p class="text-3xl font-bold">{{ template.name }}</p>
                    <p class="text-sm opacity-80 mt-2">Premium Hotspot Template</p>
                </div>
            </div>
            <button class="absolute top-5 right-5 w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center text-2xl transition-colors" @click.stop="isZoomed = false">&times;</button>
        </div>
    </Teleport>
</div>
</template>
