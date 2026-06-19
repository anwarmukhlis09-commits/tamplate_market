<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout.vue';

const props = defineProps({
    canLogin: Boolean,
    templates: { type: Array, default: () => [] },
    fetchedAt: { type: Number, default: 0 },
});

// Cek apakah user sudah bayar untuk template tertentu.
// Sumber: $page.props.paidTemplates (dari HandleInertiaRequests middleware → session).
const page = usePage();
const paidTemplates = computed(() => page.props.paidTemplates || []);
function isPaid(templateId) {
    return paidTemplates.value.includes(templateId);
}

// ── Data ──────────────────────────────
// Sumber data: LANGSUNG dari props.templates (Inertia props, dari DB).
// Pakai computed + watch props.templates agar OTOMATIS sync saat
// Inertia re-render (mis. setelah admin delete, lalu user navigate back).
// TIDAK pakai localStorage, session, atau hardcoded array.
const allTemplates = ref([...(props.templates || [])]);
watch(() => props.templates, (newTemplates) => {
    // Setiap kali props.templates berubah (mis. Inertia refresh), update state
    allTemplates.value = [...(newTemplates || [])];
    // Reset filter state ke awal karena list berubah
    currentPage.value = 1;
    searchQuery.value = '';
    selectedCategory.value = 'all';
    selectedTypes.value = [];
    selectedFeatures.value = [];
    priceMin.value = 0;
    priceMax.value = 100000;
    sortBy.value = 'popular';
    closeDrawer();
}, { deep: true });

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

// ── Mobile drawer state ───────────────
const drawerOpen = ref(false);

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

// ── Active filter counter (for mobile Filter button) ──
const activeFilterCount = computed(() => {
    let n = 0;
    if (selectedCategory.value !== 'all') n++;
    if (selectedTypes.value.length > 0) n++;
    if (selectedFeatures.value.length > 0) n++;
    if (priceMin.value > 0 || priceMax.value < 100000) n++;
    if (searchQuery.value.trim()) n++;
    return n;
});

