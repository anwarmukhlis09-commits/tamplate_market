<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ClientLayout from '@/Layouts/ClientLayout.vue';
defineProps({ stats: Object });
</script>

<template>
    <Head title="Dashboard — MarketTemplate" />
    <ClientLayout>
        <div class="mb-8">
            <h1 class="text-2xl font-extrabold text-slate-900">Dashboard</h1>
            <p class="text-slate-500 mt-1">Selamat datang kembali, {{ $page.props.auth.user?.name }}!</p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center"><svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg></div>
                <div><div class="text-2xl font-extrabold text-slate-900">0</div><div class="text-sm text-slate-500">Template Saya</div></div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center"><svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></div>
                <div><div class="text-2xl font-extrabold text-slate-900">0</div><div class="text-sm text-slate-500">Pembelian</div></div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 bg-violet-100 rounded-xl flex items-center justify-center"><svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <div><div class="text-2xl font-extrabold text-slate-900">Rp 0</div><div class="text-sm text-slate-500">Total Belanja</div></div>
            </div>
        </div>

        <!-- CTA -->
        <div class="bg-gradient-to-br from-indigo-600 to-violet-700 rounded-2xl p-8 text-white shadow-xl shadow-indigo-300/30">
            <h2 class="text-xl font-extrabold mb-2">Siap dapatkan template?</h2>
            <p class="text-indigo-100 mb-5 max-w-md">Jelajahi katalog template hotspot MikroTik dan temukan yang cocok untuk bisnis Anda.</p>
            <Link href="/katalog" class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-indigo-700 bg-white rounded-xl hover:bg-slate-100 shadow transition-all gap-2">
                Jelajahi Katalog
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </Link>
        </div>
    </ClientLayout>
</template>
