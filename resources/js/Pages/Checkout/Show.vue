<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    template: { type: Object, required: true },
    auth: { type: Object, default: () => ({ user: null }) },
});

const formatPrice = (p) => 'Rp ' + Number(p).toLocaleString('id-ID');

const finalPrice = computed(() => props.template.discountPrice || props.template.price);
const tax = computed(() => Math.round(finalPrice.value * 0.11));
const total = computed(() => finalPrice.value + tax.value);

const form = useForm({
    payment_method: 'midtrans',
    voucher_code: '',
});

function submit() {
    form.post(`/checkout/${props.template.id}`);
}
</script>

<template>
<Head :title="`Checkout — ${template.name}`" />

<div class="min-h-screen bg-slate-50" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

    <!-- Navbar -->
    <header class="sticky top-0 z-50 bg-white/85 backdrop-blur-xl border-b border-slate-200/60 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
            <Link href="/" class="flex items-center gap-2.5">
                <img src="/images/logo.png" alt="MarketTemplate" class="h-10 w-auto" />
            </Link>
            <div class="text-sm text-slate-500">
                <Link href="/katalog" class="hover:text-indigo-600">Katalog</Link>
                <span class="mx-2">/</span>
                <Link :href="`/template/${template.id}`" class="hover:text-indigo-600">{{ template.name }}</Link>
                <span class="mx-2">/</span>
                <span class="text-slate-900 font-semibold">Checkout</span>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-2">Checkout</h1>
        <p class="text-slate-500 mb-8">Selesaikan pembelian template pilihan Anda</p>

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-6">

            <!-- LEFT: Form -->
            <div class="space-y-5">
                <!-- Template card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900 mb-3">Template</h2>
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-xl bg-gradient-to-br flex items-center justify-center text-white shrink-0"
                            :class="template.imageUrl ? '' : 'from-indigo-500 to-violet-500'">
                            <img v-if="template.imageUrl" :src="template.imageUrl" class="w-full h-full object-cover rounded-xl" />
                            <svg v-else class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M2 8.82a15 15 0 0120 0M5 12.859a10 10 0 0114 0"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-900 truncate">{{ template.name }}</h3>
                            <p class="text-xs text-slate-500 capitalize">{{ template.category }} · ID #{{ template.id }}</p>
                        </div>
                    </div>
                </div>

                <!-- Payment method -->
                <form @submit.prevent="submit" class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900 mb-3">Metode Pembayaran</h2>
                    <div class="space-y-2.5">
                        <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:border-indigo-300 transition-colors">
                            <input v-model="form.payment_method" type="radio" value="midtrans" class="w-4 h-4 text-indigo-600 focus:ring-indigo-500">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-800">Midtrans Payment Gateway</p>
                                <p class="text-xs text-slate-500">Transfer bank, e-wallet, kartu kredit</p>
                            </div>
                            <span class="text-xs font-bold text-indigo-600">Direkomendasikan</span>
                        </label>
                        <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:border-indigo-300 transition-colors">
                            <input v-model="form.payment_method" type="radio" value="dana" class="w-4 h-4 text-indigo-600 focus:ring-indigo-500">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-800">DANA</p>
                                <p class="text-xs text-slate-500">Saldo DANA</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:border-indigo-300 transition-colors">
                            <input v-model="form.payment_method" type="radio" value="ovo" class="w-4 h-4 text-indigo-600 focus:ring-indigo-500">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-800">OVO</p>
                                <p class="text-xs text-slate-500">Saldo OVO</p>
                            </div>
                        </label>
                    </div>

                    <button type="submit" :disabled="form.processing" class="mt-5 w-full py-3.5 text-sm font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-md disabled:opacity-50 transition-colors">
                        <span v-if="form.processing">Memproses...</span>
                        <span v-else>Bayar Sekarang — {{ formatPrice(total) }}</span>
                    </button>
                </form>
            </div>

            <!-- RIGHT: Summary -->
            <aside class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm h-fit lg:sticky lg:top-24">
                <h2 class="text-sm font-bold text-slate-900 mb-3">Ringkasan</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Harga</dt>
                        <dd class="text-slate-800 font-medium">{{ formatPrice(template.price) }}</dd>
                    </div>
                    <div v-if="template.discountPrice && template.discountPrice > template.price" class="flex justify-between text-emerald-600">
                        <dt>Diskon</dt>
                        <dd class="font-medium">-{{ formatPrice(template.discountPrice - template.price) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">PPN 11%</dt>
                        <dd class="text-slate-800 font-medium">{{ formatPrice(tax) }}</dd>
                    </div>
                    <div class="pt-3 border-t border-slate-200 flex justify-between">
                        <dt class="font-bold text-slate-900">Total</dt>
                        <dd class="font-extrabold text-indigo-600 text-lg">{{ formatPrice(total) }}</dd>
                    </div>
                </dl>

                <div class="mt-5 pt-5 border-t border-slate-100 text-xs text-slate-500 space-y-1.5">
                    <p>✓ Akses template selamanya</p>
                    <p>✓ Update gratis 1 tahun</p>
                    <p>✓ Support WhatsApp 24/7</p>
                    <p>✓ Garansi uang kembali 7 hari</p>
                </div>
            </aside>

        </div>
    </main>
</div>
</template>
