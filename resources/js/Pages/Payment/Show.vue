<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    orderId: { type: String, required: true },
    user: { type: Object, default: () => ({}) },
    channels: { type: Array, default: () => [] },
    amount: { type: [Number, String], default: 0 },
});

const form = useForm({
    method: '',
    phone: props.user?.phone || '',
});

const orderShort = computed(() => props.orderId.slice(0, 16) + '...');

function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number);
}

function selectChannel(code) {
    form.method = code;
}

function payNow() {
    if (!form.method) {
        alert('Silakan pilih metode pembayaran terlebih dahulu.');
        return;
    }
    // Normalisasi phone: hanya digit, dan pastikan prefix 62
    let phone = form.phone.replace(/\D/g, '');
    if (phone.startsWith('0')) phone = '62' + phone.slice(1);
    if (!phone.startsWith('62')) phone = '62' + phone;

    if (phone.length < 10 || phone.length > 15) {
        form.setError('phone', 'Nomor HP tidak valid (contoh: 081234567890).');
        return;
    }
    form.phone = phone;
    form.clearErrors();
    form.post(`/payment/${props.orderId}/process`);
}
</script>

<template>
<Head :title="`Bayar — ${orderId}`" />

<div class="min-h-screen bg-gradient-to-br from-[#0F172A] via-[#312E81] to-[#6D28D9] flex items-center justify-center p-6" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

    <div class="w-full max-w-lg bg-white rounded-3xl shadow-2xl p-8 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-200">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        </div>

        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-1">Pilih Metode Pembayaran</h1>
        <p class="text-sm text-slate-500 mb-2">Order ID: <span class="font-mono text-xs font-semibold">{{ orderShort }}</span></p>
        <p class="text-lg font-bold text-slate-800 mb-6">Total: {{ formatRupiah(amount) }}</p>

        <div class="mb-6 text-left">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nomor HP (untuk konfirmasi pembayaran)</label>
            <input
                v-model="form.phone"
                type="tel"
                placeholder="081234567890"
                class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:border-indigo-500 focus:ring-0 outline-none transition-colors"
                :class="form.errors.phone ? 'border-red-400' : ''"
            />
            <p v-if="form.errors.phone" class="text-xs text-red-500 mt-1.5">{{ form.errors.phone }}</p>
        </div>

        <div v-if="channels.length === 0" class="text-sm text-red-500 mb-6">
            Gagal memuat metode pembayaran. Cek konfigurasi Tripay Anda.
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6 text-left max-h-60 overflow-y-auto pr-2">
            <div v-for="channel in channels" :key="channel.code" 
                 @click="selectChannel(channel.code)"
                 class="border-2 rounded-xl p-3 cursor-pointer flex items-center gap-3 transition-all"
                 :class="form.method === channel.code ? 'border-indigo-600 bg-indigo-50' : 'border-slate-200 hover:border-indigo-300'">
                <img v-if="channel.icon_url" :src="channel.icon_url" :alt="channel.name" class="w-12 object-contain" />
                <div class="text-sm font-semibold text-slate-800 leading-tight">
                    {{ channel.name }}
                </div>
            </div>
        </div>

        <div v-if="form.errors.error" class="text-sm text-red-500 mb-4 bg-red-50 p-2 rounded">
            {{ form.errors.error }}
        </div>

        <button @click="payNow" :disabled="form.processing || !form.method" class="w-full py-3.5 text-sm font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all disabled:opacity-50">
            <span v-if="form.processing">Membuka Halaman Pembayaran...</span>
            <span v-else>Bayar Sekarang</span>
        </button>

        <Link :href="`/template/${orderId.split('-')[2] || ''}`" class="block mt-4 text-xs text-slate-400 hover:text-slate-600 transition-colors">
            ← Kembali ke detail template
        </Link>
    </div>
</div>
</template>
