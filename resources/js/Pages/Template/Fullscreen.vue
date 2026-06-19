<script setup>
import { Head, router } from '@inertiajs/vue3';
import { onMounted, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    template: { type: Object, required: true },
});

const showChrome = ref(true);
let idleTimer = null;

function showUi() {
    showChrome.value = true;
    clearTimeout(idleTimer);
    idleTimer = setTimeout(() => { showChrome.value = false; }, 2500);
}

function handleKey(e) {
    if (e.key === 'Escape') {
        router.visit(`/template/${props.template.id}`);
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKey);
    document.addEventListener('mousemove', showUi);
    document.addEventListener('touchstart', showUi);
    showUi();
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKey);
    document.removeEventListener('mousemove', showUi);
    document.removeEventListener('touchstart', showUi);
    clearTimeout(idleTimer);
});
</script>

<template>
<Head :title="`Preview ${template.name} — Fullscreen`" />

<!-- Fullscreen: no chrome, no scroll, 100vh iframe -->
<div class="fixed inset-0 z-50 bg-slate-900 select-none" @mousemove="showUi">

    <!-- Floating chrome (auto-hide after 2.5s idle) -->
    <transition
        enter-active-class="transition-opacity duration-200"
        leave-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        leave-to-class="opacity-0"
    >
        <div v-if="showChrome" class="absolute inset-x-0 top-0 z-10 flex items-center justify-between p-4 sm:p-5 bg-gradient-to-b from-black/60 to-transparent">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center shadow-lg shrink-0">
                    <span class="text-white font-extrabold text-sm">MT</span>
                </div>
                <div class="min-w-0">
                    <p class="text-white text-sm font-semibold truncate">{{ template.name }}</p>
                    <p class="text-white/60 text-xs">Preview Fullscreen</p>
                </div>
            </div>
            <button @click="router.visit(`/template/${template.id}`)" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white/15 hover:bg-white/25 text-white text-sm font-semibold rounded-xl backdrop-blur transition-colors" title="Tutup (ESC)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span class="hidden sm:inline">Tutup</span>
            </button>
        </div>
    </transition>

    <!-- Iframe: full viewport -->
    <iframe
        :src="`/templates/${template.id}/preview/login.html`"
        class="w-full h-full border-0 bg-white"
        frameborder="0"
        allowfullscreen
    ></iframe>
</div>
</template>