// ── Methods ───────────────────────────
function goToPage(p) {
    if (p < 1 || p > totalPages.value) return;
    currentPage.value = p;
    window.scrollTo({ top: 0, behavior: 'smooth' });
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
// Validasi imageUrl: hanya render <img> kalau path valid (URL absolute
// atau path relatif). Reject path Windows absolut seperti "C:\..." yang
// bisa nyangkut di DB kalau upload setengah jadi.
function isValidImageUrl(url) {
    if (!url) return false;
    if (typeof url !== 'string') return false;
    const s = url.trim();
    if (!s) return false;
    // Reject Windows absolute paths (c:/, C:\, dll)
    if (/^[a-z]:[\\\/]/i.test(s)) return false;
    // Accept: http(s)://, //, atau path relatif
    return /^(https?:)?\/\//i.test(s) || s.startsWith('/') || s.startsWith('data:');
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
function openDrawer() { drawerOpen.value = true; }
function closeDrawer() { drawerOpen.value = false; }
function applyFilters() { closeDrawer(); }
</script>

<template>

    <Head title="Template Hotspot MikroTik — MarketTemplate" />

    <MarketplaceLayout>
        <div class="min-h-screen bg-slate-50 antialiased"
            style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

        <!-- ════════════════ MAIN CONTENT ════════════════ -->
        <main class="pt-20 pb-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-8">

                    <!-- ════════ LEFT SIDEBAR FILTERS (desktop sticky) ════════ -->
                    <aside class="hidden lg:block">
                        <div
                            class="sticky top-20 max-h-[calc(100vh-90px)] overflow-y-auto pb-2 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:bg-slate-200 [&::-webkit-scrollbar-thumb]:rounded-full">
                            <div class="space-y-5">

                                <!-- Header + Reset -->
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-bold text-slate-900">Filter</h3>
                                    <button @click="resetFilters"
                                        class="text-xs font-medium text-indigo-600 hover:text-indigo-700 transition-colors">Reset
                                        all</button>
                                </div>

                                <!-- Category Filter -->
                                <div class="bg-white border border-slate-200 rounded-2xl p-5">
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Category
                                    </h4>
                                    <ul class="space-y-1">
                                        <li v-for="cat in categories" :key="cat.value">
                                            <button @click="selectedCategory = cat.value; currentPage = 1"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg transition-colors text-left"
                                                :class="selectedCategory === cat.value ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:bg-slate-50'">
                                                <svg class="w-4 h-4 shrink-0"
                                                    :class="selectedCategory === cat.value ? 'text-indigo-600' : 'text-slate-400'"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.8" :d="cat.icon" />
                                                </svg>
                                                <span class="flex-1">{{ cat.label }}</span>
                                                <span class="text-xs text-slate-400" v-if="cat.value !== 'all'">
                                                    {{allTemplates.filter(t => t.category === cat.value).length}}
                                                </span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Price Filter -->
                                <div class="bg-white border border-slate-200 rounded-2xl p-5">
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Price
                                        Range</h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-semibold text-slate-700">{{ formatPrice(priceMin)
                                                }}</span>
                                            <span class="font-semibold text-slate-700">{{ formatPrice(priceMax)
                                                }}</span>
                                        </div>
                                        <div class="space-y-2.5">
                                            <div>
                                                <label
                                                    class="text-[10px] font-medium text-slate-500 uppercase">Min</label>
                                                <input type="range" v-model.number="priceMin" min="0" max="100000"
                                                    step="5000" @change="updatePriceRange"
                                                    class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-indigo-600" />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[10px] font-medium text-slate-500 uppercase">Max</label>
                                                <input type="range" v-model.number="priceMax" min="0" max="100000"
                                                    step="5000" @change="updatePriceRange"
                                                    class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-indigo-600" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Template Type (Platform) -->
                                <div class="bg-white border border-slate-200 rounded-2xl p-5">
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Platform
                                    </h4>
                                    <div class="space-y-2">
                                        <label class="flex items-center gap-2.5 cursor-pointer group">
                                            <input type="checkbox" :checked="selectedTypes.includes('premium')"
                                                @change="toggleArrayValue(selectedTypes, 'premium')"
                                                class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                            <span
                                                class="text-sm text-slate-700 flex-1 group-hover:text-indigo-600 transition-colors">Premium</span>
                                            <span
                                                class="text-xs text-amber-600 font-semibold bg-amber-50 px-2 py-0.5 rounded">PRO</span>
                                        </label>
                                        <label class="flex items-center gap-2.5 cursor-pointer group">
                                            <input type="checkbox" :checked="selectedTypes.includes('free')"
                                                @change="toggleArrayValue(selectedTypes, 'free')"
                                                class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                            <span
                                                class="text-sm text-slate-700 flex-1 group-hover:text-indigo-600 transition-colors">Free</span>
                                            <span
                                                class="text-xs text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded">FREE</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Features -->
                                <div class="bg-white border border-slate-200 rounded-2xl p-5">
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Features
                                    </h4>
                                    <div class="space-y-2">
                                        <label v-for="f in features" :key="f.value"
                                            class="flex items-center gap-2.5 cursor-pointer group">
                                            <input type="checkbox" :checked="selectedFeatures.includes(f.value)"
                                                @change="toggleArrayValue(selectedFeatures, f.value)"
                                                class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-500 transition-colors shrink-0"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                    :d="f.icon" />
                                            </svg>
                                            <span
                                                class="text-sm text-slate-700 group-hover:text-indigo-600 transition-colors">{{
                                                f.label }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <!-- ════════ RIGHT: TEMPLATE LISTING ════════ -->
                    <div>
                        <!-- Title + description -->
                        <div class="mb-5">
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Template
                                Hotspot MikroTik</h1>
                            <p class="text-sm sm:text-base text-slate-500 mt-1.5">Temukan template login MikroTik
                                premium siap pakai. Pilih, sesuaikan, langsung online.</p>
                        </div>

                        <!-- Mobile-only: Filter button + result count -->
                        <div class="lg:hidden flex items-center justify-between gap-3 mb-4">
                            <button @click="openDrawer"
                                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                Filter
                                <span v-if="activeFilterCount > 0"
                                    class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[10px] font-bold text-white bg-indigo-600 rounded-full">{{
                                    activeFilterCount }}</span>
                            </button>
                            <span class="text-xs text-slate-500 font-medium">{{ totalResults }} template</span>
                        </div>

                        <!-- Search + Sort -->
                        <div class="flex flex-col sm:flex-row gap-3 mb-6">
                            <div class="relative flex-1">
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input v-model="searchQuery" @input="currentPage = 1" type="search"
                                    placeholder="Cari template, kategori, atau kata kunci..."
                                    class="w-full pl-11 pr-4 py-2.5 text-sm bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all placeholder:text-slate-400" />
                            </div>
                            <select v-model="sortBy"
                                class="text-sm border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none cursor-pointer text-slate-700 font-medium">
                                <option value="popular">Terpopuler</option>
                                <option value="newest">Terbaru</option>
                                <option value="price-low">Termurah</option>
                                <option value="price-high">Termahal</option>
                                <option value="rating">Rating Tertinggi</option>
                            </select>
                        </div>

                        <!-- Results counter (desktop) -->
                        <div class="hidden lg:flex items-center justify-between mb-4">
                            <p class="text-sm text-slate-500">
                                <span class="font-semibold text-slate-700">{{ totalResults }}</span> template ditemukan
                            </p>
                        </div>

                        <!-- Grid -->
                        <div v-if="paginatedTemplates.length > 0"
                            class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                            <div v-for="tpl in paginatedTemplates" :key="tpl.id"
                                class="group bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-2xl hover:shadow-slate-300/60 hover:border-indigo-200 hover:-translate-y-1 transition-all duration-300">
                                <!-- Thumbnail: imageUrl dari DB, fallback skeleton abu-abu -->
                                <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
                                    <img v-if="isValidImageUrl(tpl.imageUrl)" :src="tpl.imageUrl" :alt="tpl.name"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                        loading="lazy" />
                                    <div v-else class="absolute inset-0 flex items-center justify-center bg-slate-100">
                                        <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <!-- MikroTik compatibility badge -->
                                    <div
                                        class="absolute top-3 left-3 inline-flex items-center gap-1 px-2 py-1 bg-slate-900/85 backdrop-blur rounded-md text-[9px] font-bold text-white">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM10 18a8 8 0 100-16 8 8 0 000 16zm-3-7a1 1 0 011-1h.01a1 1 0 010 2H8a1 1 0 01-1-1zm5-1a1 1 0 100 2h.01a1 1 0 100-2H12zm-4 4a1 1 0 011-1h4a1 1 0 110 2H9a1 1 0 01-1-1z" />
                                        </svg>
                                        MikroTik
                                    </div>
                                    <!-- Premium/Free badge -->
                                    <div v-if="tpl.price === 0"
                                        class="absolute top-3 right-3 px-2.5 py-1 bg-emerald-500 text-white text-[10px] font-bold uppercase tracking-wider rounded-md shadow-md">
                                        Free</div>
                                    <div v-else
                                        class="absolute top-3 right-3 px-2.5 py-1 bg-gradient-to-r from-amber-400 to-orange-500 text-white text-[10px] font-bold uppercase tracking-wider rounded-md shadow-md">
                                        Premium</div>
                                    <!-- Custom badge from DB (Best Seller, Baru, Populer, dll) -->
                                    <div v-if="tpl.badge"
                                        class="absolute bottom-3 left-3 px-2 py-0.5 bg-white/95 backdrop-blur text-slate-800 text-[10px] font-bold rounded-md shadow-md">
                                        {{ tpl.badge }}</div>
                                    <!-- Favorite -->
                                    <button @click.prevent="toggleWishlist(tpl.id)"
                                        class="absolute bottom-3 right-3 w-9 h-9 bg-white/95 backdrop-blur rounded-full flex items-center justify-center shadow-md hover:scale-110 transition-transform">
                                        <svg class="w-4 h-4"
                                            :class="wishlist.has(tpl.id) ? 'text-rose-500 fill-rose-500' : 'text-slate-400'"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Card body -->
                                <div class="p-5">
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <div class="min-w-0 flex-1">
                                            <h3
                                                class="font-bold text-slate-900 leading-snug truncate group-hover:text-indigo-600 transition-colors">
                                                {{ tpl.name }}</h3>
                                            <p class="text-xs text-slate-500 mt-0.5 capitalize">{{ tpl.category }}</p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div v-if="tpl.discountPrice && tpl.discountPrice > tpl.price"
                                                class="text-[10px] text-slate-400 line-through">{{
                                                formatPrice(tpl.discountPrice) }}</div>
                                            <div class="text-base font-extrabold text-indigo-600 whitespace-nowrap">{{
                                                tpl.price === 0 ? 'Gratis' : formatPrice(tpl.price) }}</div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 mb-4 text-xs">
                                        <div class="flex items-center gap-0.5">
                                            <svg v-for="i in 5" :key="i" class="w-3.5 h-3.5"
                                                :class="i <= Math.round(tpl.rating || 4.8) ? 'text-amber-400 fill-amber-400' : 'text-slate-200 fill-slate-200'"
                                                viewBox="0 0 20 20">
                                                <path
                                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        </div>
                                        <span class="font-semibold text-slate-700">{{ tpl.rating || '4.8' }}</span>
                                        <span class="text-slate-300">·</span>
                                        <span class="text-slate-500">{{ tpl.sold || 0 }} terjual</span>
                                    </div>

                                    <!-- Action buttons: Demo + Beli -->
                                    <div class="grid grid-cols-2 gap-2">
                                        <a :href="`/templates/${tpl.id}/preview`" target="_blank" rel="noopener"
                                            class="inline-flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-xl hover:bg-indigo-100 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Demo
                                        </a>
                                        <!-- Tombol kondisional: Beli / Sudah Dibeli / Edit Template -->
                                        <Link v-if="!isPaid(tpl.id)" :href="'/template/' + tpl.id"
                                            class="inline-flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-center text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            {{ tpl.price === 0 ? 'Download' : 'Beli' }}
                                        </Link>
                                        <div v-else class="grid grid-cols-2 gap-2">
                                            <!-- Sudah Dibeli: badge hijau, non-clickable -->
                                            <div class="inline-flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                                Sudah Dibeli
                                            </div>
                                            <!-- Edit Template: link ke editor -->
                                            <Link :href="'/template/' + tpl.id + '/edit'"
                                                class="inline-flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-white bg-violet-600 rounded-xl hover:bg-violet-700 transition-colors shadow-sm shadow-violet-200">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Edit
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div v-else
                            class="text-center py-20 bg-white rounded-2xl border border-dashed border-slate-300">
                            <div
                                class="w-16 h-16 mx-auto mb-4 bg-slate-100 rounded-2xl flex items-center justify-center">
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 mb-1.5">Tidak ada template</h3>
                            <p class="text-slate-500 text-sm mb-6 max-w-sm mx-auto">Coba ubah kata kunci atau hapus
                                filter yang aktif.</p>
                            <button @click="resetFilters"
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-semibold text-indigo-600 bg-indigo-50 rounded-xl hover:bg-indigo-100 transition-colors">Reset
                                Filter</button>
                        </div>

                        <!-- Pagination -->
                        <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 mt-12">
                            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                                class="px-3.5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center gap-1.5 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7" />
                                </svg>
                                Sebelumnya
                            </button>
                            <template v-for="(p, i) in visiblePages()" :key="i">
                                <span v-if="p === '…'"
                                    class="w-10 h-10 flex items-center justify-center text-slate-300 text-sm">…</span>
                                <button v-else @click="goToPage(p)"
                                    class="w-10 h-10 text-sm font-semibold rounded-xl transition-all"
                                    :class="p === currentPage ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 shadow-sm'">
                                    {{ p }}
                                </button>
                            </template>
                            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
                                class="px-3.5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center gap-1.5 shadow-sm">
                                Selanjutnya
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- ════════════════ MOBILE FILTER DRAWER ════════════════ -->
        <Teleport to="body">
            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="drawerOpen" class="lg:hidden fixed inset-0 z-[100] bg-slate-900/50 backdrop-blur-sm"
                    @click.self="closeDrawer">
                    <transition enter-active-class="transition duration-300 ease-out"
                        enter-from-class="translate-y-full" enter-to-class="translate-y-0"
                        leave-active-class="transition duration-200 ease-in" leave-from-class="translate-y-0"
                        leave-to-class="translate-y-full">
                        <div v-if="drawerOpen"
                            class="absolute bottom-0 inset-x-0 bg-white rounded-t-3xl shadow-2xl max-h-[85vh] flex flex-col"
                            @click.stop>
                            <!-- Drawer handle -->
                            <div class="flex items-center justify-center pt-3 pb-2">
                                <div class="w-12 h-1.5 bg-slate-300 rounded-full"></div>
                            </div>
                            <!-- Drawer header -->
                            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200">
                                <h3 class="text-base font-bold text-slate-900">Filter {{ activeFilterCount > 0 ?
                                    `(${activeFilterCount})` : '' }}</h3>
                                <button @click="resetFilters"
                                    class="text-xs font-medium text-indigo-600 hover:text-indigo-700 transition-colors">Reset</button>
                            </div>
                            <!-- Drawer body (scrollable) -->
                            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">

                                <!-- Category Filter -->
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Kategori
                                    </h4>
                                    <ul class="space-y-1">
                                        <li v-for="cat in categories" :key="cat.value">
                                            <button @click="selectedCategory = cat.value; currentPage = 1"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg transition-colors text-left"
                                                :class="selectedCategory === cat.value ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:bg-slate-50'">
                                                <svg class="w-4 h-4 shrink-0"
                                                    :class="selectedCategory === cat.value ? 'text-indigo-600' : 'text-slate-400'"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.8" :d="cat.icon" />
                                                </svg>
                                                <span class="flex-1">{{ cat.label }}</span>
                                                <span class="text-xs text-slate-400" v-if="cat.value !== 'all'">
                                                    {{allTemplates.filter(t => t.category === cat.value).length}}
                                                </span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Price Filter -->
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Harga
                                    </h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-semibold text-slate-700">{{ formatPrice(priceMin)
                                                }}</span>
                                            <span class="font-semibold text-slate-700">{{ formatPrice(priceMax)
                                                }}</span>
                                        </div>
                                        <div class="space-y-2.5">
                                            <div>
                                                <label
                                                    class="text-[10px] font-medium text-slate-500 uppercase">Min</label>
                                                <input type="range" v-model.number="priceMin" min="0" max="100000"
                                                    step="5000" @change="updatePriceRange"
                                                    class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-indigo-600" />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[10px] font-medium text-slate-500 uppercase">Max</label>
                                                <input type="range" v-model.number="priceMax" min="0" max="100000"
                                                    step="5000" @change="updatePriceRange"
                                                    class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-indigo-600" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Platform -->
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Platform
                                    </h4>
                                    <div class="space-y-2">
                                        <label class="flex items-center gap-2.5 cursor-pointer group">
                                            <input type="checkbox" :checked="selectedTypes.includes('premium')"
                                                @change="toggleArrayValue(selectedTypes, 'premium')"
                                                class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                            <span
                                                class="text-sm text-slate-700 flex-1 group-hover:text-indigo-600 transition-colors">Premium</span>
                                            <span
                                                class="text-xs text-amber-600 font-semibold bg-amber-50 px-2 py-0.5 rounded">PRO</span>
                                        </label>
                                        <label class="flex items-center gap-2.5 cursor-pointer group">
                                            <input type="checkbox" :checked="selectedTypes.includes('free')"
                                                @change="toggleArrayValue(selectedTypes, 'free')"
                                                class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                            <span
                                                class="text-sm text-slate-700 flex-1 group-hover:text-indigo-600 transition-colors">Free</span>
                                            <span
                                                class="text-xs text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded">FREE</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Features -->
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Fitur
                                    </h4>
                                    <div class="space-y-2">
                                        <label v-for="f in features" :key="f.value"
                                            class="flex items-center gap-2.5 cursor-pointer group">
                                            <input type="checkbox" :checked="selectedFeatures.includes(f.value)"
                                                @change="toggleArrayValue(selectedFeatures, f.value)"
                                                class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-500 transition-colors shrink-0"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                    :d="f.icon" />
                                            </svg>
                                            <span
                                                class="text-sm text-slate-700 group-hover:text-indigo-600 transition-colors">{{
                                                f.label }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <!-- Drawer footer -->
                            <div class="px-5 py-4 border-t border-slate-200 bg-slate-50">
                                <button @click="applyFilters"
                                    class="w-full py-3 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
                                    Terapkan Filter
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>
            </transition>
        </Teleport>
        </div>
    </MarketplaceLayout>
</template>
