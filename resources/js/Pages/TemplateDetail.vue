<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout.vue';

const props = defineProps({
    template: { type: Object, default: null },
    relatedTemplates: { type: Array, default: () => [] },
    canLogin: Boolean,
});

const t = computed(() => props.template);
const previewMode = ref('desktop'); // 'desktop' | 'mobile'

// Cek apakah user sudah bayar untuk template ini.
// Sumber: $page.props.paidTemplates (dari HandleInertiaRequests middleware).
const page = usePage();
const paidTemplates = computed(() => page.props.paidTemplates || []);
function isPaid(templateId) {
    return paidTemplates.value.includes(templateId);
}

function formatPrice(p) { return 'Rp ' + Number(p).toLocaleString('id-ID'); }

// Hide 0 stats — pakai null kalau rating/sold = 0
const showRating = computed(() => (t.value?.rating ?? 0) > 0);
const showSold = computed(() => (t.value?.sold ?? 0) > 0);
const isNewRelease = computed(() => !showRating.value && !showSold.value);

function shareTemplate() {
    if (navigator.share) {
        navigator.share({ title: t.value?.name + ' — MarketTemplate', url: window.location.href });
    } else {
        navigator.clipboard.writeText(window.location.href);
        alert('Link berhasil disalin!');
    }
}

// Section: "Cocok untuk" use cases
const useCases = ['Cafe', 'Hotel', 'Restoran', 'Warkop', 'UMKM'];

// Section: "Yang Akan Anda Dapatkan" — file deliverable
const deliverables = [
    { name: 'login.html', desc: 'Halaman login MikroTik interaktif' },
    { name: 'status.html', desc: 'Halaman status koneksi user' },
    { name: 'logout.html', desc: 'Halaman logout setelah sesi' },
    { name: 'style.css', desc: 'Stylesheet utama template' },
    { name: 'Assets Lengkap', desc: 'Logo, ikon, font, dan gambar siap pakai' },
    { name: 'Dokumentasi Instalasi', desc: 'Panduan PDF upload ke MikroTik' },
];

// Section: License info
const licenseItems = [
    'Lisensi Lifetime',
    '1 Bisnis / 1 Lokasi',
    'Gratis Update 1 Tahun',
    'Support ROS 6 & ROS 7',
    'Bebas Edit Template',
];

// "Baru Dirilis" fallback badges
const newReleaseBadges = ['Baru Dirilis', 'Template Premium', 'Siap Digunakan'];
</script>

<template>
<Head :title="t ? t.name + ' — MarketTemplate' : 'Template Tidak Ditemukan'" />

