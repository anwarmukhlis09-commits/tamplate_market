<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    orderId: { type: String, required: true },
    user: { type: Object, default: () => ({}) },
});

const form = useForm({});

const orderShort = computed(() => props.orderId.slice(0, 16) + '...');

function payNow() {
    form.post(`/payment/${props.orderId}/process`);
}
</script>

<template>
<Head :title="`Bayar — ${orderId}`" />

<div class="min-h-screen bg-gradient-to-br from-[#0F172A] via-[#312E81] to-[#6D28D9] flex items-center justify-center p-6" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-200">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        </div>

        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-1">Selesaikan Pembayaran</h1>
        <p class="text-sm text-slate-500 mb-6">Order ID: <span class="font-mono text-xs font-semibold">{{ orderShort }}</span></p>

        <div class="bg-slate-50 rounded-2xl p-5 mb-6 text-left">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Metode Pembayaran</p>
            <p class="text-sm font-semibold text-slate-800">Midtrans Payment Gateway</p>
            <p class="text-xs text-slate-500 mt-1">Transfer bank · E-Wallet · Kartu Kredit</p>
        </div>

        <button @click="payNow" :disabled="form.processing" class="w-full py-3.5 text-sm font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all disabled:opacity-50">
            <span v-if="form.processing">Menghubungkan ke payment gateway...</span>
            <span v-else>Bayar Sekarang</span>
        </button>

        <Link :href="`/template/${orderId.split('-')[2] || ''}`" class="block mt-4 text-xs text-slate-400 hover:text-slate-600 transition-colors">
            ← Kembali ke detail template
        </Link>
    </div>
</div>
</template>
