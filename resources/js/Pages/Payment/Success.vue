<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({
    orderId: { type: String, required: true },
    template: { type: Object, default: () => ({ id: null, name: 'Template', slug: null }) },
    canEdit: { type: Boolean, default: false },
});

const downloadState = ref('idle'); // 'idle' | 'downloading' | 'done' | 'error'
const downloadError = ref(null);

// Auto-trigger download ZIP saat halaman load (1.5 detik delay untuk
// user lihat "Pembayaran Berhasil" animation dulu).
//
// PENTING: pakai plain <a download> — BUKAN fetch + blob. Alasan:
// - Inertia SPA auto-add header X-Inertia: true ke semua XHR/fetch.
// - Server Inertia middleware proses response jadi HTML page (bukan
//   binary ZIP). r.blob() terima HTML, lalu di-save sebagai .zip —
//   user dapat file corrupt saat extract.
// - Solusi: anchor element native browser. Browser handle download
//   langsung via HTTP, ignore Inertia SPA layer (no XHR, no Inertia
//   header).
onMounted(async () => {
    if (!props.template.id) return;
    await new Promise(r => setTimeout(r, 1500));
    triggerDownload();
});

function triggerDownload() {
    downloadState.value = 'downloading';
    downloadError.value = null;
    try {
        const safeName = (props.template.name || 'template').replace(/[^A-Za-z0-9\-]/g, '_');
        const filename = `Template_ID${props.template.id}_${safeName}_edited.zip`;

        // Plain anchor — browser download langsung, bypass Inertia XHR
        const a = document.createElement('a');
        a.href = `/template/${props.template.id}/download`;
        a.download = filename;
        a.rel = 'noopener';
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        // Tunggu sebentar lalu set 'done' (browser mulai download stream)
        setTimeout(() => {
            downloadState.value = 'done';
        }, 1500);
    } catch (e) {
        downloadState.value = 'error';
        downloadError.value = e.message;
    }
}
</script>

<template>
<Head :title="`Pembayaran Berhasil — ${orderId}`" />

<div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-indigo-50 flex items-center justify-center p-6" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 text-center">
        <!-- Success icon -->
        <div class="relative w-20 h-20 mx-auto mb-5">
            <div class="absolute inset-0 bg-emerald-200 rounded-full animate-ping opacity-50"></div>
            <div class="relative w-full h-full bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full flex items-center justify-center shadow-xl shadow-emerald-200">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            </div>
        </div>

        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-1">Pembayaran Berhasil!</h1>
        <p class="text-sm text-slate-500 mb-6">Template siap di-download</p>

        <div class="bg-slate-50 rounded-2xl p-4 mb-6 text-left space-y-1.5 text-sm">
            <div class="flex justify-between gap-3">
                <span class="text-slate-500 shrink-0">Order ID</span>
                <span class="font-mono text-xs font-semibold text-slate-800 truncate">{{ orderId }}</span>
            </div>
            <div v-if="template.name" class="flex justify-between gap-3">
                <span class="text-slate-500 shrink-0">Template</span>
                <span class="font-semibold text-slate-800 truncate">{{ template.name }}</span>
            </div>
        </div>

        <!-- ═══ Primary action: Download ═══ -->
        <div class="space-y-2.5">
            <!-- Auto-download trigger button — reactive state -->
            <button v-if="template.id" @click="triggerDownload" :disabled="downloadState === 'downloading' || downloadState === 'done'" class="flex items-center justify-center gap-2 w-full py-3.5 text-sm font-bold text-white rounded-xl shadow-md transition-colors disabled:cursor-not-allowed" :class="downloadState === 'done' ? 'bg-emerald-600 shadow-emerald-200' : downloadState === 'error' ? 'bg-rose-600 hover:bg-rose-700 shadow-rose-200' : 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-200'">
                <!-- Downloading -->
                <svg v-if="downloadState === 'downloading'" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                <!-- Done -->
                <svg v-else-if="downloadState === 'done'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                <!-- Error -->
                <svg v-else-if="downloadState === 'error'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <!-- Idle -->
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>

                <span v-if="downloadState === 'downloading'">Menyiapkan download...</span>
                <span v-else-if="downloadState === 'done'">Template berhasil diunduh!</span>
                <span v-else-if="downloadState === 'error'">Gagal download — klik untuk coba lagi</span>
                <span v-else>Download Template</span>
            </button>

            <p v-if="downloadError" class="text-xs text-rose-600 text-center">{{ downloadError }}</p>

            <!-- ═══ Secondary shortcuts ═══ -->
            <div v-if="template.id" class="grid grid-cols-2 gap-2.5">
                <!-- Lihat detail template -->
                <Link :href="`/template/${template.id}`" class="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Lihat Template
                </Link>
                <!-- Edit template (untuk user yang punya akses) -->
                <Link v-if="canEdit" :href="`/template/${template.id}/edit`" class="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-white bg-violet-600 hover:bg-violet-700 rounded-xl shadow-sm transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Template
                </Link>
                <!-- Belanja lagi -->
                <Link href="/katalog" class="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Belanja Lagi
                </Link>
                <!-- Pesanan saya -->
                <Link href="/dashboard/purchases" class="flex items-center justify-center gap-1.5 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Pesanan Saya
                </Link>
            </div>

            <!-- Beranda -->
            <Link href="/" class="flex items-center justify-center gap-1.5 w-full py-2.5 text-xs font-medium text-slate-500 hover:text-slate-700 transition-colors mt-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Kembali ke Beranda
            </Link>
        </div>
    </div>
</div>
</template>
