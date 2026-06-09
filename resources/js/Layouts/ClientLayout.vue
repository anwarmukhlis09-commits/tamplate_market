<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentPath = computed(() => page.url);

function isActive(path) {
    if (path === '/dashboard') return currentPath.value === '/dashboard';
    return currentPath.value.startsWith(path);
}

const menuItems = [
    { label: 'Dashboard', icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z', href: '/dashboard' },
    { label: 'Template Saya', icon: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z', href: '/dashboard/templates' },
    { label: 'Riwayat Pembelian', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', href: '/dashboard/purchases' },
];
</script>

<template>
    <div class="min-h-screen bg-slate-50">

        <!-- ═══ TOP NAVBAR ═══ -->
        <header class="sticky top-0 z-50 bg-white border-b border-slate-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">

                    <!-- Left: Logo + Menu -->
                    <div class="flex items-center gap-8">
                        <Link href="/" class="flex items-center gap-2.5 shrink-0">
                            <div class="w-9 h-9 bg-gradient-to-br from-indigo-600 to-violet-600 rounded-xl flex items-center justify-center shadow-md shadow-indigo-200">
                                <span class="text-white font-extrabold text-sm">MT</span>
                            </div>
                            <span class="font-bold text-lg text-slate-900 hidden sm:inline">Market<span class="text-indigo-600">Template</span></span>
                        </Link>

                        <nav class="hidden md:flex items-center gap-1">
                            <Link v-for="item in menuItems" :key="item.href" :href="item.href"
                                class="px-3.5 py-2 text-sm font-medium rounded-lg transition-all flex items-center gap-2"
                                :class="isActive(item.href) ? 'bg-indigo-50 text-indigo-700' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-100'">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"/></svg>
                                {{ item.label }}
                            </Link>
                        </nav>
                    </div>

                    <!-- Right: User -->
                    <div class="flex items-center gap-4">
                        <Link href="/katalog" class="text-sm text-slate-500 hover:text-indigo-600 transition-colors hidden sm:block">← Katalog</Link>
                        <div class="flex items-center gap-3 pl-4 border-l border-slate-200">
                            <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-violet-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                {{ $page.props.auth?.user?.name?.charAt(0) || 'U' }}
                            </div>
                            <div class="hidden sm:block">
                                <p class="text-sm font-semibold text-slate-900">{{ $page.props.auth?.user?.name || 'User' }}</p>
                                <p class="text-xs text-slate-400">Pelanggan</p>
                            </div>
                            <Link :href="route('logout')" method="post" as="button" class="text-xs text-slate-400 hover:text-red-500 transition-colors">Keluar</Link>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- ═══ PAGE CONTENT ═══ -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <slot />
        </main>
    </div>
</template>
