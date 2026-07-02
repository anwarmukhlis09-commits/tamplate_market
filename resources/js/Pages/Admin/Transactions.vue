<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

// ═══ Props ═══
const props = defineProps({
    transactions: { type: Object, required: true },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

// ═══ State ═══
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const currentStats = ref(props.stats || {});
const lastUpdated = ref(props.stats?.last_updated || null);
const selectedOrder = ref(null); // null = modal tertutup

let pollTimer = null;
let searchDebounceTimer = null;

// ═══ Status mapping (4 status aktual dari code path) ═══
const statusMap = {
    pending:   { label: 'Pending',    class: 'bg-amber-50 text-amber-700 border-amber-200' },
    completed: { label: 'Lunas',      class: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
    expired:   { label: 'Kadaluarsa', class: 'bg-slate-100 text-slate-700 border-slate-300' },
    failed:    { label: 'Gagal',      class: 'bg-rose-50 text-rose-700 border-rose-200' },
};

// ═══ Computed ═══
const orders = computed(() => props.transactions?.data || []);
const pagination = computed(() => ({
    current: props.transactions?.current_page || 1,
    last: props.transactions?.last_page || 1,
    total: props.transactions?.total || 0,
    from: props.transactions?.from || 0,
    to: props.transactions?.to || 0,
}));

// ═══ Formatters ═══
function formatRupiah(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}
function formatDateTime(iso) {
    if (!iso) return '-';
    return new Date(iso).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });
}
function formatDate(iso) {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString('id-ID', { dateStyle: 'medium' });
}
function statusLabel(s) { return statusMap[s]?.label || s; }
function statusClass(s) { return statusMap[s]?.class || 'bg-slate-50 text-slate-600 border-slate-200'; }

// ═══ Polling: fetch stats real-time (5 detik) ═══
async function fetchStats() {
    // Skip kalau tab hidden — visibility listener akan trigger immediate fetch
    // saat tab visible lagi
    if (document.visibilityState === 'hidden') return;
    try {
        const r = await fetch(route('admin.transactions.stats'), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!r.ok) return;
        const j = await r.json();
        currentStats.value = j;
        lastUpdated.value = j.last_updated;
    } catch (e) {
        // Silent — next tick akan retry
    }
}

function startStatsPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(fetchStats, 5000);
}

function onVisibilityChange() {
    if (document.visibilityState === 'visible') {
        // Immediate fetch saat tab kembali aktif
        fetchStats();
    }
}

// ═══ Filter / Search / Pagination ═══
function applyFilters() {
    router.reload({
        only: ['transactions'],
        data: {
            search: search.value || undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        },
        preserveState: true,
    });
}

// Debounce search 300ms supaya tidak spam reload tiap keystroke
watch(search, () => {
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(applyFilters, 300);
});

function onStatusChange() {
    applyFilters();
}

function goToPage(page) {
    router.reload({
        only: ['transactions'],
        data: {
            search: search.value || undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
            page,
        },
        preserveState: true,
    });
}

// ═══ Modal detail ═══
function openDetail(order) {
    selectedOrder.value = order;
}
function closeDetail() {
    selectedOrder.value = null;
}

// ═══ Lifecycle ═══
onMounted(() => {
    fetchStats(); // immediate saat mount
    startStatsPolling();
    document.addEventListener('visibilitychange', onVisibilityChange);
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
    document.removeEventListener('visibilitychange', onVisibilityChange);
});

