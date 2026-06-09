<script setup>
import { Head } from '@inertiajs/vue3';
import ClientLayout from '@/Layouts/ClientLayout.vue';
</script>
<template>
    <Head title="Riwayat Pembelian — MarketTemplate" />
    <ClientLayout>
        <h1 class="text-2xl font-extrabold text-slate-900 mb-4">Riwayat Pembelian</h1>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 rounded-2xl flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Belum ada pembelian</h3>
            <p class="text-sm text-slate-400">Riwayat pembelian template Anda akan muncul di sini.</p>
        </div>
    </ClientLayout>
</template>
