<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    template: { type: Object, default: null },
    canLogin: Boolean,
});

const template = computed(() => props.template);
const relatedTemplates = computed(() => []); // TODO: fetch from backend
const activePreview = ref(0);
const isZoomed = ref(false);

function formatPrice(p) { return 'Rp ' + p.toLocaleString('id-ID'); }
function getBadgeClass(b) {
    const m = { 'Best Seller': 'bg-amber-400 text-amber-900', 'Baru': 'bg-emerald-400 text-emerald-900', 'Populer': 'bg-rose-400 text-rose-900', 'Sale': 'bg-red-500 text-white', 'Seasonal': 'bg-orange-400 text-orange-900', 'Trending': 'bg-violet-400 text-violet-900' };
    return m[b] || 'bg-gray-400 text-gray-900';
}
function shareTemplate() {
    if (navigator.share) { navigator.share({ title: template.value?.name + ' — MarketTemplate', url: window.location.href }); }
    else { navigator.clipboard.writeText(window.location.href); alert('Link berhasil disalin!'); }
}
</script>

<template>
    <Head :title="template ? template.name + ' — MarketTemplate' : 'Template Tidak Ditemukan'" />

    <div class="min-h-screen bg-gray-50 antialiased">

        <!-- ==================== NAVBAR ==================== -->
        <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <Link href="/" class="flex items-center gap-2.5 shrink-0">
                        <div class="w-9 h-9 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-200">
                            <span class="text-white font-bold text-sm">MT</span>
                        </div>
                        <span class="font-bold text-lg text-gray-900 tracking-tight hidden sm:inline">Market<span class="text-indigo-600">Template</span></span>
                    </Link>
                    <nav class="hidden md:flex items-center gap-1">
                        <Link href="/" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 rounded-lg hover:bg-gray-50 transition-colors">Beranda</Link>
                        <Link href="/katalog" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 rounded-lg hover:bg-gray-50 transition-colors">Template</Link>
                        <a href="/#cara-kerja" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 rounded-lg hover:bg-gray-50 transition-colors">Cara Kerja</a>
                        <a href="/#bantuan" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 rounded-lg hover:bg-gray-50 transition-colors">Bantuan</a>
                    </nav>
                    <div class="flex items-center gap-2.5">
                        <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')" class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-indigo-600 transition-colors">Login</Link>
                        <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm transition-colors">Dashboard</Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- ==================== NOT FOUND ==================== -->
        <div v-if="!template" class="max-w-2xl mx-auto px-4 py-32 text-center">
            <div class="text-6xl mb-5">😕</div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Template Tidak Ditemukan</h1>
            <p class="text-gray-500 mb-8">Template yang Anda cari tidak tersedia atau sudah dihapus.</p>
            <Link href="/katalog" class="inline-flex items-center px-6 py-3 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-sm transition-colors">
                ← Kembali ke Katalog
            </Link>
        </div>

        <!-- ==================== CONTENT ==================== -->
        <div v-else class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-sm text-gray-400 mb-6">
                <Link href="/" class="hover:text-indigo-600 transition-colors">Beranda</Link>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <Link href="/katalog" class="hover:text-indigo-600 transition-colors">Katalog</Link>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-600 font-medium truncate">{{ template.name }}</span>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

                <!-- ===== LEFT: PREVIEW ===== -->
                <div class="lg:col-span-3 space-y-4">
                    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
                        <!-- Preview image -->
                        <div
                            class="relative h-72 sm:h-96 flex items-center justify-center cursor-zoom-in overflow-hidden"
                            :class="template.previewImageUrl ? 'bg-slate-200' : template.previews[activePreview]"
                            @click="isZoomed = !isZoomed">
                            <img v-if="template.previewImageUrl" :src="template.previewImageUrl" class="w-full h-full object-cover" alt="">
                            <span v-else class="text-white/15 text-8xl font-black select-none">{{ template.name.charAt(0) }}</span>
                            <span v-if="template.badge" class="absolute top-4 left-4 px-3 py-1 text-sm font-bold rounded-full" :class="getBadgeClass(template.badge)">{{ template.badge }}</span>
                            <div class="absolute top-4 right-4 flex items-center gap-1 bg-white/90 backdrop-blur px-3 py-1.5 rounded-full text-sm font-semibold text-gray-700">
                                <svg class="w-4 h-4 text-amber-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                {{ template.rating }} <span class="text-gray-400 font-normal">({{ template.sold }} terjual)</span>
                            </div>
                            <div class="absolute bottom-4 left-4 flex items-center gap-2">
                                <span class="bg-black/40 backdrop-blur px-3 py-1.5 rounded-full text-xs font-medium text-white">Mobile</span>
                                <span class="bg-black/40 backdrop-blur px-3 py-1.5 rounded-full text-xs font-medium text-white">Desktop</span>
                            </div>
                            <div class="absolute bottom-4 right-4 bg-black/40 backdrop-blur px-3 py-1.5 rounded-full text-xs font-medium text-white">Klik untuk zoom</div>
                        </div>
                        <!-- Thumbnails -->
                        <div class="flex gap-2 p-3 border-t border-gray-100 bg-gray-50/50">
                            <button v-for="(preview, i) in template.previews" :key="i" @click="activePreview = i"
                                :class="[preview, 'w-14 h-10 rounded-lg border-2 transition-all shrink-0', activePreview === i ? 'border-indigo-600 ring-2 ring-indigo-200 shadow-sm' : 'border-gray-200 hover:border-gray-400 opacity-80 hover:opacity-100']" />
                        </div>
                    </div>
                </div>

                <!-- ===== RIGHT: INFO ===== -->
                <div class="lg:col-span-2 space-y-5">

                    <!-- Main Info -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                        <span class="inline-block px-2.5 py-1 text-xs font-semibold bg-indigo-50 text-indigo-600 rounded-lg uppercase tracking-wide mb-3">{{ template.category }}</span>
                        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight mb-3">{{ template.name }}</h1>
                        <p class="text-gray-500 text-sm leading-relaxed mb-5">{{ template.longDesc }}</p>
                        <div class="flex items-end gap-2 mb-5">
                            <span class="text-3xl font-extrabold text-indigo-600">{{ formatPrice(template.price) }}</span>
                            <span class="text-sm text-gray-400 line-through">{{ formatPrice(template.price + 20000) }}</span>
                            <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">Hemat 29%</span>
                        </div>
                        <button class="w-full py-3.5 text-base font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all hover:-translate-y-0.5 hover:shadow-xl">
                            Beli Sekarang — {{ formatPrice(template.price) }}
                        </button>
                        <Link :href="'/template/' + template.id + '/edit'" class="mt-2.5 w-full py-3.5 text-base font-semibold text-indigo-600 bg-indigo-50 border-2 border-indigo-200 rounded-xl hover:bg-indigo-100 transition-all flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Edit & Preview Template
                        </Link>
                        <Link :href="'/template/' + template.id + '/preview'" class="mt-2.5 w-full py-3.5 text-base font-semibold text-indigo-600 bg-white border-2 border-indigo-200 rounded-xl hover:bg-indigo-50 transition-all flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Preview Interaktif
                        </Link>
                        <div class="flex gap-2 mt-2.5">
                            <button class="flex-1 py-2.5 text-sm font-medium text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">Simpan</button>
                            <button @click="shareTemplate" class="flex-1 py-2.5 text-sm font-medium text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">Bagikan</button>
                        </div>
                        <div class="flex items-center gap-3 mt-4 p-3 bg-green-50 border border-green-100 rounded-xl">
                            <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <div class="text-xs text-green-800"><span class="font-semibold">Garansi 7 Hari</span> — Uang kembali jika template tidak sesuai atau error.</div>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                        <h3 class="font-semibold text-gray-900 text-lg mb-4">Fitur Template</h3>
                        <ul class="space-y-2.5">
                            <li v-for="f in template.features" :key="f" class="flex items-start gap-2.5 text-sm text-gray-600">
                                <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ f }}
                            </li>
                        </ul>
                    </div>

                    <!-- What's Included -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                        <h3 class="font-semibold text-gray-900 text-lg mb-4">Yang Anda Dapatkan</h3>
                        <ul class="space-y-2.5">
                            <li v-for="item in template.whatsIncluded" :key="item" class="flex items-start gap-2.5 text-sm text-gray-600">
                                <svg class="w-5 h-5 text-indigo-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ item }}
                            </li>
                        </ul>
                    </div>

                    <!-- Meta -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><span class="text-gray-400 text-xs">Terakhir Update</span><p class="font-semibold text-gray-700 mt-0.5">{{ template.updatedAt }}</p></div>
                            <div><span class="text-gray-400 text-xs">Terjual</span><p class="font-semibold text-gray-700 mt-0.5">{{ template.sold }} kali</p></div>
                            <div><span class="text-gray-400 text-xs">Rating</span><p class="font-semibold text-gray-700 mt-0.5"> {{ template.rating }}/5.0</p></div>
                            <div><span class="text-gray-400 text-xs">Kategori</span><p class="font-semibold text-gray-700 mt-0.5 capitalize">{{ template.category }}</p></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== RELATED TEMPLATES ===== -->
            <div v-if="relatedTemplates.length" class="mt-16">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-extrabold text-gray-900 tracking-tight">Template Terkait</h2>
                        <p class="text-sm text-gray-500 mt-1">Template lain dalam kategori yang sama.</p>
                    </div>
                    <Link href="/katalog" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 transition-colors">Lihat Semua →</Link>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <Link v-for="tpl in relatedTemplates" :key="tpl.id" :href="'/template/' + tpl.id"
                        class="group flex flex-col bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-xl hover:border-indigo-200 hover:-translate-y-1 transition-all duration-300">
                        <div :class="tpl.image" class="relative h-40 flex items-center justify-center shrink-0">
                            <span class="text-white/20 text-5xl font-black select-none">{{ tpl.name.charAt(0) }}</span>
                            <span v-if="tpl.badge" class="absolute top-3 left-3 px-2 py-0.5 text-xs font-bold rounded-full" :class="getBadgeClass(tpl.badge)">{{ tpl.badge }}</span>
                            <div class="absolute top-3 right-3 flex items-center gap-1 bg-white/90 backdrop-blur px-2 py-0.5 rounded-full text-xs font-semibold text-gray-700">
                                <svg class="w-3.5 h-3.5 text-amber-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                {{ tpl.rating }}
                            </div>
                        </div>
                        <div class="flex flex-col flex-1 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-semibold text-gray-900 text-sm leading-snug">{{ tpl.name }}</h3>
                                <span class="font-bold text-indigo-600 text-sm whitespace-nowrap">{{ formatPrice(tpl.price) }}</span>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- ==================== ZOOM MODAL ==================== -->
        <Teleport to="body">
            <div v-if="isZoomed" class="fixed inset-0 z-[100] bg-black/95 flex items-center justify-center p-4 sm:p-8 cursor-zoom-out" @click="isZoomed = false">
                <div class="w-full max-w-5xl h-[60vh] sm:h-[75vh] rounded-3xl flex items-center justify-center shadow-2xl overflow-hidden" :class="template.previewImageUrl ? 'bg-slate-200' : template.previews[activePreview]">
                    <img v-if="template.previewImageUrl" :src="template.previewImageUrl" class="w-full h-full object-contain" alt="">
                    <span v-else class="text-white/15 text-[12rem] font-black select-none">{{ template.name.charAt(0) }}</span>
                </div>
                <button class="absolute top-5 right-5 w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center text-2xl transition-colors">&times;</button>
                <div class="absolute bottom-6 flex gap-2">
                    <button v-for="(preview, i) in template.previews" :key="i" @click.stop="activePreview = i"
                        :class="[preview, 'w-12 h-8 rounded-lg border-2 transition-all', activePreview === i ? 'border-white' : 'border-white/30 opacity-60 hover:opacity-100']" />
                </div>
            </div>
        </Teleport>

        <!-- ==================== FOOTER ==================== -->
        <footer class="bg-gray-900 text-gray-400 py-10 px-4 mt-16">
            <div class="max-w-7xl mx-auto text-center">
                <div class="flex items-center justify-center gap-2.5 mb-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-xl flex items-center justify-center"><span class="text-white font-bold text-xs">MT</span></div>
                    <span class="font-bold text-white tracking-tight">Market<span class="text-indigo-400">Template</span></span>
                </div>
                <p class="text-sm">&copy; {{ new Date().getFullYear() }} MarketTemplate. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>
