<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useToast } from '@/Composables/useToast.js';

const page = usePage();
const currentPath = computed(() => page.url);

function isActive(path) {
    if (path === '/admin') return currentPath.value === '/admin';
    return currentPath.value.startsWith(path);
}

// ── Toast System (shared, persists across layout remounts) ──
const { toasts, dismiss } = useToast();

// Watch for flash messages from backend.
// `immediate: true` is critical: when the layout mounts on the redirect
// target page (e.g. /admin/templates after a successful Create submit),
// `page.props.flash.success` is ALREADY set — a plain `watch` would never
// fire because no change has happened yet.
watch(() => page.props.flash?.success, (msg) => {
    if (msg) useToast().success('Berhasil', msg);
}, { immediate: true });

watch(() => page.props.flash?.error, (msg) => {
    if (msg) useToast().error('Gagal', msg);
}, { immediate: true });

const mainMenu = [
    { label: 'Dashboard', icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z', href: '/admin' },
    { label: 'Kelola Template', icon: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z', href: '/admin/templates' },
    { label: 'Transaksi', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', href: '/admin/transactions' },
    { label: 'User', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', href: '/admin/users' },
];

const settingsMenu = [
    { label: 'Kategori', icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', href: '/admin/categories' },
    { label: 'Pengaturan', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', href: '/admin/settings' },
];
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex">
        <!-- ═══ SIDEBAR ═══ -->
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 flex flex-col">

            <!-- Brand -->
            <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-800 shrink-0">
                <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-violet-500 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                    <span class="text-white font-extrabold text-sm">MT</span>
                </div>
                <div>
                    <h1 class="text-sm font-bold text-white">MarketTemplate</h1>
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest">Admin Panel</p>
                </div>
            </div>

            <!-- Quick Action -->
            <div class="px-3 pt-4">
                <Link href="/admin/templates/create"
                    class="flex items-center justify-center gap-2 w-full py-2.5 text-sm font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-500 transition-all shadow-lg shadow-indigo-500/25">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Tambah Template
                </Link>
            </div>

            <!-- Menu -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <!-- Main Menu -->
                <p class="px-3 text-[10px] font-semibold text-slate-500 uppercase tracking-widest mb-2 mt-2">Menu</p>
                <Link v-for="item in mainMenu" :key="item.href" :href="item.href"
                    class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-xl transition-all group"
                    :class="isActive(item.href) ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800'">
                    <svg class="w-5 h-5 shrink-0" :class="isActive(item.href) ? '' : 'text-slate-500 group-hover:text-white'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="item.icon"/></svg>
                    {{ item.label }}
                </Link>

                <!-- Settings -->
                <p class="px-3 text-[10px] font-semibold text-slate-500 uppercase tracking-widest mb-2 mt-8">Lainnya</p>
                <Link v-for="item in settingsMenu" :key="item.href" :href="item.href"
                    class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-xl transition-all group"
                    :class="isActive(item.href) ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800'">
                    <svg class="w-5 h-5 shrink-0" :class="isActive(item.href) ? '' : 'text-slate-500 group-hover:text-white'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="item.icon"/></svg>
                    {{ item.label }}
                </Link>
            </nav>

            <!-- User footer -->
            <div class="p-3 border-t border-slate-800">
                <div class="flex items-center gap-3 px-3 py-2">
                    <div class="w-8 h-8 bg-slate-700 rounded-full flex items-center justify-center text-white text-xs font-bold">
                        {{ $page.props.auth?.user?.name?.charAt(0) || 'A' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-white truncate">{{ $page.props.auth?.user?.name || 'Admin' }}</p>
                        <p class="text-[10px] text-slate-500">Administrator</p>
                    </div>
                    <Link href="/" class="text-slate-500 hover:text-white transition-colors" title="Ke Website">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- ═══ MAIN CONTENT ═══ -->
        <div class="flex-1 ml-64">
            <!-- Top bar -->
            <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-xl border-b border-slate-200 h-16 flex items-center px-6">
                <div class="flex items-center justify-between w-full">
                    <h2 class="text-lg font-bold text-slate-900"><slot name="title">Dashboard</slot></h2>
                    <div class="flex items-center gap-3">
                        <Link href="/admin/templates/create" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-sm transition-all">+ Tambah Template</Link>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="p-6">
                <slot />
            </main>
        </div>

        <!-- ═══ TOAST NOTIFICATIONS ═══ -->
        <Teleport to="body">
            <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-3 pointer-events-none max-w-[calc(100vw-2rem)]">
                <TransitionGroup name="toast" tag="div" class="flex flex-col gap-3">
                    <div v-for="t in toasts" :key="t.id"
                        class="pointer-events-auto bg-white rounded-2xl shadow-2xl ring-1 ring-slate-900/5 pl-3 pr-2 py-3 flex items-start gap-3 w-[360px] max-w-full border-l-[4px] toast-card"
                        :class="{
                            'border-l-emerald-500': t.type === 'success',
                            'border-l-red-500':     t.type === 'error',
                            'border-l-sky-500':     t.type === 'info',
                        }">

                        <!-- Icon circle -->
                        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                            :class="{
                                'bg-emerald-100': t.type === 'success',
                                'bg-red-100':     t.type === 'error',
                                'bg-sky-100':     t.type === 'info',
                            }">
                            <svg v-if="t.type === 'success'" class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg v-else-if="t.type === 'error'" class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                            </svg>
                            <svg v-else class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>

                        <!-- Title + message -->
                        <div class="flex-1 min-w-0 pt-0.5">
                            <h4 v-if="t.title" class="text-sm font-semibold text-slate-900 leading-tight">{{ t.title }}</h4>
                            <p v-if="t.message" class="text-xs text-slate-500 mt-1 leading-relaxed break-words whitespace-pre-line">{{ t.message }}</p>
                        </div>

                        <!-- Close -->
                        <button @click="dismiss(t.id)"
                            class="shrink-0 w-7 h-7 -mr-0.5 -mt-0.5 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                            aria-label="Tutup notifikasi">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </TransitionGroup>
            </div>
        </Teleport>
    </div>
</template>

<style>
.toast-enter-active { transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1); }
.toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from   { opacity: 0; transform: translateX(120%) scale(0.95); }
.toast-leave-to     { opacity: 0; transform: translateX(120%) scale(0.95); }
.toast-move         { transition: transform 0.3s ease; }

/* Progress bar on bottom of toast — visual hint of auto-dismiss */
.toast-card { position: relative; overflow: hidden; }
.toast-card::after {
    content: '';
    position: absolute;
    left: 0; bottom: 0;
    height: 3px;
    width: 100%;
    background: currentColor;
    opacity: 0.15;
    animation: toast-progress 4s linear forwards;
}
.border-l-emerald-500::after { background: #10b981; }
.border-l-red-500::after     { background: #ef4444; }
.border-l-sky-500::after     { background: #0ea5e9; }

@keyframes toast-progress {
    from { width: 100%; }
    to   { width: 0%; }
}
</style>
