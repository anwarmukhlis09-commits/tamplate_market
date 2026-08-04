<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ClientLayout from '@/Layouts/ClientLayout.vue';

const props = defineProps({
    purchases: { type: Array, default: () => [] }
});

function formatPrice(p) {
    return p === 0 ? 'Gratis' : 'Rp ' + Number(p).toLocaleString('id-ID');
}

function getStatusBadge(status) {
    const badges = {
        completed: 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
        pending: 'bg-amber-50 text-amber-700 border-amber-200/60',
        failed: 'bg-rose-50 text-rose-700 border-rose-200/60',
        cancelled: 'bg-slate-100 text-slate-600 border-slate-200/60',
    };
    return badges[status] || 'bg-slate-50 text-slate-600 border-slate-200/60';
}

function getStatusLabel(status) {
    const labels = {
        completed: 'Selesai',
        pending: 'Menunggu Pembayaran',
        failed: 'Gagal',
        cancelled: 'Batal',
    };
    return labels[status] || status;
}
</script>

<template>
    <Head title="Riwayat Pembelian — Template Hotspot" />
    <ClientLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-extrabold text-slate-900">Riwayat Pembelian</h1>
            <p class="text-sm text-slate-500 mt-1">Daftar semua transaksi pembelian template Anda.</p>
        </div>

        <div v-if="purchases.length === 0" class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 rounded-2xl flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Belum ada pembelian</h3>
            <p class="text-sm text-slate-400 mb-6">Riwayat pembelian template Anda akan muncul di sini.</p>
            <Link href="/katalog" class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700">Jelajahi Katalog</Link>
        </div>

        <div v-else class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Order ID / Tanggal</th>
                            <th class="px-6 py-4">Template</th>
                            <th class="px-6 py-4">Total</th>
                            <th class="px-6 py-4">Metode</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        <tr v-for="p in purchases" :key="p.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-mono font-semibold text-slate-900">{{ p.order_id }}</div>
                                <div class="text-xs text-slate-400 mt-0.5">{{ p.created_at }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="p.template" class="font-bold text-slate-900">{{ p.template.name }}</div>
                                <div v-else class="text-slate-400">—</div>
                                <div v-if="p.template" class="text-xs text-slate-400 mt-0.5 capitalize">{{ p.template.category }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-indigo-600">
                                {{ formatPrice(p.amount) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-500 capitalize">
                                {{ p.payment_method === 'simulated' ? 'Simulasi' : p.payment_method }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border" :class="getStatusBadge(p.status)">
                                    {{ getStatusLabel(p.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link v-if="p.status === 'completed' && p.template" :href="`/template/${p.template.id}`" class="px-3 py-1.5 text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors">Detail</Link>
                                    <Link v-if="p.status === 'pending'" :href="`/payment/${p.order_id}`" class="px-3 py-1.5 text-xs font-bold text-white bg-amber-500 hover:bg-amber-600 rounded-lg transition-colors">Bayar</Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </ClientLayout>
</template>
