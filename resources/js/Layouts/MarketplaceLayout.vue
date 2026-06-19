<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentPath = computed(() => page.url);
const authUser = computed(() => page.props?.auth?.user);

function isActive(path) {
    if (path === '/') return currentPath.value === '/';
    return currentPath.value.startsWith(path);
}
</script>

<template>
<div class="min-h-screen bg-slate-50" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

    <!-- ═══ TOP NAVBAR (shared: Beranda & Katalog) ═══ -->
    <header class="fixed top-0 inset-x-0 z-50 bg-white/85 backdrop-blur-xl border-b border-slate-200/60 shadow-sm shadow-slate-200/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-6 h-16">

                <!-- Left: Logo + Menu -->
                <div class="flex items-center gap-8 min-w-0">
                    <Link href="/" class="flex items-center gap-2.5 shrink-0 group">
                        <img src="/images/logo.png" alt="MarketTemplate" class="h-10 w-auto group-hover:scale-105 transition-transform" />
                    </Link>

                    <nav class="hidden md:flex items-center gap-1">
                        <Link href="/" :class="isActive('/') && currentPath === '/' ? 'px-3.5 py-2 text-sm font-semibold text-indigo-700 bg-indigo-50 rounded-lg' : 'px-3.5 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors'">Beranda</Link>
                        <Link href="/katalog" :class="isActive('/katalog') ? 'px-3.5 py-2 text-sm font-semibold text-indigo-700 bg-indigo-50 rounded-lg' : 'px-3.5 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors'">Katalog</Link>
                    </nav>
                </div>

                <!-- Right: Auth buttons / User menu -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <!-- Guest: Login + Daftar -->
                    <template v-if="!authUser">
                        <Link href="/login" class="hidden sm:inline-flex px-3.5 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Login</Link>
                        <Link v-if="$page.props.canRegister" href="/register" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm transition-all">Daftar</Link>
                    </template>

                    <!-- Logged in: avatar + Dashboard -->
                    <template v-else>
                        <!-- Admin: langsung ke /admin -->
                        <Link v-if="authUser.is_admin" href="/admin" class="hidden sm:inline-flex px-3.5 py-2 text-sm font-semibold text-white bg-violet-600 rounded-lg hover:bg-violet-700 shadow-sm transition-all">Dashboard Admin</Link>
                        <!-- Customer: ke /dashboard -->
                        <Link v-else href="/dashboard" class="hidden sm:inline-flex px-3.5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm transition-all">Dashboard</Link>
                        <Link :href="route('logout')" method="post" as="button" class="text-xs text-slate-400 hover:text-rose-600 transition-colors">Keluar</Link>
                    </template>
                </div>
            </div>
        </div>
    </header>

    <!-- ═══ PAGE CONTENT ═══ -->
    <main>
        <slot />
    </main>
</div>
</template>
