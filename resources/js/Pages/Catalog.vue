<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { templates as templatesData, categories as categoriesData } from '@/data/templates.js';

defineProps({ canLogin: Boolean });

// ── Data ──────────────────────────────
const allTemplates = ref([...templatesData]);
const categories = ref([...categoriesData]);

// ── Filter State ──────────────────────
const searchQuery = ref('');
const selectedCategory = ref('');
const sortBy = ref('popular');
const priceRange = ref({ min: '', max: '' });
const currentPage = ref(1);
const perPage = 9;
const viewMode = ref('grid');

// ── Computed ──────────────────────────
const filteredTemplates = computed(() => {
    let result = [...allTemplates.value];
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(t =>
            t.name.toLowerCase().includes(q) ||
            t.features.some(f => f.toLowerCase().includes(q)) ||
            (t.shortDesc || '').toLowerCase().includes(q) ||
            (t.longDesc || '').toLowerCase().includes(q)
        );
    }
    if (selectedCategory.value) result = result.filter(t => t.category === selectedCategory.value);
    if (priceRange.value.min !== '') result = result.filter(t => t.price >= Number(priceRange.value.min));
    if (priceRange.value.max !== '') result = result.filter(t => t.price <= Number(priceRange.value.max));
    switch (sortBy.value) {
        case 'popular': result.sort((a, b) => b.sold - a.sold); break;
        case 'newest': result.sort((a, b) => b.id - a.id); break;
        case 'price-low': result.sort((a, b) => a.price - b.price); break;
        case 'price-high': result.sort((a, b) => b.price - a.price); break;
        case 'rating': result.sort((a, b) => b.rating - a.rating); break;
    }
    return result;
});
const totalPages = computed(() => Math.ceil(filteredTemplates.value.length / perPage));
const paginatedTemplates = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return filteredTemplates.value.slice(start, start + perPage);
});
const totalResults = computed(() => filteredTemplates.value.length);

// ── Methods ───────────────────────────
function goToPage(p) { currentPage.value = p; window.scrollTo({ top: 500, behavior: 'smooth' }); }
function resetFilters() { searchQuery.value = ''; selectedCategory.value = ''; priceRange.value = { min: '', max: '' }; sortBy.value = 'popular'; currentPage.value = 1; }
function formatPrice(p) { return 'Rp ' + p.toLocaleString('id-ID'); }
function getBadgeClass(b) {
    const m = { 'Best Seller': 'bg-amber-50 text-amber-700 border-amber-200', 'Baru': 'bg-emerald-50 text-emerald-700 border-emerald-200', 'Populer': 'bg-rose-50 text-rose-700 border-rose-200', 'Sale': 'bg-red-50 text-red-600 border-red-200', 'Seasonal': 'bg-orange-50 text-orange-700 border-orange-200', 'Trending': 'bg-violet-50 text-violet-700 border-violet-200' };
    return m[b] || 'bg-slate-50 text-slate-600 border-slate-200';
}
function visiblePages() {
    const pages = [];
    for (let i = 1; i <= totalPages.value; i++) pages.push(i);
    if (totalPages.value <= 7) return pages;
    const cur = currentPage.value;
    if (cur <= 4) return [...pages.slice(0, 5), '…', totalPages.value];
    if (cur >= totalPages.value - 3) return [1, '…', ...pages.slice(-5)];
    return [1, '…', cur - 1, cur, cur + 1, '…', totalPages.value];
}
</script>

