<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    transactions: { type: Array, default: () => [] },
});

const search = ref('');
const statusFilter = ref('all');

// Sample data (placeholder — integrate with backend later)
const sampleTransactions = ref([
    { id: 1, orderId: 'TRX-2026-001', buyer: 'Ahmad Fauzi', template: 'Hotspot Premium', amount: 49000, status: 'paid', method: 'DANA', date: '2026-06-08 14:23' },
    { id: 2, orderId: 'TRX-2026-002', buyer: 'Dian Permata', template: 'Hotel Luxury', amount: 89000, status: 'paid', method: 'Midtrans', date: '2026-06-08 11:05' },
    { id: 3, orderId: 'TRX-2026-003', buyer: 'Rudi Hartono', template: 'Cafe Login', amount: 29000, status: 'pending', method: 'Transfer Bank', date: '2026-06-08 09:18' },
    { id: 4, orderId: 'TRX-2026-004', buyer: 'Siti Nurhaliza', template: 'Voucher Blue', amount: 19000, status: 'paid', method: 'OVO', date: '2026-06-07 22:41' },
    { id: 5, orderId: 'TRX-2026-005', buyer: 'Budi Santoso', template: 'Hotspot Gaming', amount: 79000, status: 'paid', method: 'GoPay', date: '2026-06-07 16:55' },
    { id: 6, orderId: 'TRX-2026-006', buyer: 'Linda Wijaya', template: 'Sekolah Ceria', amount: 39000, status: 'refunded', method: 'DANA', date: '2026-06-07 08:30' },
    { id: 7, orderId: 'TRX-2026-007', buyer: 'Agus Pratama', template: 'ISP Enterprise', amount: 99000, status: 'paid', method: 'Midtrans', date: '2026-06-06 19:12' },
    { id: 8, orderId: 'TRX-2026-008', buyer: 'Dewi Lestari', template: 'Hotspot Minimalis', amount: 29000, status: 'paid', method: 'OVO', date: '2026-06-06 13:08' },
]);

const allTransactions = computed(() => props.transactions?.length ? props.transactions : sampleTransactions.value);

const filteredTransactions = computed(() => {
    let result = [...allTransactions.value];
    if (search.value) {
        const q = search.value.toLowerCase();
        result = result.filter(t =>
            t.orderId.toLowerCase().includes(q) ||
            t.buyer.toLowerCase().includes(q) ||
            t.template.toLowerCase().includes(q)
        );
    }
    if (statusFilter.value !== 'all') {
        result = result.filter(t => t.status === statusFilter.value);
    }
    return result;
});

const totalRevenue = computed(() =>
    allTransactions.value
        .filter(t => t.status === 'paid')
        .reduce((sum, t) => sum + t.amount, 0)
);

const totalPaid = computed(() => allTransactions.value.filter(t => t.status === 'paid').length);
const totalPending = computed(() => allTransactions.value.filter(t => t.status === 'pending').length);
const totalRefunded = computed(() => allTransactions.value.filter(t => t.status === 'refunded').length);

function formatPrice(p) { return 'Rp ' + Number(p).toLocaleString('id-ID'); }
function statusClass(s) {
    const m = {
        paid: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        pending: 'bg-amber-50 text-amber-700 border-amber-200',
        refunded: 'bg-rose-50 text-rose-700 border-rose-200',
    };
    return m[s] || 'bg-slate-50 text-slate-600 border-slate-200';
}
function statusLabel(s) {
    return { paid: 'Lunas', pending: 'Pending', refunded: 'Refund' }[s] || s;
}
</script>

<template>
    <Head title="Transaksi — Admin" />
    <AdminLayout>
        <template #title>Transaksi</template>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500 mb-1">Total Pendapatan</p>
                <p class="text-xl font-extrabold text-slate-900 tracking-tight">{{ formatPrice(totalRevenue) }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500 mb-1">Lunas</p>
                <p class="text-xl font-extrabold text-emerald-600 tracking-tight">{{ totalPaid }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500 mb-1">Pending</p>
                <p class="text-xl font-extrabold text-amber-600 tracking-tight">{{ totalPending }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500 mb-1">Refund</p>
                <p class="text-xl font-extrabold text-rose-600 tracking-tight">{{ totalRefunded }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm mb-6">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input v-model="search" type="search" placeholder="Cari order ID, nama pembeli, atau template..."
                        class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                </div>
                <select v-model="statusFilter" class="text-sm border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none cursor-pointer">
                    <option value="all">Semua Status</option>
                    <option value="paid">Lunas</option>
                    <option value="pending">Pending</option>
                    <option value="refunded">Refund</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50/50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Order ID</th>
                            <th class="text-left px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Pembeli</th>
                            <th class="text-left px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider hidden md:table-cell">Template</th>
                            <th class="text-left px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Jumlah</th>
                            <th class="text-left px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider hidden lg:table-cell">Metode</th>
                            <th class="text-left px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Status</th>
                            <th class="text-left px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider hidden lg:table-cell">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="t in filteredTransactions" :key="t.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3.5 font-mono text-xs font-semibold text-slate-700">{{ t.orderId }}</td>
                            <td class="px-5 py-3.5 text-slate-800 font-medium">{{ t.buyer }}</td>
                            <td class="px-5 py-3.5 text-slate-600 hidden md:table-cell">{{ t.template }}</td>
                            <td class="px-5 py-3.5 font-bold text-slate-900">{{ formatPrice(t.amount) }}</td>
                            <td class="px-5 py-3.5 text-slate-600 hidden lg:table-cell">{{ t.method }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full border" :class="statusClass(t.status)">
                                    {{ statusLabel(t.status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-500 text-xs hidden lg:table-cell">{{ t.date }}</td>
                        </tr>
                        <tr v-if="filteredTransactions.length === 0">
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400 text-sm">Tidak ada transaksi ditemukan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
