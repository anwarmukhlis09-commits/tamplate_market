<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import MikrotikInstallGuide from "@/Components/MikrotikInstallGuide.vue";

const props = defineProps({
    template: { type: Object, required: true },
    purchase: { type: Object, required: true },
});

const showGuide = ref(false);
const formatPrice = (p) => "Rp " + Number(p).toLocaleString("id-ID");
</script>

<template>
    <Head :title="`Pembelian — ${template.name}`" />    <div class="min-h-screen bg-slate-50" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">
        <header class="bg-white border-b border-slate-200">
            <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-2.5">
                    <img src="/images/logo.png?v=2" alt="Template Hotspot" class="h-[70px] w-auto" />
                </Link>
                <Link href="/dashboard/purchases" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">← Kembali ke Riwayat</Link>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 py-10">
            <div class="mb-8">
                <p class="text-sm text-emerald-600 font-semibold mb-2">✓ {{ purchase.status === 'completed' ? 'Pembelian Selesai' : 'Sedang Diproses' }}</p>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Detail Pembelian</h1>
                <p class="text-slate-500 mt-1">Order ID: <span class="font-mono text-sm font-semibold">{{ purchase.orderId }}</span></p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h2 class="text-sm font-bold text-slate-900 mb-4">Template</h2>
                <div class="flex items-center gap-4 pb-5 border-b border-slate-100">
                    <div class="w-20 h-20 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center text-white shrink-0">
                        <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M2 20h.01M2 8.82a15 15 0 0120 0M5 12.859a10 10 0 0114 0"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-slate-900">{{ template.name }}</h3>
                        <p class="text-sm text-slate-500 capitalize">{{ template.category }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-extrabold text-indigo-600">{{ formatPrice(template.price) }}</p>
                        <p class="text-xs text-slate-500">{{ purchase.date }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mt-5">
                    <a :href="`/template/${template.id}/download?source=master`" class="flex items-center justify-center gap-2 px-4 py-3 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download
                    </a>
                    <button 
                        type="button" 
                        @click="showGuide = !showGuide" 
                        class="flex items-center justify-center gap-2 px-4 py-3 text-sm font-semibold rounded-xl transition-all"
                        :class="showGuide ? 'bg-amber-500 text-white shadow-sm' : 'bg-amber-50 text-amber-800 border border-amber-200/80 hover:bg-amber-100'"
                    >
                        <span>📖 Panduan Pasang</span>
                    </button>
                    <Link :href="`/template/${template.id}`" class="flex items-center justify-center gap-2 px-4 py-3 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                        Lihat Detail
                    </Link>
                    <Link v-if="true" :href="`/template/${template.id}/editor`" class="flex items-center justify-center gap-2 px-4 py-3 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                        Editor
                    </Link>
                </div>
            </div>

            <!-- ═══ EXPANDABLE / EMBEDDED PANDUAN MIKROTIK ═══ -->
            <div v-show="showGuide" class="mt-8 transition-all animate-fade-in">
                <MikrotikInstallGuide 
                    :templateName="template.name" 
                    :showSupportButton="true" 
                />
            </div>
            <div v-if="!showGuide" class="mt-6 p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">💡</span>
                    <div class="text-xs">
                        <span class="font-bold text-slate-800">Baru pertama kali upload template ke MikroTik?</span>
                        <p class="text-slate-500 mt-0.5">Lihat 4 langkah mudah pemasangan file ke Winbox tanpa ribet terminal.</p>
                    </div>
                </div>
                <button 
                    type="button" 
                    @click="showGuide = true" 
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-colors shrink-0"
                >
                    Buka Panduan
                </button>
            </div>
        </main>
    </div>
</template>