<template>
    <Head title="Katalog Template Hotspot — MarketTemplate" />

    <div class="min-h-screen bg-white antialiased">

        <!-- ════════════════ NAVBAR ════════════════ -->
        <header class="fixed top-0 inset-x-0 z-50 bg-white/80 backdrop-blur-xl border-b border-white/20 shadow-sm shadow-slate-200/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center gap-2.5 shrink-0 group">
                        <div class="w-9 h-9 bg-gradient-to-br from-indigo-600 to-violet-600 rounded-xl flex items-center justify-center shadow-md shadow-indigo-200 group-hover:shadow-lg group-hover:scale-105 transition-all">
                            <span class="text-white font-extrabold text-sm">MT</span>
                        </div>
                        <span class="font-bold text-lg text-slate-900 tracking-tight">Market<span class="text-indigo-600">Template</span></span>
                    </Link>
                    <!-- Nav -->
                    <nav class="hidden md:flex items-center gap-1">
                        <Link href="/" class="px-3.5 py-2 text-sm font-medium text-slate-500 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">Beranda</Link>
                        <span class="px-3.5 py-2 text-sm font-semibold text-indigo-700 bg-indigo-50 rounded-lg">Katalog</span>
                    </nav>
                    <!-- Auth -->
                    <div class="flex items-center gap-2.5">
                        <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 rounded-lg hover:bg-slate-50 transition-colors">Login</Link>
                        <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm transition-all">Dashboard</Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- ════════════════ HERO ════════════════ -->
        <section class="relative pt-24 pb-16 sm:pt-28 sm:pb-20 overflow-hidden">

            <!-- Background decoration -->
            <div class="absolute inset-0 -z-10 pointer-events-none" aria-hidden="true">
                <div class="absolute top-0 right-0 w-[600px] h-[400px] bg-gradient-to-bl from-indigo-50 via-indigo-50/50 to-transparent rounded-bl-[100px]"></div>
                <div class="absolute bottom-0 left-0 w-[500px] h-[300px] bg-gradient-to-tr from-violet-50/60 to-transparent rounded-tr-[100px]"></div>
            </div>

            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <!-- Label -->
                <p class="text-sm font-semibold text-indigo-600 mb-4 uppercase tracking-wide">Katalog Template</p>

                <!-- Heading -->
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-slate-900 tracking-tight mb-5">
                    Temukan Template Hotspot <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600">Terbaik</span> untuk Bisnis Anda
                </h1>

                <!-- Subtitle -->
                <p class="text-lg text-slate-500 max-w-2xl mx-auto leading-relaxed">
                    {{ allTemplates.length }} template login MikroTik premium — responsive, modern, dan siap dipasang dalam hitungan menit.
                </p>

                <!-- Hero search -->
                <div class="mt-8 max-w-xl mx-auto">
                    <div class="relative">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        <input v-model="searchQuery" type="text" placeholder="Cari nama template, fitur, atau kata kunci..."
                            class="w-full pl-12 pr-4 py-3.5 text-sm border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none bg-white shadow-sm shadow-slate-200/50 transition-shadow placeholder:text-slate-400">
                    </div>
                </div>
            </div>
        </section>

        <!-- ════════════════ FILTER BAR ════════════════ -->
        <div class="sticky top-16 z-40 bg-white/80 backdrop-blur-xl border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">

                    <!-- Category pills -->
                    <div class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0 [&::-webkit-scrollbar]{display:none} [-ms-overflow-style:none] [scrollbar-width:none]">
                        <button v-for="cat in categories" :key="cat.value" @click="selectedCategory = cat.value; currentPage = 1"
                            class="shrink-0 px-3.5 py-1.5 text-xs font-semibold rounded-lg border transition-all"
                            :class="selectedCategory === cat.value ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm shadow-indigo-200' : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300 hover:text-slate-700'">
                            {{ cat.label }}
                            <span v-if="cat.value" class="ml-1 text-[10px] opacity-70">{{ allTemplates.filter(t => t.category === cat.value).length }}</span>
                        </button>
                    </div>

                    <!-- Sort + view toggle -->
                    <div class="flex items-center gap-2.5 shrink-0">
                        <p class="text-xs text-slate-400 hidden sm:block">
                            <span class="font-semibold text-slate-600">{{ totalResults }}</span> template
                            <span v-if="selectedCategory || searchQuery || priceRange.min || priceRange.max" class="ml-1 text-indigo-600 font-medium">· difilter</span>
                        </p>
                        <select v-model="sortBy" class="text-xs border border-slate-200 rounded-lg px-2.5 py-2 bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none cursor-pointer text-slate-600">
                            <option value="popular">Terpopuler</option>
                            <option value="newest">Terbaru</option>
                            <option value="price-low">Harga Rendah</option>
                            <option value="price-high">Harga Tinggi</option>
                            <option value="rating">Rating</option>
                        </select>
                        <div class="hidden sm:flex border border-slate-200 rounded-lg overflow-hidden bg-white">
                            <button @click="viewMode = 'grid'" class="p-2 transition-colors" :class="viewMode === 'grid' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-600 hover:bg-slate-50'" title="Grid">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 16 16"><path d="M1.5 1h4v4h-4V1zm0 5h4v4h-4V6zm5-5h4v4h-4V1zm0 5h4v4h-4V6zm5-5h4v4h-4V1zm0 5h4v4h-4V6z"/></svg>
                            </button>
                            <button @click="viewMode = 'list'" class="p-2 transition-colors" :class="viewMode === 'list' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-600 hover:bg-slate-50'" title="List">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M2 3.5a.5.5 0 01.5-.5h11a.5.5 0 010 1h-11a.5.5 0 01-.5-.5zm0 4a.5.5 0 01.5-.5h11a.5.5 0 010 1h-11a.5.5 0 01-.5-.5zm0 4a.5.5 0 01.5-.5h11a.5.5 0 010 1h-11a.5.5 0 01-.5-.5z"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════ CATALOG SECTION ════════════════ -->
        <section class="bg-slate-50/80 py-8 sm:py-10 min-h-[60vh]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Mobile search (only when hero search scrolled away) -->
                <div class="lg:hidden mb-4">
                    <!-- Mobile category displayed via sticky bar already -->
                </div>

                <!-- ===== GRID VIEW ===== -->
                <div v-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    <Link v-for="tpl in paginatedTemplates" :key="tpl.id" :href="'/template/' + tpl.id"
                        class="group flex flex-col bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-xl hover:shadow-slate-200/60 hover:-translate-y-1 hover:border-indigo-200 transition-all duration-300">

                        <!-- Card Image -->
                        <div :class="tpl.image" class="relative h-48 flex items-center justify-center shrink-0">
                            <span class="text-white/15 text-8xl font-black select-none">{{ tpl.name.charAt(0) }}</span>
                            <span v-if="tpl.badge" class="absolute top-3 left-3 inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg border" :class="getBadgeClass(tpl.badge)">{{ tpl.badge }}</span>
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                                <span class="px-5 py-2.5 bg-white/90 backdrop-blur text-slate-900 font-semibold text-sm rounded-xl opacity-0 group-hover:opacity-100 transition-all translate-y-2 group-hover:translate-y-0 shadow-lg">Lihat Detail</span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="flex flex-col flex-1 p-5">
                            <div class="flex items-start justify-between gap-3 mb-2.5">
                                <h3 class="font-bold text-slate-900 leading-snug">{{ tpl.name }}</h3>
                                <span class="text-base font-extrabold text-indigo-600 whitespace-nowrap">{{ formatPrice(tpl.price) }}</span>
                            </div>
                            <p class="text-xs text-slate-400 leading-relaxed mb-3 line-clamp-2">{{ tpl.shortDesc }}</p>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="flex items-center gap-0.5">
                                    <svg v-for="i in 5" :key="i" class="w-3.5 h-3.5" :class="i <= Math.round(tpl.rating) ? 'text-amber-400 fill-amber-400' : 'text-slate-200 fill-slate-200'" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </div>
                                <span class="text-xs font-medium text-slate-400">{{ tpl.rating }}</span>
                                <span class="text-slate-200">·</span>
                                <span class="text-xs text-slate-400">{{ tpl.sold }} terjual</span>
                            </div>
                            <div class="flex flex-wrap gap-1.5 mb-auto">
                                <span v-for="f in tpl.features.slice(0, 3)" :key="f" class="text-xs text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg font-medium">{{ f }}</span>
                                <span v-if="tpl.features.length > 3" class="text-xs text-slate-400 font-medium self-center">+{{ tpl.features.length - 3 }}</span>
                            </div>
                            <div class="mt-5 pt-4 border-t border-slate-100">
                                <span class="block w-full py-2.5 text-sm font-semibold text-center text-white bg-indigo-600 rounded-xl group-hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200 group-hover:shadow-md group-hover:shadow-indigo-200">Beli Sekarang</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- ===== LIST VIEW ===== -->
                <div v-else class="space-y-4">
                    <Link v-for="tpl in paginatedTemplates" :key="tpl.id" :href="'/template/' + tpl.id"
                        class="group flex flex-col sm:flex-row bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-lg hover:border-indigo-200 transition-all duration-300">
                        <div :class="tpl.image" class="sm:w-56 h-44 sm:h-auto relative shrink-0 flex items-center justify-center">
                            <span class="text-white/15 text-7xl font-black select-none">{{ tpl.name.charAt(0) }}</span>
                            <span v-if="tpl.badge" class="absolute top-3 left-3 inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg border" :class="getBadgeClass(tpl.badge)">{{ tpl.badge }}</span>
                        </div>
                        <div class="flex-1 flex flex-col justify-between p-5 sm:p-6">
                            <div>
                                <div class="flex items-start justify-between gap-3 mb-1.5">
                                    <h3 class="font-bold text-slate-900 text-lg">{{ tpl.name }}</h3>
                                    <span class="text-lg font-extrabold text-indigo-600 whitespace-nowrap">{{ formatPrice(tpl.price) }}</span>
                                </div>
                                <div class="flex items-center gap-2 mb-2.5">
                                    <div class="flex items-center gap-0.5">
                                        <svg v-for="i in 5" :key="i" class="w-3.5 h-3.5" :class="i <= Math.round(tpl.rating) ? 'text-amber-400 fill-amber-400' : 'text-slate-200 fill-slate-200'" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    </div>
                                    <span class="text-sm font-medium text-slate-500">{{ tpl.rating }}</span>
                                    <span class="text-slate-300">·</span>
                                    <span class="text-sm text-slate-400">{{ tpl.sold }} terjual</span>
                                </div>
                                <p class="text-sm text-slate-400 leading-relaxed mb-3 line-clamp-2">{{ tpl.shortDesc }}</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <span v-for="f in tpl.features.slice(0, 4)" :key="f" class="text-xs text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg font-medium">{{ f }}</span>
                                    <span v-if="tpl.features.length > 4" class="text-xs text-slate-400 font-medium self-center">+{{ tpl.features.length - 4 }} lainnya</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-end mt-4 pt-4 border-t border-slate-100">
                                <span class="px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-xl group-hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">Beli Sekarang</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- ===== EMPTY STATE ===== -->
                <div v-if="paginatedTemplates.length === 0" class="text-center py-24 bg-white rounded-2xl border border-slate-200 shadow-sm">
                    <div class="w-20 h-20 mx-auto mb-5 bg-slate-100 rounded-2xl flex items-center justify-center">
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-1.5">Template Tidak Ditemukan</h3>
                    <p class="text-slate-400 text-sm mb-8 max-w-sm mx-auto">Tidak ada template yang cocok dengan filter. Coba ubah kata kunci atau hapus filter.</p>
                    <button @click="resetFilters" class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-semibold text-indigo-600 bg-indigo-50 rounded-xl hover:bg-indigo-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Reset Filter
                    </button>
                </div>

                <!-- ===== PAGINATION ===== -->
                <div v-if="totalPages > 1" class="flex items-center justify-center gap-1.5 mt-16">
                    <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                        class="px-4 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 disabled:opacity-30 disabled:cursor-not-allowed transition-all flex items-center gap-1 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Sebelumnya
                    </button>
                    <template v-for="(p, i) in visiblePages()" :key="i">
                        <span v-if="p === '…'" class="w-10 h-10 flex items-center justify-center text-slate-300 text-sm">…</span>
                        <button v-else @click="goToPage(p)"
                            class="w-10 h-10 text-sm font-semibold rounded-xl transition-all"
                            :class="p === currentPage ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 shadow-sm'">
                            {{ p }}
                        </button>
                    </template>
                    <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
                        class="px-4 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 disabled:opacity-30 disabled:cursor-not-allowed transition-all flex items-center gap-1 shadow-sm">
                        Berikutnya
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

            </div>
        </section>

        <!-- ════════════════ FOOTER ════════════════ -->
        <footer class="bg-slate-900 text-slate-400 py-12 px-4">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-10">
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-2.5 mb-3">
                            <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-violet-500 rounded-xl flex items-center justify-center shadow-md">
                                <span class="text-white font-extrabold text-sm">MT</span>
                            </div>
                            <span class="font-bold text-lg text-white tracking-tight">Market<span class="text-indigo-400">Template</span></span>
                        </div>
                        <p class="text-sm text-slate-400 max-w-sm leading-relaxed">Penyedia template login hotspot MikroTik terbaik di Indonesia. Desain profesional, pemasangan mudah, harga terjangkau.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white text-sm mb-4">Navigasi</h4>
                        <ul class="space-y-2.5 text-sm">
                            <li><Link href="/" class="text-slate-400 hover:text-white transition-colors">Beranda</Link></li>
                            <li><Link href="/katalog" class="text-slate-400 hover:text-white transition-colors">Katalog</Link></li>
                            <li><Link href="/login" class="text-slate-400 hover:text-white transition-colors">Login</Link></li>
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
                <div class="border-t border-slate-800 pt-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-slate-500">
                    <p>&copy; {{ new Date().getFullYear() }} MarketTemplate. All rights reserved.</p>
                    <p>Made with ❤️ in Indonesia</p>
                </div>
            </div>
        </footer>
    </div>
</template>
