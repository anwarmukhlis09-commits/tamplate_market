<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    canLogin: Boolean,
    templates: { type: Array, default: () => [] },
});

// ── Data ──────────────────────────────
const allTemplates = ref([...props.templates]);

// ── Filter State ──────────────────────
const searchQuery = ref('');
const selectedCategory = ref('all');
const selectedTypes = ref([]); // 'premium' | 'free'
const selectedFeatures = ref([]); // 'responsive', 'dark-mode', ...
const priceMin = ref(0);
const priceMax = ref(100000);
const sortBy = ref('popular');
const currentPage = ref(1);
const perPage = 9;
const wishlist = ref(new Set());

// ── Categories ─────────────────────────
const categories = [
    { value: 'all', label: 'All Templates', icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' },
    { value: 'minimalis', label: 'Minimalist', icon: 'M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z' },
    { value: 'modern', label: 'Modern', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
    { value: 'gaming', label: 'Gaming', icon: 'M11 17a1 1 0 102 0v-1a1 1 0 10-2 0v1zM5 17a1 1 0 102 0v-1a1 1 0 10-2 0v1zm5-15a7 7 0 00-7 7c0 2 .5 3.5 1.5 5L3 17h14l-1.5-3c1-1.5 1.5-3 1.5-5a7 7 0 00-7-7z' },
    { value: 'hotel', label: 'Hotel', icon: 'M3 21h18M3 7v14M21 7v14M6 21V11h12v10M9 7V3h6v4' },
    { value: 'cafe', label: 'Cafe & Restaurant', icon: 'M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z' },
    { value: 'sekolah', label: 'School', icon: 'M12 14l9-5-9-5-9 5 9 5zM12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z' },
    { value: 'voucher', label: 'Voucher', icon: 'M2 9V7a2 2 0 012-2h16a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4z' },
    { value: 'isp', label: 'ISP', icon: 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M2 8.82a15 15 0 0120 0M5 12.859a10 10 0 0114 0M8.5 16.429a5 5 0 017 0' },
    { value: 'other', label: 'Others', icon: 'M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z' },
];

// ── Features ──────────────────────────
const features = [
    { value: 'responsive', label: 'Responsive', icon: 'M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z' },
    { value: 'dark-mode', label: 'Dark Mode', icon: 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z' },
    { value: 'multi-language', label: 'Multi Language', icon: 'M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129' },
    { value: 'qr-login', label: 'QR Login', icon: 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z' },
    { value: 'voucher', label: 'Voucher Ready', icon: 'M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4V6z' },
    { value: 'mobile', label: 'Mobile Friendly', icon: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z' },
];

// ── Stats ─────────────────────────────
const stats = [
    { value: '500+', label: 'Templates', icon: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM14 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z' },
    { value: '4.9', label: 'Average Rating', icon: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' },
    { value: '25K+', label: 'Downloads', icon: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4' },
    { value: '100%', label: 'MikroTik Compatible', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
];

// ── Computed ──────────────────────────
const filteredTemplates = computed(() => {
    let result = [...allTemplates.value];

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(t =>
            t.name.toLowerCase().includes(q) ||
            (t.shortDesc || '').toLowerCase().includes(q) ||
            (t.longDesc || '').toLowerCase().includes(q)
        );
    }

    if (selectedCategory.value !== 'all') {
        result = result.filter(t => t.category === selectedCategory.value);
    }

    if (selectedTypes.value.length > 0) {
        result = result.filter(t => {
            if (selectedTypes.value.includes('free') && t.price === 0) return true;
            if (selectedTypes.value.includes('premium') && t.price > 0) return true;
            return false;
        });
    }

    if (selectedFeatures.value.length > 0) {
        result = result.filter(t => {
            const feats = (t.features || []).map(f => f.toLowerCase());
            return selectedFeatures.value.every(sf =>
                feats.some(f => f.includes(sf.replace('-', ' ')) || f.includes(sf))
            );
        });
    }

    result = result.filter(t => t.price >= priceMin.value && t.price <= priceMax.value);

    switch (sortBy.value) {
        case 'popular': result.sort((a, b) => b.sold - a.sold); break;
        case 'newest': result.sort((a, b) => b.id - a.id); break;
        case 'price-low': result.sort((a, b) => a.price - b.price); break;
        case 'price-high': result.sort((a, b) => b.price - a.price); break;
        case 'rating': result.sort((a, b) => b.rating - a.rating); break;
    }
    return result;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredTemplates.value.length / perPage)));
const paginatedTemplates = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return filteredTemplates.value.slice(start, start + perPage);
});
const totalResults = computed(() => filteredTemplates.value.length);

// ── Methods ───────────────────────────
function goToPage(p) {
    if (p < 1 || p > totalPages.value) return;
    currentPage.value = p;
    window.scrollTo({ top: 400, behavior: 'smooth' });
}
function resetFilters() {
    searchQuery.value = '';
    selectedCategory.value = 'all';
    selectedTypes.value = [];
    selectedFeatures.value = [];
    priceMin.value = 0;
    priceMax.value = 100000;
    sortBy.value = 'popular';
    currentPage.value = 1;
}
function toggleArrayValue(arr, value) {
    const idx = arr.value.indexOf(value);
    if (idx > -1) arr.value.splice(idx, 1);
    else arr.value.push(value);
    currentPage.value = 1;
}
function toggleWishlist(id) {
    if (wishlist.value.has(id)) wishlist.value.delete(id);
    else wishlist.value.add(id);
}
function formatPrice(p) { return 'Rp ' + Number(p).toLocaleString('id-ID'); }
function getTemplateGradient(name) {
    const gradients = [
        'from-indigo-500 via-purple-500 to-pink-500',
        'from-blue-500 via-indigo-500 to-violet-500',
        'from-emerald-400 via-teal-500 to-cyan-500',
        'from-orange-400 via-rose-500 to-pink-500',
        'from-violet-500 via-purple-500 to-fuchsia-500',
        'from-cyan-400 via-blue-500 to-indigo-500',
        'from-rose-400 via-red-500 to-orange-500',
        'from-amber-400 via-orange-500 to-red-500',
    ];
    let h = 0;
    for (let i = 0; i < (name || 'x').length; i++) h = (h * 31 + name.charCodeAt(i)) & 0xfffffff;
    return gradients[h % gradients.length];
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
function updatePriceRange() {
    currentPage.value = 1;
}
</script>

<template>
<Head title="Katalog Template Hotspot MikroTik — MarketTemplate" />

<div class="min-h-screen bg-slate-50 antialiased" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

    <!-- ════════════════ NAVBAR ════════════════ -->
    <header class="fixed top-0 inset-x-0 z-50 bg-white/85 backdrop-blur-xl border-b border-slate-200/60 shadow-sm shadow-slate-200/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-6 h-16">
                <Link href="/" class="flex items-center gap-2.5 shrink-0 group">
                    <img src="/images/logo.png" alt="MarketTemplate" class="h-10 w-auto group-hover:scale-105 transition-transform" />
                </Link>

                <nav class="hidden lg:flex items-center gap-1">
                    <Link href="/" class="px-3.5 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">Beranda</Link>
                    <span class="px-3.5 py-2 text-sm font-semibold text-indigo-700 bg-indigo-50 rounded-lg">Template</span>
                    <a href="/#cara-kerja" class="px-3.5 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">Cara Kerja</a>
                    <a href="/#bantuan-sidebar" class="px-3.5 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">Bantuan</a>
                </nav>

                <div class="flex items-center gap-2.5 shrink-0">
                    <button class="relative w-10 h-10 flex items-center justify-center text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors" title="Cart">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span class="absolute top-1.5 right-1.5 w-4 h-4 text-[9px] font-bold text-white bg-rose-500 rounded-full flex items-center justify-center">3</span>
                    </button>
                    <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')" class="hidden sm:inline-flex px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-200 transition-all">
                        Sign In
                    </Link>
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-100 rounded-xl transition-colors">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center text-white text-sm font-bold">
                            {{ $page.props.auth.user.name?.charAt(0) || 'U' }}
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </header>

    <!-- ════════════════ HERO ════════════════ -->
    <section class="relative pt-28 pb-12 sm:pt-32 sm:pb-16 overflow-hidden">
        <div class="absolute inset-0 -z-10 pointer-events-none" aria-hidden="true">
            <div class="absolute -top-40 -right-40 w-[500px] h-[500px] rounded-full bg-gradient-to-br from-indigo-200 via-indigo-50 to-transparent opacity-60 blur-3xl"></div>
            <div class="absolute -bottom-20 -left-40 w-[400px] h-[400px] rounded-full bg-gradient-to-tr from-violet-200 via-purple-50 to-transparent opacity-50 blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:24px_24px] opacity-30"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-white border border-indigo-100 rounded-full text-sm font-medium text-indigo-700 mb-6 shadow-sm shadow-indigo-100/50">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                </span>
                Template Hotspot MikroTik
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.05] mb-5">
                Template Hotspot <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600">MikroTik</span>
            </h1>

            <p class="text-lg sm:text-xl text-slate-500 max-w-2xl mx-auto leading-relaxed mb-10">
                Temukan ratusan template login hotspot MikroTik premium yang siap upload dan langsung digunakan.
            </p>

            <!-- Search bar -->
            <div class="max-w-2xl mx-auto mb-12">
                <div class="relative">
                    <svg class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input v-model="searchQuery" @input="currentPage = 1" type="search" placeholder="Cari template, kategori, atau kata kunci..."
                        class="w-full pl-14 pr-4 py-4 text-sm bg-white border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none shadow-lg shadow-slate-200/40 transition-all placeholder:text-slate-400" />
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 max-w-4xl mx-auto">
                <div v-for="s in stats" :key="s.label" class="bg-white border border-slate-200 rounded-2xl p-5 hover:border-indigo-200 hover:shadow-md transition-all">
                    <div class="w-9 h-9 mx-auto rounded-xl bg-indigo-50 flex items-center justify-center mb-2.5">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="s.icon"/></svg>
                    </div>
                    <div class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ s.value }}</div>
                    <div class="text-xs text-slate-500 mt-1 font-medium">{{ s.label }}</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════════════ MAIN CONTENT ════════════════ -->
    <section class="pb-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-8">

                <!-- ════════ LEFT SIDEBAR FILTERS ════════ -->
                <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto lg:pr-2 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:bg-slate-200 [&::-webkit-scrollbar-thumb]:rounded-full">

                    <!-- Reset button -->
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-900">Filter</h3>
                        <button @click="resetFilters" class="text-xs font-medium text-indigo-600 hover:text-indigo-700 transition-colors">Reset all</button>
                    </div>

                    <!-- Category Filter -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-5">
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Category</h4>
                        <ul class="space-y-1">
                            <li v-for="cat in categories" :key="cat.value">
                                <button @click="selectedCategory = cat.value; currentPage = 1"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg transition-colors text-left"
                                    :class="selectedCategory === cat.value ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:bg-slate-50'">
                                    <svg class="w-4 h-4 shrink-0" :class="selectedCategory === cat.value ? 'text-indigo-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="cat.icon"/></svg>
                                    <span class="flex-1">{{ cat.label }}</span>
                                    <span class="text-xs text-slate-400" v-if="cat.value !== 'all'">
                                        {{ allTemplates.filter(t => t.category === cat.value).length }}
                                    </span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Price Filter -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-5">
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Price Range</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-700">{{ formatPrice(priceMin) }}</span>
                                <span class="font-semibold text-slate-700">{{ formatPrice(priceMax) }}</span>
                            </div>
                            <div class="space-y-2.5">
                                <div>
                                    <label class="text-[10px] font-medium text-slate-500 uppercase">Min</label>
                                    <input type="range" v-model.number="priceMin" min="0" max="100000" step="5000" @change="updatePriceRange"
                                        class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-indigo-600" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-medium text-slate-500 uppercase">Max</label>
                                    <input type="range" v-model.number="priceMax" min="0" max="100000" step="5000" @change="updatePriceRange"
                                        class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-indigo-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Template Type -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-5">
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Template Type</h4>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <input type="checkbox" :checked="selectedTypes.includes('premium')" @change="toggleArrayValue(selectedTypes, 'premium')"
                                    class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                <span class="text-sm text-slate-700 flex-1 group-hover:text-indigo-600 transition-colors">Premium</span>
                                <span class="text-xs text-amber-600 font-semibold bg-amber-50 px-2 py-0.5 rounded">PRO</span>
                            </label>
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <input type="checkbox" :checked="selectedTypes.includes('free')" @change="toggleArrayValue(selectedTypes, 'free')"
                                    class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                <span class="text-sm text-slate-700 flex-1 group-hover:text-indigo-600 transition-colors">Free</span>
                                <span class="text-xs text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded">FREE</span>
                            </label>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-5">
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Features</h4>
                        <div class="space-y-2">
                            <label v-for="f in features" :key="f.value" class="flex items-center gap-2.5 cursor-pointer group">
                                <input type="checkbox" :checked="selectedFeatures.includes(f.value)" @change="toggleArrayValue(selectedFeatures, f.value)"
                                    class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-500 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="f.icon"/></svg>
                                <span class="text-sm text-slate-700 group-hover:text-indigo-600 transition-colors">{{ f.label }}</span>
                            </label>
                        </div>
                    </div>
                </aside>

                <!-- ════════ RIGHT: TEMPLATE GRID ════════ -->
                <div>
                    <!-- Results header -->
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900">All Templates</h2>
                            <p class="text-sm text-slate-500 mt-0.5">
                                <span class="font-semibold text-slate-700">{{ totalResults }}</span> templates found
                            </p>
                        </div>
                        <select v-model="sortBy" class="text-sm border border-slate-200 rounded-xl px-3.5 py-2 bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none cursor-pointer text-slate-700 font-medium">
                            <option value="popular">Most Popular</option>
                            <option value="newest">Newest</option>
                            <option value="rating">Highest Rated</option>
                            <option value="price-low">Price: Low to High</option>
                            <option value="price-high">Price: High to Low</option>
                        </select>
                    </div>

                    <!-- Grid -->
                    <div v-if="paginatedTemplates.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                        <div v-for="tpl in paginatedTemplates" :key="tpl.id" class="group bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-2xl hover:shadow-slate-300/60 hover:border-indigo-200 hover:-translate-y-1 transition-all duration-300">
                            <!-- Thumbnail (realistic hotspot mockup) -->
                            <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
                                <div class="absolute inset-0 bg-gradient-to-br" :class="getTemplateGradient(tpl.name)">
                                    <!-- Mockup hotspot login -->
                                    <div class="absolute inset-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 flex flex-col items-center justify-center p-4 text-white">
                                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center mb-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M2 8.82a15 15 0 0120 0M5 12.859a10 10 0 0114 0"/></svg>
                                        </div>
                                        <p class="text-[10px] uppercase tracking-widest opacity-80 mb-0.5">Selamat Datang</p>
                                        <p class="text-sm font-bold text-center leading-tight mb-2">{{ tpl.name }}</p>
                                        <div class="w-full max-w-[140px] space-y-1">
                                            <div class="h-6 bg-white/15 backdrop-blur rounded-md border border-white/20"></div>
                                            <div class="h-6 bg-white/15 backdrop-blur rounded-md border border-white/20"></div>
                                            <div class="h-7 bg-white text-indigo-700 rounded-md text-[9px] font-bold flex items-center justify-center mt-1.5">LOGIN</div>
                                        </div>
                                    </div>
                                </div>
                                <!-- MikroTik compatibility badge -->
                                <div class="absolute top-3 left-3 inline-flex items-center gap-1 px-2 py-1 bg-slate-900/85 backdrop-blur rounded-md text-[9px] font-bold text-white">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM10 18a8 8 0 100-16 8 8 0 000 16zm-3-7a1 1 0 011-1h.01a1 1 0 010 2H8a1 1 0 01-1-1zm5-1a1 1 0 100 2h.01a1 1 0 100-2H12zm-4 4a1 1 0 011-1h4a1 1 0 110 2H9a1 1 0 01-1-1z"/></svg>
                                    MikroTik
                                </div>
                                <!-- Premium/Free badge -->
                                <div v-if="tpl.price === 0" class="absolute top-3 right-3 px-2.5 py-1 bg-emerald-500 text-white text-[10px] font-bold uppercase tracking-wider rounded-md shadow-md">Free</div>
                                <div v-else class="absolute top-3 right-3 px-2.5 py-1 bg-gradient-to-r from-amber-400 to-orange-500 text-white text-[10px] font-bold uppercase tracking-wider rounded-md shadow-md">Premium</div>
                                <!-- Favorite -->
                                <button @click.prevent="toggleWishlist(tpl.id)" class="absolute bottom-3 right-3 w-9 h-9 bg-white/95 backdrop-blur rounded-full flex items-center justify-center shadow-md hover:scale-110 transition-transform">
                                    <svg class="w-4 h-4" :class="wishlist.has(tpl.id) ? 'text-rose-500 fill-rose-500' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                </button>
                                <!-- Hover overlay: quick actions -->
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col items-center justify-end p-4 gap-2">
                                    <div class="flex items-center gap-2 w-full">
                                        <a :href="`/templates/${tpl.id}/preview/login.html`" target="_blank" rel="noopener" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 bg-white text-slate-900 text-xs font-semibold rounded-xl hover:bg-slate-100 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Live Preview
                                        </a>
                                        <button @click.prevent="toggleWishlist(tpl.id)" class="w-10 h-10 bg-white text-slate-700 rounded-xl flex items-center justify-center hover:bg-slate-100 transition-colors" title="Add to Wishlist">
                                            <svg class="w-4 h-4" :class="wishlist.has(tpl.id) ? 'text-rose-500 fill-rose-500' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Card body -->
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <div class="min-w-0 flex-1">
                                        <h3 class="font-bold text-slate-900 leading-snug truncate group-hover:text-indigo-600 transition-colors">{{ tpl.name }}</h3>
                                        <p class="text-xs text-slate-500 mt-0.5">by <span class="font-medium text-slate-700">MarketTemplate Studio</span></p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div v-if="tpl.discountPrice && tpl.discountPrice > tpl.price" class="text-[10px] text-slate-400 line-through">{{ formatPrice(tpl.discountPrice) }}</div>
                                        <div class="text-base font-extrabold text-indigo-600 whitespace-nowrap">{{ tpl.price === 0 ? 'Gratis' : formatPrice(tpl.price) }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 mb-3 text-xs">
                                    <div class="flex items-center gap-0.5">
                                        <svg v-for="i in 5" :key="i" class="w-3.5 h-3.5" :class="i <= Math.round(tpl.rating || 4.8) ? 'text-amber-400 fill-amber-400' : 'text-slate-200 fill-slate-200'" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    </div>
                                    <span class="font-semibold text-slate-700">{{ tpl.rating || '4.8' }}</span>
                                    <span class="text-slate-300">·</span>
                                    <span class="text-slate-500">{{ tpl.sold || 0 }} sold</span>
                                </div>

                                <!-- Badges -->
                                <div class="flex flex-wrap gap-1.5 mb-4">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        Mobile
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM10 18a8 8 0 100-16 8 8 0 000 16z"/></svg>
                                        MikroTik
                                    </span>
                                    <span v-if="tpl.sold > 100" class="inline-flex items-center gap-1 text-[10px] font-semibold text-violet-700 bg-violet-50 px-2 py-0.5 rounded-md">
                                        🔥 Trending
                                    </span>
                                </div>

                                <!-- Buy button -->
                                <Link :href="'/template/' + tpl.id" class="block w-full py-2.5 text-sm font-semibold text-center text-white bg-indigo-600 rounded-xl group-hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200 group-hover:shadow-md">
                                    {{ tpl.price === 0 ? 'Download Gratis' : 'Beli Sekarang' }}
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="text-center py-20 bg-white rounded-2xl border border-dashed border-slate-300">
                        <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-1.5">No templates found</h3>
                        <p class="text-slate-500 text-sm mb-6 max-w-sm mx-auto">Coba ubah kata kunci atau hapus filter yang aktif.</p>
                        <button @click="resetFilters" class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-semibold text-indigo-600 bg-indigo-50 rounded-xl hover:bg-indigo-100 transition-colors">Reset Filters</button>
                    </div>

                    <!-- Pagination -->
                    <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 mt-12">
                        <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                            class="px-3.5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center gap-1.5 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Previous
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
                            class="px-3.5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center gap-1.5 shadow-sm">
                            Next
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
</template>