<MarketplaceLayout>
<div class="min-h-screen bg-slate-50 antialiased" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

    <!-- ═══════════ NOT FOUND ═══════════ -->
    <div v-if="!t" class="max-w-2xl mx-auto px-4 py-32 text-center">
        <div class="text-6xl mb-5">😕</div>
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Template Tidak Ditemukan</h1>
        <p class="text-slate-500 mb-8">Template yang Anda cari tidak tersedia atau sudah dihapus.</p>
        <Link href="/katalog" class="inline-flex items-center px-6 py-3 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-sm transition-colors">← Kembali ke Katalog</Link>
    </div>

    <div v-else>

        <!-- ═══════════ BREADCRUMB ═══════════ -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
            <nav class="flex items-center gap-2 text-xs text-slate-400 mb-6">
                <Link href="/" class="hover:text-indigo-600 transition-colors">Beranda</Link>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <Link href="/katalog" class="hover:text-indigo-600 transition-colors">Katalog</Link>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-700 font-semibold truncate">{{ t.name }}</span>
            </nav>
        </div>

        <!-- ═══════════ HERO: PREVIEW 65% + INFO 35% ═══════════ -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
            <div class="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-6 lg:gap-8">

                <!-- ════ LEFT: PREVIEW (65-70% lebar) ════ -->
                <div>
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                        <!-- Browser-style chrome -->
                        <div class="bg-slate-50 border-b border-slate-200 px-3 py-2.5 flex items-center gap-2">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            </div>
                            <div class="flex-1 mx-2 bg-white border border-slate-200 rounded-md px-3 py-1 text-[10px] text-slate-400 font-mono truncate flex items-center gap-1.5">
                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                hotspot.{{ t.slug || t.id }}/login
                            </div>
                        </div>

                        <!-- Iframe preview (langsung ke file asli) -->
                        <div class="relative bg-slate-100" :class="previewMode === 'mobile' ? 'flex justify-center py-6' : ''" :style="previewMode === 'mobile' ? '' : 'height: 65vh; min-height: 500px;'">
                            <iframe
                                :src="`/templates/${t.id}/preview/login.html`"
                                :class="previewMode === 'mobile' ? 'w-[375px] h-[700px] border-8 border-slate-800 rounded-3xl shadow-2xl' : 'w-full h-full'"
                                style="border: 0;"
                                :style="previewMode === 'desktop' ? 'min-height: 500px; height: 65vh;' : ''"
                            ></iframe>
                        </div>

                        <!-- Bottom toolbar: mode switcher + fullscreen -->
                        <div class="px-3 py-2.5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <button @click="previewMode = 'mobile'" :class="previewMode === 'mobile' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    Mobile
                                </button>
                                <button @click="previewMode = 'desktop'" :class="previewMode === 'desktop' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    Desktop
                                </button>
                            </div>
                            <a :href="`/template/${t.id}/fullscreen`" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white text-indigo-600 border border-indigo-200 hover:bg-indigo-50 rounded-lg text-xs font-semibold transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                Fullscreen
                            </a>
                        </div>
                    </div>

                    <!-- ════ COCOK UNTUK SECTION ════ -->
                    <div class="mt-8 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-3">Cocok untuk</h3>
                        <p class="text-sm text-slate-500 mb-4">Template ini ideal untuk berbagai jenis bisnis hotspot MikroTik:</p>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="uc in useCases" :key="uc" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-br from-indigo-50 to-violet-50 border border-indigo-100 text-indigo-700 text-sm font-semibold rounded-xl">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                {{ uc }}
                            </span>
                        </div>
                    </div>

                    <!-- ════ YANG AKAN ANDA DAPATKAN SECTION ════ -->
                    <div class="mt-6 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <h3 class="text-base font-extrabold text-slate-900 mb-1">Yang Akan Anda Dapatkan</h3>
                        <p class="text-sm text-slate-500 mb-5">Paket lengkap siap upload ke MikroTik.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div v-for="d in deliverables" :key="d.name" class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-900 text-sm">{{ d.name }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ d.desc }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════ RIGHT: INFO PRODUK (30-35% lebar, sticky) ════ -->
                <div class="lg:sticky lg:top-20 self-start space-y-5">

                    <!-- Badges -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold bg-gradient-to-r from-amber-400 to-orange-500 text-white rounded-lg uppercase tracking-wider">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            Premium
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold bg-slate-100 text-slate-700 rounded-lg capitalize">{{ t.category }}</span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 rounded-lg">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            ROS 6 &amp; 7
                        </span>
                    </div>

                    <!-- Template name -->
                    <h1 class="text-3xl lg:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">{{ t.name }}</h1>

                    <!-- Stats (hide 0) -->
                    <div class="flex items-center gap-3 text-sm py-3 border-y border-slate-100">
                        <div v-if="showRating" class="flex items-center gap-1 text-amber-500">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <span class="font-extrabold text-slate-900">{{ t.rating }}</span>
                            <span class="text-slate-400 text-xs ml-0.5">/5</span>
                        </div>
                        <div v-if="showSold" class="flex items-center gap-1 text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="font-semibold text-slate-700">{{ t.sold }}</span>
                            <span>terjual</span>
                        </div>
                        <!-- Fallback: Baru Dirilis / Template Premium / Siap Digunakan -->
                        <div v-if="isNewRelease" class="flex flex-wrap items-center gap-1.5">
                            <span v-for="b in newReleaseBadges" :key="b" class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                {{ b }}
                            </span>
                        </div>
                    </div>

                    <!-- Price -->
                    <div class="flex items-end gap-2 pt-1">
                        <span class="text-4xl lg:text-5xl font-extrabold text-indigo-600 tracking-tight">{{ formatPrice(t.price) }}</span>
                        <span v-if="t.discountPrice && t.discountPrice > t.price" class="text-base text-slate-400 line-through mb-1">{{ formatPrice(t.discountPrice) }}</span>
                        <span v-if="t.discountPrice && t.discountPrice > t.price" class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md mb-1">HEMAT</span>
                    </div>

                    <!-- ════ CTA BUTTONS — kondisional: Beli / Sudah Dibeli + Edit / Download ════ -->
                    <div class="space-y-2.5 pt-2">
                        <!-- BELUM BAYAR: tombol Beli Sekarang -->
                        <template v-if="!isPaid(t.id)">
                            <Link :href="`/checkout/${t.id}`" method="get" as="button" class="w-full py-4 text-base font-bold text-white bg-gradient-to-r from-indigo-600 to-violet-600 rounded-2xl hover:from-indigo-700 hover:to-violet-700 shadow-xl shadow-indigo-200 transition-all hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                Beli Sekarang
                            </Link>
                        </template>

                        <!-- SUDAH BAYAR: badge "Sudah Dibeli" + Edit Template + Download -->
                        <template v-else>
                            <div class="w-full py-4 px-4 text-base font-bold text-emerald-700 bg-gradient-to-r from-emerald-50 to-emerald-100 border-2 border-emerald-200 rounded-2xl flex items-center justify-center gap-2">
                                <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Sudah Dibeli
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                <Link :href="`/template/${t.id}/edit`" class="py-3 text-sm font-semibold text-white bg-indigo-600 rounded-2xl hover:bg-indigo-700 transition-all flex items-center justify-center gap-2 shadow-md shadow-indigo-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Edit Template
                                </Link>
                                <a :href="`/template/${t.id}/download`" class="py-3 text-sm font-semibold text-emerald-700 bg-white border-2 border-emerald-200 rounded-2xl hover:bg-emerald-50 transition-all flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download
                                </a>
                            </div>
                        </template>

                        <!-- Live Preview selalu tampil (untuk lihat sebelum/sesudah bayar) -->
                        <a :href="`/templates/${t.id}/preview/login.html`" target="_blank" rel="noopener" class="w-full py-3.5 text-sm font-semibold text-indigo-600 bg-white border-2 border-indigo-200 rounded-2xl hover:bg-indigo-50 transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Live Preview
                        </a>

                        <!-- Edit Template (untuk user yang BELUM bayar — preview-only mode) -->
                        <Link v-if="!isPaid(t.id) && $page.props.auth.user" :href="`/template/${t.id}/editor`" class="w-full py-3.5 text-sm font-semibold text-slate-700 bg-white border-2 border-slate-200 rounded-2xl hover:border-indigo-300 hover:bg-slate-50 transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit Template Sebelum Membeli
                        </Link>

                        <!-- Secondary actions (lebih kecil) -->
                        <div class="grid grid-cols-2 gap-2.5 pt-1">
                            <Link v-if="$page.props.auth.user" :href="`/cart/${t.id}`" method="post" as="button" class="py-2.5 text-xs font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                + Keranjang
                            </Link>
                            <button v-else disabled class="py-2.5 text-xs font-medium text-slate-400 border border-slate-200 rounded-xl flex items-center justify-center gap-1.5 cursor-not-allowed" title="Login dulu untuk menambah ke keranjang">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l2l2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                + Keranjang
                            </button>
                            <button @click="shareTemplate" class="py-2.5 text-xs font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                Bagikan
                            </button>
                        </div>
                    </div>

                    <!-- ════ LICENSE CHECKLIST ════ -->
                    <div class="pt-4 border-t border-slate-100">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Lisensi &amp; Hak Penggunaan</h3>
                        <ul class="space-y-2">
                            <li v-for="item in licenseItems" :key="item" class="flex items-center gap-2.5 text-sm text-slate-700">
                                <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════ DESKRIPSI TEMPLATE ═══════════ -->
        <section v-if="t.longDesc || t.shortDesc" class="bg-white border-y border-slate-200 py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-sm font-semibold text-indigo-600 uppercase tracking-wider mb-2">Tentang Template</p>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-4">{{ t.name }}</h2>
                <div class="prose prose-slate max-w-none text-slate-600 leading-relaxed whitespace-pre-line">{{ t.longDesc || t.shortDesc }}</div>
            </div>
        </section>

        <!-- ═══════════ TEMPLATE SERUPA ═══════════ -->
        <section v-if="relatedTemplates.length > 0" class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between mb-6">
                    <div>
                        <p class="text-sm font-semibold text-indigo-600 uppercase tracking-wider mb-1">Rekomendasi</p>
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Template Serupa</h2>
                    </div>
                    <Link href="/katalog" class="hidden sm:inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700 transition-colors">
                        Lihat Semua
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </Link>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <Link v-for="rt in relatedTemplates" :key="rt.id" :href="`/template/${rt.id}`" class="group bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-xl hover:shadow-slate-200/60 hover:border-indigo-200 hover:-translate-y-0.5 transition-all duration-300">
                        <div class="relative aspect-[4/3] bg-slate-100 overflow-hidden">
                            <img v-if="rt.imageUrl" :src="rt.imageUrl" :alt="rt.name" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy" />
                            <div v-else class="absolute inset-0 bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center text-white font-extrabold text-3xl">{{ rt.name?.charAt(0) }}</div>
                        </div>
                        <div class="p-4">
                            <p class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider mb-1">{{ rt.category }}</p>
                            <h3 class="font-bold text-slate-900 text-sm mb-2 line-clamp-2 group-hover:text-indigo-600 transition-colors">{{ rt.name }}</h3>
                            <div class="flex items-center justify-between">
                                <span class="text-base font-extrabold text-indigo-600">{{ formatPrice(rt.price) }}</span>
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 group-hover:translate-x-0.5 transition-transform">
                                    Lihat
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </span>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ═══════════ FOOTER SIMPLE ═══════════ -->
        <footer class="bg-slate-900 text-slate-300 py-10 mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <div class="flex items-center gap-2.5 mb-3">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center">
                                <span class="text-white font-extrabold text-sm">MT</span>
                            </div>
                            <span class="font-bold text-white">MarketTemplate</span>
                        </div>
                        <p class="text-sm text-slate-400 leading-relaxed">Marketplace template hotspot MikroTik premium #1 di Indonesia.</p>
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm mb-3">Marketplace</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link href="/" class="hover:text-white transition-colors">Beranda</Link></li>
                            <li><Link href="/katalog" class="hover:text-white transition-colors">Katalog</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm mb-3">Bantuan</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="https://wa.me/6281234567890" class="hover:text-white transition-colors">WhatsApp Support</a></li>
                            <li><a href="mailto:support@markettemplate.id" class="hover:text-white transition-colors">Email Support</a></li>
                        </ul>
                    </div>
                </div>
                <div class="mt-8 pt-6 border-t border-slate-800 text-center text-xs text-slate-500">
                    © 2026 MarketTemplate.id — Semua hak dilindungi.
                </div>
            </div>
        </footer>
    </div>
</div>
</MarketplaceLayout>
</template>
