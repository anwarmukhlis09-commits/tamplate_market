<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({ template: Object, canLogin: Boolean });

// ── Device mode ──────────────────────
const windowWidth = ref(window.innerWidth);
const deviceMode = ref('auto');
const isMobile = computed(() => deviceMode.value === 'mobile' || (deviceMode.value === 'auto' && windowWidth.value < 768));
function onResize() { windowWidth.value = window.innerWidth; }
onMounted(() => window.addEventListener('resize', onResize));
onUnmounted(() => window.removeEventListener('resize', onResize));

// ── Flow state (driven by iframe postMessage) ──
const currentPage = ref('login');
const iframeKey = ref(0);
const isConnecting = ref(false);
const showSuccess = ref(false);

function loadPage(page) { currentPage.value = page; iframeKey.value++; isConnecting.value = false; showSuccess.value = false; }

// Listen for messages from the demo script inside the iframe
onMounted(() => {
    window.addEventListener('message', onIframeMessage);
});
onUnmounted(() => {
    window.removeEventListener('message', onIframeMessage);
});

function onIframeMessage(e) {
    // Only accept messages from our own preview frames
    if (!e.data || typeof e.data.action !== 'string') return;
    switch (e.data.action) {
        case 'connecting':
            isConnecting.value = true;
            showSuccess.value = false;
            break;
        case 'success':
            isConnecting.value = false;
            showSuccess.value = true;
            break;
        case 'logout':
            isConnecting.value = false;
            showSuccess.value = false;
            // iframe will navigate itself, just track state
            setTimeout(() => { currentPage.value = 'login'; }, 1000);
            break;
    }
}
</script>