// ESC key tutup modal
function onKeydown(e) {
    if (e.key === 'Escape' && selectedOrder.value) closeDetail();
}
onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Head title="Transaksi — Admin" />
    <AdminLayout>
        <template #title>Transaksi</template>

        <!-- ═══ Stats cards (4, di-refresh tiap 5 detik via polling) ═══ -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-xs font-medium text-slate-500">Total Pendapatan</p>
                    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                </div>
                <p class="text-xl font-extrabold text-slate-900 tracking-tight">{{ formatRupiah(currentStats.total_revenue) }}</p>
                <p v-if="lastUpdated" class="text-[10px] text-slate-400 mt-1">Update: {{ formatDateTime(lastUpdated) }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500 mb-1">Lunas</p>
                <p class="text-xl font-extrabold text-emerald-600 tracking-tight">{{ currentStats.paid_count || 0 }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500 mb-1">Pending</p>
                <p class="text-xl font-extrabold text-amber-600 tracking-tight">{{ currentStats.pending_count || 0 }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500 mb-1">Gagal / Kadaluarsa</p>
                <p class="text-xl font-extrabold text-rose-600 tracking-tight">{{ currentStats.failed_count || 0 }}</p>
            </div>
        </div>

        <!-- ═══ Filters ═══ -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm mb-6">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari order ID, nama, email, atau nama template..."
                        class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none"
                    >
                </div>
                <select
                    v-model="statusFilter"
                    @change="onStatusChange"
                    class="text-sm border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none cursor-pointer"
                >
                    <option value="all">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Lunas</option>
                    <option value="expired">Kadaluarsa</option>
                    <option value="failed">Gagal</option>
                </select>
            </div>
        </div>

        <!-- ═══ Table ═══ -->
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
                        <tr
                            v-for="o in orders"
                            :key="o.id"
                            @click="openDetail(o)"
                            class="hover:bg-slate-50/50 transition-colors cursor-pointer"
                        >
                            <td class="px-5 py-3.5 font-mono text-xs font-semibold text-slate-700">{{ o.order_id }}</td>
                            <td class="px-5 py-3.5">
                                <p class="text-slate-800 font-medium">{{ o.user?.name || '-' }}</p>
                                <p class="text-xs text-slate-500">{{ o.user?.email || '' }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600 hidden md:table-cell">
                                <p class="truncate">{{ o.template?.name || '-' }}</p>
                                <p v-if="o.template?.category" class="text-xs text-slate-400 capitalize">{{ o.template.category }}</p>
                            </td>
                            <td class="px-5 py-3.5 font-bold text-slate-900">{{ formatRupiah(o.amount) }}</td>
                            <td class="px-5 py-3.5 text-slate-600 hidden lg:table-cell font-mono text-xs">{{ o.payment_method || '-' }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full border" :class="statusClass(o.status)">
                                    {{ statusLabel(o.status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-500 text-xs hidden lg:table-cell">{{ formatDateTime(o.created_at) }}</td>
                        </tr>
                        <tr v-if="orders.length === 0">
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400 text-sm">Tidak ada transaksi ditemukan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.last > 1" class="flex flex-col sm:flex-row items-center justify-between gap-3 px-5 py-4 border-t border-slate-200 bg-slate-50/50">
                <p class="text-xs text-slate-500">
                    Menampilkan <span class="font-semibold text-slate-700">{{ pagination.from }}</span> –
                    <span class="font-semibold text-slate-700">{{ pagination.to }}</span> dari
                    <span class="font-semibold text-slate-700">{{ pagination.total }}</span> transaksi
                </p>
                <div class="flex items-center gap-1.5">
                    <button
                        @click="goToPage(pagination.current - 1)"
                        :disabled="pagination.current <= 1"
                        class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        ← Prev
                    </button>
                    <span class="px-3 py-1.5 text-xs font-semibold text-slate-700">
                        {{ pagination.current }} / {{ pagination.last }}
                    </span>
                    <button
                        @click="goToPage(pagination.current + 1)"
                        :disabled="pagination.current >= pagination.last"
                        class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        Next →
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ Detail Modal ═══ -->
        <Teleport to="body">
            <div
                v-if="selectedOrder"
                @click.self="closeDetail"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
            >
                <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                        <div>
                            <h2 class="text-lg font-extrabold text-slate-900">Detail Transaksi</h2>
                            <p class="text-xs text-slate-500 font-mono">{{ selectedOrder.order_id }}</p>
                        </div>
                        <button @click="closeDetail" class="p-1.5 hover:bg-slate-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-5">
                        <!-- Section 1: Info Pesanan -->
                        <section>
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Info Pesanan</h3>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500 shrink-0">Status</dt>
                                    <dd>
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full border" :class="statusClass(selectedOrder.status)">
                                            {{ statusLabel(selectedOrder.status) }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500 shrink-0">Jumlah</dt>
                                    <dd class="font-extrabold text-indigo-600">{{ formatRupiah(selectedOrder.amount) }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500 shrink-0">Metode</dt>
                                    <dd class="font-mono text-xs text-slate-800">{{ selectedOrder.payment_method || '-' }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500 shrink-0">Pembeli</dt>
                                    <dd class="text-right">
                                        <p class="text-slate-800 font-medium">{{ selectedOrder.user?.name || '-' }}</p>
                                        <p class="text-xs text-slate-500">{{ selectedOrder.user?.email || '' }}</p>
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500 shrink-0">Template</dt>
                                    <dd class="text-right">
                                        <p class="text-slate-800 font-medium">{{ selectedOrder.template?.name || '-' }}</p>
                                        <p v-if="selectedOrder.template?.category" class="text-xs text-slate-500 capitalize">{{ selectedOrder.template.category }}</p>
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500 shrink-0">Tanggal order</dt>
                                    <dd class="text-slate-800">{{ formatDateTime(selectedOrder.created_at) }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500 shrink-0">Paid at</dt>
                                    <dd class="text-slate-800">{{ formatDateTime(selectedOrder.paid_at) }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500 shrink-0">Expired at</dt>
                                    <dd class="text-slate-800">{{ formatDateTime(selectedOrder.expired_at) }}</dd>
                                </div>
                            </dl>
                        </section>

                        <!-- Section 2: Info Tripay -->
                        <section v-if="selectedOrder.tripay_reference || selectedOrder.callback_payload">
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Info Tripay</h3>
                            <dl class="space-y-2 text-sm">
                                <div v-if="selectedOrder.tripay_reference" class="flex justify-between gap-3">
                                    <dt class="text-slate-500 shrink-0">Reference</dt>
                                    <dd class="font-mono text-xs text-slate-800">{{ selectedOrder.tripay_reference }}</dd>
                                </div>
                                <div v-if="selectedOrder.tripay_checkout_url" class="flex justify-between gap-3">
                                    <dt class="text-slate-500 shrink-0">Checkout URL</dt>
                                    <dd>
                                        <a :href="selectedOrder.tripay_checkout_url" target="_blank" rel="noopener" class="text-xs text-indigo-600 hover:text-indigo-800 break-all">
                                            Buka ↗
                                        </a>
                                    </dd>
                                </div>
                                <div v-if="selectedOrder.tripay_pay_code" class="flex flex-col gap-1">
                                    <dt class="text-slate-500">Virtual Account / Pay Code</dt>
                                    <dd class="font-mono text-sm font-bold text-slate-800 bg-slate-50 px-3 py-2 rounded-lg break-all">{{ selectedOrder.tripay_pay_code }}</dd>
                                </div>
                                <div v-if="selectedOrder.tripay_qr_string" class="flex flex-col gap-1">
                                    <dt class="text-slate-500">QRIS String</dt>
                                    <dd class="font-mono text-xs text-slate-600 bg-slate-50 px-3 py-2 rounded-lg break-all max-h-24 overflow-y-auto">{{ selectedOrder.tripay_qr_string }}</dd>
                                </div>
                                <div v-if="selectedOrder.callback_payload" class="flex flex-col gap-1">
                                    <dt class="text-slate-500">Callback Payload</dt>
                                    <dd>
                                        <pre class="text-[10px] text-slate-600 bg-slate-900 text-slate-100 px-3 py-2 rounded-lg overflow-x-auto max-h-40 overflow-y-auto">{{ JSON.stringify(selectedOrder.callback_payload, null, 2) }}</pre>
                                    </dd>
                                </div>
                            </dl>
                        </section>
                    </div>

                    <!-- Footer -->
                    <div class="sticky bottom-0 bg-slate-50 border-t border-slate-200 px-6 py-3 flex justify-end rounded-b-2xl">
                        <button @click="closeDetail" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-100">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>