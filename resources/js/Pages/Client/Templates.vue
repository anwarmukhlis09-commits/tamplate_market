<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ClientLayout from '@/Layouts/ClientLayout.vue';

const props = defineProps({
    myTemplates: { type: Array, default: () => [] },
});

// Sample data — daftar template yang sudah dibeli user
// Setiap entry scope ke template.id spesifik
const sampleMyTemplates = ref([
    { id: 1, name: 'Hotspot Premium', category: 'modern', price: 49000, purchasedAt: '2026-06-05', lastUpdated: '2026-06-08' },
    { id: 4, name: 'Hotspot Minimalis', category: 'minimalis', price: 29000, purchasedAt: '2026-06-01', lastUpdated: '2026-06-07' },
    { id: 19, name: 'Hospot Sekolah', category: 'modern', price: 0, purchasedAt: '2026-05-28', lastUpdated: '2026-06-04' },
]);

const allMyTemplates = computed(() => props.myTemplates?.length ? props.myTemplates : sampleMyTemplates.value);

function formatPrice(p) { return p === 0 ? 'Gratis' : 'Rp ' + Number(p).toLocaleString('id-ID'); }
</script>

<template>
    <Head title="Template Saya — MarketTemplate" />
    <ClientLayout>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900">Template Saya</h1>
                <p class="text-sm text-slate-500 mt-1">{{ allMyTemplates.length }} template yang sudah Anda beli</p>
            </div>
            <Link href="/katalog" class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Beli Lagi
            </Link>
        </div>

        <div v-if="allMyTemplates.length === 0" class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 rounded-2xl flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Belum ada template</h3>
            <p class="text-sm text-slate-400 mb-6">Template yang Anda beli akan muncul di sini.</p>
            <Link href="/katalog" class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700">Jelajahi Katalog</Link>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div v-for="t in allMyTemplates" :key="t.id" class="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-lg transition-all">
                <div class="aspect-[4/3] bg-gradient-to-br from-indigo-500 via-violet-500 to-purple-500 flex items-center justify-center text-white">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M2 8.82a15 15 0 0120 0M5 12.859a10 10 0 0114 0"/></svg>
                </div>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <h3 class="font-bold text-slate-900 text-sm leading-snug">{{ t.name }}</h3>
                        <span class="text-xs text-slate-500 font-mono">#{{ t.id }}</span>
                    </div>
                    <p class="text-xs text-slate-500 capitalize mb-3">{{ t.category }} · {{ formatPrice(t.price) }}</p>
                    <p class="text-xs text-slate-400 mb-4">Dibeli: {{ t.purchasedAt }} · Update: {{ t.lastUpdated }}</p>
                    <!-- SEMUA link scope ke t.id — tidak ada URL generic -->
                    <div class="grid grid-cols-2 gap-2">
                        <Link :href="`/template/${t.id}`" class="py-2 text-xs font-semibold text-center text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100">Detail</Link>
                        <Link :href="`/template/${t.id}/editor`" class="py-2 text-xs font-semibold text-center text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200">Editor</Link>
                        <a :href="`/template/${t.id}/download`" class="py-2 text-xs font-semibold text-center text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 col-span-2">Download ZIP</a>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>