<template>
    <Head :title="template.name + ' — Preview Interaktif'" />

    <div class="min-h-screen flex flex-col bg-white">

        <!-- ═══ TOP BAR ═══ -->
        <header class="bg-slate-900 text-white shrink-0">
            <div class="max-w-full mx-auto px-4 h-12 flex items-center justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <Link :href="'/template/' + template.id" class="text-slate-400 hover:text-white shrink-0" title="Kembali">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <h1 class="text-sm font-semibold truncate">{{ template.name }}</h1>
                    <span class="text-[10px] bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded font-medium hidden sm:inline">DEMO</span>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Device toggle -->
                    <div class="flex bg-slate-700 rounded-lg p-0.5">
                        <button @click="deviceMode = 'mobile'" class="px-2.5 py-1 text-[11px] font-medium rounded-md transition-all"
                            :class="isMobile ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'">Mobile</button>
                        <button @click="deviceMode = 'desktop'" class="px-2.5 py-1 text-[11px] font-medium rounded-md transition-all"
                            :class="!isMobile ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'">Desktop</button>
                    </div>
                    <!-- Manual nav (tiny fallback) -->
                    <button @click="loadPage('login')" class="w-2 h-2 rounded-full transition-colors" :class="currentPage === 'login' ? 'bg-indigo-400' : 'bg-slate-600'" title="Login"></button>
                    <button @click="loadPage('status')" class="w-2 h-2 rounded-full transition-colors" :class="currentPage === 'status' ? 'bg-indigo-400' : 'bg-slate-600'" title="Status"></button>
                    <button @click="loadPage('logout')" class="w-2 h-2 rounded-full transition-colors" :class="currentPage === 'logout' ? 'bg-indigo-400' : 'bg-slate-600'" title="Logout"></button>
                    <Link :href="'/template/' + template.id + '/edit'" class="px-3 py-1.5 text-[11px] font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 ml-2">Edit & Beli</Link>
                </div>
            </div>
        </header>

        <!-- ═══ PREVIEW AREA ═══ -->
        <div class="flex-1 flex items-center justify-center bg-slate-200 p-2 sm:p-4"
            :style="{ backgroundImage: 'radial-gradient(#cbd5e1 1px, transparent 1px)', backgroundSize: '16px 16px' }">

            <!-- ═══ MOBILE ═══ -->
            <div v-if="isMobile" class="relative w-full max-w-[375px]">
                <div class="bg-slate-900 rounded-[2rem] p-2 shadow-2xl">
                    <div class="flex justify-center mb-1.5"><div class="w-14 h-3.5 bg-slate-800 rounded-full"></div></div>
                    <div class="rounded-[1.5rem] overflow-hidden bg-white relative" style="aspect-ratio:9/16;">
                        <iframe :key="iframeKey"
                            :src="'/template/' + template.id + '/preview-frame?page=' + currentPage + '&demo=1'"
                            class="w-full h-full border-0"
                            sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-top-navigation allow-top-navigation-by-user-activation" />

                        <!-- Overlay: Menghubungkan -->
                        <div v-if="isConnecting" class="absolute inset-0 bg-black/70 flex flex-col items-center justify-center z-10 backdrop-blur-sm transition-opacity">
                            <div class="w-10 h-10 border-3 border-indigo-400 border-t-transparent rounded-full animate-spin mb-3"></div>
                            <p class="text-white text-sm font-semibold">Menghubungkan...</p>
                            <p class="text-white/60 text-xs mt-1">Voucher: DEMO123</p>
                        </div>
                        <!-- Overlay: Sukses -->
                        <div v-if="showSuccess" class="absolute inset-0 bg-black/70 flex flex-col items-center justify-center z-10 backdrop-blur-sm transition-opacity">
                            <div class="w-14 h-14 bg-emerald-500 rounded-full flex items-center justify-center mb-3 animate-[pop_0.3s_ease-out]">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <p class="text-white text-sm font-bold">Login Berhasil</p>
                        </div>
                    </div>
                    <div class="flex justify-center mt-1.5"><div class="w-16 h-1 bg-slate-800 rounded-full"></div></div>
                </div>
                <div class="absolute -bottom-3 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[10px] px-3 py-1 rounded-full font-mono whitespace-nowrap">
                    Voucher: DEMO123
                </div>
            </div>

            <!-- ═══ DESKTOP ═══ -->
            <div v-else class="w-full max-w-5xl h-full flex flex-col bg-white rounded-xl shadow-2xl overflow-hidden ring-1 ring-slate-300">
                <div class="bg-slate-100 flex items-center gap-1.5 px-4 py-2.5 border-b border-slate-200 shrink-0">
                    <span class="w-3 h-3 rounded-full bg-red-400"></span>
                    <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                    <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                    <div class="flex-1 mx-3 bg-white border border-slate-200 rounded-md px-3 py-1 text-[11px] text-slate-400 truncate flex items-center gap-2">
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        hotspot/{{ template.name.toLowerCase().replace(/\s+/g, '-') }}/login
                    </div>
                </div>
                <div class="flex-1 relative bg-white">
                    <iframe :key="iframeKey"
                        :src="'/template/' + template.id + '/preview-frame?page=' + currentPage + '&demo=1'"
                        class="w-full h-full border-0" style="min-height:60vh;"
                        sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-top-navigation allow-top-navigation-by-user-activation" />

                    <!-- Overlay: Menghubungkan -->
                    <div v-if="isConnecting" class="absolute inset-0 bg-black/70 flex flex-col items-center justify-center z-10 backdrop-blur-sm transition-opacity">
                        <div class="w-10 h-10 border-3 border-indigo-400 border-t-transparent rounded-full animate-spin mb-3"></div>
                        <p class="text-white text-sm font-semibold">Menghubungkan...</p>
                        <p class="text-white/60 text-xs mt-1">Voucher: DEMO123</p>
                    </div>
                    <!-- Overlay: Sukses -->
                    <div v-if="showSuccess" class="absolute inset-0 bg-black/70 flex flex-col items-center justify-center z-10 backdrop-blur-sm transition-opacity">
                        <div class="w-14 h-14 bg-emerald-500 rounded-full flex items-center justify-center mb-3 animate-[pop_0.3s_ease-out]">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <p class="text-white text-sm font-bold">Login Berhasil</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@keyframes pop { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>
