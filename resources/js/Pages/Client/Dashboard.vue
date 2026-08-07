<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ClientLayout from '@/Layouts/ClientLayout.vue';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    recentOrders: { type: Array, default: () => [] },
});

function formatPrice(p) {
    return 'Rp ' + Number(p || 0).toLocaleString('id-ID');
}
</script>

<template>
    <Head title="Dashboard — Template Hotspot" />
    <ClientLayout>
        <div class="mb-8">
            <h1 class="text-2xl font-extrabold text-slate-900">Dashboard</h1>
            <p class="text-slate-500 mt-1">Selamat datang kembali, {{ $page.props.auth.user?.name }}!</p>
        </div>        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
            <!-- Card 1: Template Saya -->
            <div class="group relative bg-white rounded-2xl border border-[#EEF2F7] p-6 shadow-[0_8px_30px_rgba(15,23,42,0.03)] hover:shadow-[0_12px_35px_rgba(15,23,42,0.06)] hover:-translate-y-1 hover:border-indigo-100/85 transition-all duration-300 overflow-hidden flex items-center gap-5">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-indigo-50/40 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500 pointer-events-none"></div>
                <div class="w-14 h-14 bg-gradient-to-br from-indigo-50 to-indigo-100/60 rounded-2xl flex items-center justify-center border border-indigo-100/50 shrink-0 group-hover:scale-105 transition-transform duration-300">
                    <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                </div>
                <div class="relative z-10">
                    <div class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.templates_count ?? 0 }}</div>
                    <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mt-1">Template Saya</div>
                </div>
            </div>

            <!-- Card 2: Pembelian -->
            <div class="group relative bg-white rounded-2xl border border-[#EEF2F7] p-6 shadow-[0_8px_30px_rgba(15,23,42,0.03)] hover:shadow-[0_12px_35px_rgba(15,23,42,0.06)] hover:-translate-y-1 hover:border-emerald-100/85 transition-all duration-300 overflow-hidden flex items-center gap-5">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-emerald-50/40 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500 pointer-events-none"></div>
                <div class="w-14 h-14 bg-gradient-to-br from-emerald-50 to-emerald-100/60 rounded-2xl flex items-center justify-center border border-emerald-100/50 shrink-0 group-hover:scale-105 transition-transform duration-300">
                    <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>
                    </svg>
                </div>
                <div class="relative z-10">
                    <div class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.orders_count ?? 0 }}</div>
                    <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mt-1">Pembelian</div>
                </div>
            </div>

            <!-- Card 3: Total Belanja -->
            <div class="group relative bg-white rounded-2xl border border-[#EEF2F7] p-6 shadow-[0_8px_30px_rgba(15,23,42,0.03)] hover:shadow-[0_12px_35px_rgba(15,23,42,0.06)] hover:-translate-y-1 hover:border-violet-100/85 transition-all duration-300 overflow-hidden flex items-center gap-5">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-violet-50/40 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500 pointer-events-none"></div>
                <div class="w-14 h-14 bg-gradient-to-br from-violet-50 to-violet-100/60 rounded-2xl flex items-center justify-center border border-violet-100/50 shrink-0 group-hover:scale-105 transition-transform duration-300">
                    <svg class="w-7 h-7 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="relative z-10">
                    <div class="text-3xl font-black text-slate-900 tracking-tight">{{ formatPrice(stats.total_spent) }}</div>
                    <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mt-1">Total Belanja</div>
                </div>
            </div>
        </div>

        <!-- Recent orders / activity -->
        <div v-if="recentOrders && recentOrders.length > 0" class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-8 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900">Pembelian Terbaru</h2>
                <Link href="/dashboard/templates" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                    Lihat semua →
                </Link>
            </div>
            <ul class="divide-y divide-slate-100">
                <li v-for="o in recentOrders" :key="o.id" class="px-6 py-4 flex items-center gap-4 hover:bg-slate-50 transition-colors">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center text-white font-bold text-lg shrink-0">
                        <span v-if="o.template">{{ o.template.name?.charAt(0) || '?' }}</span>
                        <span v-else>?</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-slate-900 truncate">{{ o.template?.name || 'Template' }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            Order <code class="font-mono">{{ o.order_id }}</code> · {{ o.paid_at }}
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="font-bold text-indigo-600">{{ formatPrice(o.amount) }}</div>
                        <Link v-if="o.template" :href="`/template/${o.template.id}`" class="text-[10px] font-semibold text-slate-500 hover:text-indigo-600">
                            Lihat →
                        </Link>
                    </div>
                </li>
            </ul>
        </div>

        <!-- CTA Banner -->
        <div class="relative bg-gradient-to-br from-indigo-600 to-violet-700 rounded-3xl p-8 sm:p-10 text-white shadow-xl shadow-indigo-500/20 overflow-hidden group hover:shadow-2xl hover:shadow-indigo-500/30 transition-all duration-300">
            <!-- Decorative circle glows -->
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-white/10 rounded-full blur-3xl group-hover:scale-110 transition-transform duration-500 pointer-events-none"></div>
            <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-indigo-500/30 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 max-w-xl">
                <h2 class="text-2xl font-black mb-3 tracking-tight">Siap dapatkan template baru?</h2>
                <p class="text-indigo-100/90 text-sm leading-[1.6] mb-6">Jelajahi katalog template hotspot MikroTik premium kami dan temukan desain yang cocok untuk bisnis Anda.</p>
                <Link href="/katalog" class="inline-flex items-center px-5 py-3 text-sm font-bold text-indigo-600 bg-white hover:bg-indigo-50 rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all gap-2 duration-300">
                    Jelajahi Katalog
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </Link>
            </div>
        </div>
    </ClientLayout>
</template>