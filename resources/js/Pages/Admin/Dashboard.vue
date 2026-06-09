<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    stats: Object,
    templates: Object,
});

const steps = [
    { num: 1, title: 'Tambah Template', desc: 'Upload file HTML, preview image, dan atur harga template.', href: '/admin/templates/create', cta: 'Mulai', color: 'indigo' },
    { num: 2, title: 'Atur & Publikasikan', desc: 'Review detail template, atur status ke Published agar muncul di katalog.', href: '/admin/templates', cta: 'Kelola', color: 'emerald' },
    { num: 3, title: 'Pantau Penjualan', desc: 'Lihat statistik penjualan dan kelola transaksi pelanggan.', href: '/admin/transactions', cta: 'Lihat', color: 'violet' },
];
</script>

<template>
    <Head title="Admin Dashboard — MarketTemplate" />

    <AdminLayout>
        <template #title>Dashboard</template>

        <!-- ═══ HERO CTA ═══ -->
        <div class="bg-gradient-to-br from-indigo-600 to-violet-700 rounded-2xl p-8 sm:p-10 mb-8 shadow-xl shadow-indigo-300/30 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/[0.06] rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-1/2 w-48 h-48 bg-white/[0.04] rounded-full translate-y-1/3"></div>
            <div class="relative z-10">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white mb-2">Mulai Jual Template Hotspot</h2>
                <p class="text-indigo-100 max-w-lg mb-6">Upload template hotspot MikroTik, atur harga, dan mulai dapatkan pelanggan. Mudah dan cepat.</p>
                <Link href="/admin/templates/create"
                    class="inline-flex items-center gap-2 px-6 py-3.5 bg-white text-indigo-700 font-bold rounded-xl hover:bg-slate-100 shadow-xl transition-all hover:shadow-2xl hover:-translate-y-0.5 text-base">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    + Tambah Template Baru
                </Link>
            </div>
        </div>

        <!-- ═══ STATS ═══ -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                    </div>
                    <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{{ stats.published }} Published</span>
                </div>
                <div class="text-3xl font-extrabold text-slate-900 mt-3">{{ stats.templates }}</div>
                <div class="text-sm text-slate-500">Total Template</div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center mb-2"><svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <div class="text-3xl font-extrabold text-slate-900">{{ stats.total_sold }}</div>
                <div class="text-sm text-slate-500">Total Terjual</div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <div class="w-10 h-10 bg-violet-100 rounded-xl flex items-center justify-center mb-2"><svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0"/></svg></div>
                <div class="text-3xl font-extrabold text-slate-900">{{ stats.user_count }}</div>
                <div class="text-sm text-slate-500">Total User</div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center mb-2"><svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg></div>
                <div class="text-3xl font-extrabold text-slate-900">{{ stats.draft }}</div>
                <div class="text-sm text-slate-500">Draft</div>
            </div>
        </div>

        <!-- ═══ WORKFLOW + QUICK LIST ═══ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Workflow Steps -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="font-bold text-slate-900 mb-5">Alur Menjual Template</h3>
                <div class="space-y-4">
                    <Link v-for="s in steps" :key="s.num" :href="s.href"
                        class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 hover:border-indigo-200 hover:bg-slate-50 transition-all group">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-sm"
                            :class="s.num === 1 ? 'bg-indigo-600' : s.num === 2 ? 'bg-emerald-600' : 'bg-violet-600'">
                            {{ s.num }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ s.title }}</h4>
                            <p class="text-sm text-slate-500 mt-0.5">{{ s.desc }}</p>
                        </div>
                        <span class="px-3 py-1.5 text-xs font-semibold rounded-lg shrink-0 self-center transition-colors"
                            :class="s.num === 1 ? 'bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100' : s.num === 2 ? 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100' : 'bg-violet-50 text-violet-600 group-hover:bg-violet-100'">
                            {{ s.cta }} →
                        </span>
                    </Link>
                </div>
            </div>

            <!-- Quick stats / status -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="font-bold text-slate-900 mb-4">Status Cepat</h3>
                <div class="space-y-4 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Published</span>
                        <span class="font-bold text-emerald-600 text-lg">{{ stats.published }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Draft</span>
                        <span class="font-bold text-slate-600 text-lg">{{ stats.draft }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">User Terdaftar</span>
                        <span class="font-bold text-slate-600 text-lg">{{ stats.user_count }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Total Terjual</span>
                        <span class="font-bold text-slate-600 text-lg">{{ stats.total_sold }}</span>
                    </div>
                </div>
                <div class="mt-5 pt-4 border-t border-slate-100">
                    <Link href="/admin/templates/create" class="block w-full py-2.5 text-sm font-semibold text-center text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-sm transition-all">
                        + Tambah Template Baru
                    </Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
