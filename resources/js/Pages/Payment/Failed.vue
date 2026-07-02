<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    orderId: { type: String, required: true },
    status: { type: String, default: 'failed' }, // 'failed' | 'expired'
    amount: { type: Number, default: 0 },
    paymentMethod: { type: String, default: '' },
    template: { type: Object, default: () => ({ id: null, name: 'Template' }) },
});

const isExpired = computed(() => props.status === 'expired');

function formatRupiah(n) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n);
}
</script>

<template>
<Head :title="`Pembayaran ${isExpired ? 'Kedaluwarsa' : 'Gagal'} — ${orderId}`" />

<div class="min-h-screen bg-gradient-to-br from-rose-50 via-white to-slate-50 flex items-center justify-center p-6" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 text-center">

        <!-- ═══ Error icon ═══ -->
        <div class="relative w-20 h-20 mx-auto mb-5">
            <div class="absolute inset-0 bg-rose-200 rounded-full animate-ping opacity-50"></div>
            <div class="relative w-full h-full bg-gradient-to-br from-rose-400 to-rose-600 rounded-full flex items-center justify-center shadow-xl shadow-rose-200">
                <svg v-if="isExpired" class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <svg v-else class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
        </div>

        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-1">
            {{ isExpired ? 'Pembayaran Kedaluwarsa' : 'Pembayaran Gagal' }}
        </h1>
        <p class="text-sm text-slate-500 mb-6">
            <span v-if="isExpired">Waktu pembayaran Anda telah habis. Silakan buat order baru.</span>
            <span v-else>Transaksi tidak dapat diproses. Coba lagi dengan metode lain.</span>
        </p>

        <!-- ═══ Detail card ═══ -->
        <div class="bg-slate-50 rounded-2xl p-4 mb-6 text-left space-y-2 text-sm">
            <div class="flex justify-between gap-3">
                <span class="text-slate-500 shrink-0">Order ID</span>
                <span class="font-mono text-xs font-semibold text-slate-800 truncate">{{ orderId }}</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="text-slate-500 shrink-0">Template</span>
                <span class="font-semibold text-slate-800 truncate">{{ template?.name }}</span>
            </div>
            <div v-if="paymentMethod" class="flex justify-between gap-3">
                <span class="text-slate-500 shrink-0">Metode</span>
                <span class="font-semibold text-slate-800 truncate">{{ paymentMethod }}</span>
            </div>
            <div class="flex justify-between gap-3 pt-2 border-t border-slate-200">
                <span class="text-slate-500 shrink-0">Total</span>
                <span class="font-extrabold text-rose-600">{{ formatRupiah(amount) }}</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="text-slate-500 shrink-0">Status</span>
                <span class="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full" :class="isExpired ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700'">
                    {{ isExpired ? 'EXPIRED' : 'FAILED' }}
                </span>
            </div>
        </div>

        <!-- ═══ Action buttons ═══ -->
        <div class="space-y-2.5">
            <!-- Primary: coba lagi (order baru) -->
            <Link v-if="template?.id" :href="`/checkout/${template.id}`" class="flex items-center justify-center gap-2 w-full py-3.5 text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 rounded-xl shadow-lg shadow-indigo-200 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Coba Lagi — Buat Order Baru
            </Link>

            <!-- Secondary: ganti metode (order lama) -->
            <Link :href="`/payment/${orderId}`" class="flex items-center justify-center gap-2 w-full py-3 text-sm font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Ganti Metode Pembayaran
            </Link>

            <!-- Tertiary: kembali ke katalog -->
            <Link href="/katalog" class="flex items-center justify-center gap-1.5 w-full py-2.5 text-xs font-medium text-slate-500 hover:text-slate-700 transition-colors">
                ← Kembali ke Katalog
            </Link>
        </div>
    </div>
</div>
</template>