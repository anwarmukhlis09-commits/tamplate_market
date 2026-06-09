<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    orderId: { type: String, required: true },
    template: { type: Object, default: () => ({}) },
});
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
            <div class="flex justify-between">
                <span class="text-slate-500">Order ID</span>
                <span class="font-mono text-xs font-semibold text-slate-800">{{ orderId }}</span>
            </div>
            <div v-if="template.name" class="flex justify-between">
                <span class="text-slate-500">Template</span>
                <span class="font-semibold text-slate-800">{{ template.name }}</span>
            </div>
        </div>

        <div class="space-y-2.5">
            <a v-if="template.id" :href="`/template/${template.id}/download`" class="block w-full py-3.5 text-sm font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-md transition-colors">
                <span class="inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download Template
                </span>
            </a>
            <Link v-if="template.id" :href="`/template/${template.id}`" class="block w-full py-3 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                Lihat Detail Template
            </Link>
            <Link href="/dashboard/purchases" class="block w-full py-2.5 text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors">
                Lihat Riwayat Pembelian →
            </Link>
        </div>
    </div>
</div>
</template>
