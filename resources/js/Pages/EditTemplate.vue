<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, reactive, computed, watch } from 'vue';
const props = defineProps({
    template: { type: Object, default: null },
    canLogin: Boolean,
});

// ── Editor state ─────────────────────
const config = reactive({
    businessName: 'WIFI HOTSPOT',
    runningText: 'Selamat datang! Nikmati internet cepat dan stabil.',
    primaryColor: '#4F46E5',
    bgGradient: true,
    bgColor1: '#4F46E5',
    bgColor2: '#7C3AED',
    loginBtnText: 'Login Hotspot',
    showVoucher: true,
    vouchers: [
        { name: '1 Jam', price: 'Rp 1K', duration: '1 JAM', highlight: false },
        { name: '5 Jam', price: 'Rp 3K', duration: '5 JAM', highlight: false },
        { name: '24 Jam', price: 'Rp 5K', duration: '1 HARI', highlight: true },
    ],
    whatsapp: '',
    footerText: 'Powered by MarketTemplate',
});

// ── Active section ───────────────────
const activeSection = ref('nama');

// ── Logo state ───────────────────────
const logoFile = ref(null);
const logoPreview = ref(null);       // Object URL for <img> preview
const logoSrcOriginal = ref(null);   // Original image for crop
const logoError = ref('');
const showCrop = ref(false);

// Crop state
const cropZoom = ref(1);
const cropX = ref(0);
const cropY = ref(0);
const cropDragging = ref(false);
const cropStartX = ref(0);
const cropStartY = ref(0);
const cropOrigX = ref(0);
const cropOrigY = ref(0);

// ── File input ref ───────────────────
const fileInput = ref(null);

// ── Computed styles for preview ──────
const previewBg = computed(() => {
    if (config.bgGradient) return `linear-gradient(135deg, ${config.bgColor1}, ${config.bgColor2})`;
    return config.bgColor1;
});

// ── Logo methods ─────────────────────
const ALLOWED_TYPES = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'];
const MAX_SIZE = 2 * 1024 * 1024; // 2MB

function handleLogoUpload(e) {
    const file = e.target.files?.[0];
    logoError.value = '';

    if (!file) return;
    if (!ALLOWED_TYPES.includes(file.type)) {
        logoError.value = 'Format tidak didukung. Gunakan PNG, JPG, JPEG, atau SVG.';
        return;
    }
    if (file.size > MAX_SIZE) {
        logoError.value = 'Ukuran file maksimal 2MB.';
        return;
    }

    logoFile.value = file;

    // For SVG, use as-is; for raster, create object URL for preview
    if (file.type === 'image/svg+xml') {
        const reader = new FileReader();
        reader.onload = (ev) => {
            logoPreview.value = ev.target.result;
            logoSrcOriginal.value = ev.target.result;
            showCrop.value = false; // No crop for SVG
        };
        reader.readAsDataURL(file);
    } else {
        logoPreview.value = URL.createObjectURL(file);
        logoSrcOriginal.value = logoPreview.value;
        resetCrop();
        showCrop.value = true;
    }
}

function removeLogo() {
    if (logoPreview.value && logoFile.value?.type !== 'image/svg+xml') {
        URL.revokeObjectURL(logoPreview.value);
    }
    logoFile.value = null;
    logoPreview.value = null;
    logoSrcOriginal.value = null;
    logoError.value = '';
    showCrop.value = false;
    resetCrop();
    if (fileInput.value) fileInput.value.value = '';
}

function triggerUpload() {
    fileInput.value?.click();
}

// ── Crop methods ─────────────────────
function resetCrop() { cropZoom.value = 1; cropX.value = 0; cropY.value = 0; }

function zoomIn() { cropZoom.value = Math.min(3, +(cropZoom.value + 0.2).toFixed(1)); }
function zoomOut() { cropZoom.value = Math.max(0.5, +(cropZoom.value - 0.2).toFixed(1)); }

function startCropDrag(e) {
    if (!showCrop.value) return;
    cropDragging.value = true;
    const t = e.touches?.[0] || e;
    cropStartX.value = t.clientX;
    cropStartY.value = t.clientY;
    cropOrigX.value = cropX.value;
    cropOrigY.value = cropY.value;
}
function onCropDrag(e) {
    if (!cropDragging.value) return;
    e.preventDefault();
    const t = e.touches?.[0] || e;
    cropX.value = cropOrigX.value + (t.clientX - cropStartX.value);
    cropY.value = cropOrigY.value + (t.clientY - cropStartY.value);
}
function stopCropDrag() { cropDragging.value = false; }

// ── Utilities ─────────────────────────
function darkenColor(hex, amount) {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return `rgb(${Math.max(0, r - amount)}, ${Math.max(0, g - amount)}, ${Math.max(0, b - amount)})`;
}

const presetColors = [
    { name: 'Indigo', value: '#4F46E5' }, { name: 'Biru', value: '#2563EB' },
    { name: 'Teal', value: '#0D9488' }, { name: 'Hijau', value: '#16A34A' },
    { name: 'Orange', value: '#EA580C' }, { name: 'Merah', value: '#DC2626' },
    { name: 'Ungu', value: '#7C3AED' }, { name: 'Pink', value: '#DB2777' },
    { name: 'Slate', value: '#475569' }, { name: 'Coklat', value: '#78350F' },
];

function selectColor(hex) { config.primaryColor = hex; config.bgColor1 = hex; config.bgColor2 = darkenColor(hex, 30); }

function resetConfig() {
    config.businessName = 'WIFI HOTSPOT';
    config.runningText = 'Selamat datang! Nikmati internet cepat dan stabil.';
    config.primaryColor = '#4F46E5'; config.bgGradient = true;
    config.bgColor1 = '#4F46E5'; config.bgColor2 = '#7C3AED';
    config.loginBtnText = 'Login Hotspot'; config.showVoucher = true;
    config.vouchers = [
        { name: '1 Jam', price: 'Rp 1K', duration: '1 JAM', highlight: false },
        { name: '5 Jam', price: 'Rp 3K', duration: '5 JAM', highlight: false },
        { name: '24 Jam', price: 'Rp 5K', duration: '1 HARI', highlight: true },
    ];
    config.whatsapp = ''; config.footerText = 'Powered by MarketTemplate';
    removeLogo();
}

const sections = [
    { id: 'nama', label: 'Identitas' },
    { id: 'warna', label: 'Warna' },
    { id: 'tombol', label: 'Tombol' },
    { id: 'voucher', label: 'Voucher' },
    { id: 'kontak', label: 'Kontak' },
];
</script>

<template>
    <Head :title="'Edit Template — ' + (template?.name || '')" />

    <div class="min-h-screen bg-slate-100 antialiased">

        <!-- ═══ TOP BAR ═══ -->
        <header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-slate-200">
            <div class="max-w-full mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between h-14">
                    <div class="flex items-center gap-3">
                        <Link :href="'/template/' + template?.id" class="p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </Link>
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 bg-gradient-to-br from-indigo-600 to-violet-600 rounded-lg flex items-center justify-center shadow-sm"><span class="text-white font-bold text-xs">MT</span></div>
                            <div><h1 class="text-sm font-bold text-slate-900">Edit Template</h1><p class="text-xs text-slate-400">{{ template?.name }}</p></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="resetConfig" class="px-3 py-1.5 text-xs font-medium text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Reset
                        </button>
                        <Link href="/katalog" class="px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-violet-600 rounded-xl hover:from-indigo-700 hover:to-violet-700 shadow-md shadow-indigo-200 transition-all">Lanjut Checkout</Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- ═══ EDITOR BODY ═══ -->
        <div class="flex flex-col lg:flex-row h-[calc(100vh-56px)]">

            <!-- ═══ LEFT PANEL ═══ -->
            <div class="lg:w-[420px] shrink-0 bg-white border-r border-slate-200 overflow-y-auto">
                <!-- Tabs -->
                <div class="flex border-b border-slate-200 overflow-x-auto sticky top-0 bg-white z-10">
                    <button v-for="sec in sections" :key="sec.id" @click="activeSection = sec.id"
                        class="flex-1 min-w-[60px] py-3 px-2 text-xs font-medium transition-colors border-b-2 text-center"
                        :class="activeSection === sec.id ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'">
                        {{ sec.label }}
                    </button>
                </div>

                <div class="p-5">

                    <!-- ── IDENTITAS ── -->
                    <div v-show="activeSection === 'nama'" class="space-y-5">

                        <!-- Upload Logo -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Logo</label>

                            <input ref="fileInput" type="file" accept="image/png,image/jpeg,image/jpg,image/svg+xml" class="hidden" @change="handleLogoUpload">

                            <!-- No logo: upload area -->
                            <div v-if="!logoPreview" @click="triggerUpload" class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/30 transition-all group">
                                <svg class="w-10 h-10 text-slate-300 group-hover:text-indigo-400 mx-auto mb-2 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <p class="text-sm font-medium text-slate-400 group-hover:text-indigo-600 transition-colors">Klik untuk upload logo</p>
                                <p class="text-xs text-slate-300 mt-1">PNG, JPG, JPEG, SVG — max 2MB</p>
                            </div>

                            <!-- Has logo: preview + actions -->
                            <div v-else class="space-y-3">
                                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                                    <img :src="logoPreview" alt="Logo preview" class="w-14 h-14 object-contain rounded-xl border border-slate-200 bg-white">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-700 truncate">{{ logoFile?.name }}</p>
                                        <p class="text-xs text-slate-400">{{ (logoFile?.size / 1024).toFixed(1) }} KB</p>
                                    </div>
                                    <button @click="removeLogo" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus logo">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                                <button @click="triggerUpload" class="w-full py-2 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">Ganti Logo</button>
                            </div>

                            <p v-if="logoError" class="text-xs text-red-500 mt-1.5">{{ logoError }}</p>
                        </div>

                        <!-- Crop UI -->
                        <div v-if="showCrop" class="bg-slate-50 rounded-xl p-4 space-y-3">
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Sesuaikan Logo</p>
                            <!-- Crop viewport -->
                            <div class="w-full aspect-square bg-slate-200 rounded-xl overflow-hidden relative cursor-grab active:cursor-grabbing select-none"
                                @mousedown="startCropDrag" @mousemove="onCropDrag" @mouseup="stopCropDrag" @mouseleave="stopCropDrag"
                                @touchstart.prevent="startCropDrag" @touchmove.prevent="onCropDrag" @touchend="stopCropDrag">
                                <img :src="logoSrcOriginal"
                                    class="absolute inset-0 w-full h-full object-cover pointer-events-none"
                                    :style="{ transform: `scale(${cropZoom}) translate(${cropX / cropZoom}px, ${cropY / cropZoom}px)` }">
                                <!-- Crosshair -->
                                <div class="absolute inset-4 border-2 border-dashed border-white/50 rounded-lg pointer-events-none"></div>
                            </div>
                            <!-- Crop controls -->
                            <div class="flex items-center justify-between">
                                <button @click="zoomOut" class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center hover:bg-slate-100 transition-colors text-slate-600 font-bold text-sm">−</button>
                                <span class="text-xs font-medium text-slate-400">{{ Math.round(cropZoom * 100) }}%</span>
                                <button @click="zoomIn" class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center hover:bg-slate-100 transition-colors text-slate-600 font-bold text-sm">+</button>
                                <button @click="resetCrop" class="px-3 py-1.5 text-xs font-medium text-slate-500 bg-white border border-slate-200 rounded-lg hover:bg-slate-100 transition-colors">Reset</button>
                            </div>
                        </div>

                        <!-- Brand name -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Nama WiFi / Brand</label>
                            <input v-model="config.businessName" type="text" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-shadow">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Teks Berjalan / Tagline</label>
                            <textarea v-model="config.runningText" rows="3" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-shadow resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Teks Footer</label>
                            <input v-model="config.footerText" type="text" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-shadow">
                        </div>
                    </div>

                    <!-- ── WARNA ── -->
                    <div v-show="activeSection === 'warna'" class="space-y-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Warna Utama</label>
                            <div class="grid grid-cols-5 gap-2">
                                <button v-for="c in presetColors" :key="c.value" @click="selectColor(c.value)" class="h-10 rounded-xl border-2 transition-all relative" :style="{ backgroundColor: c.value }"
                                    :class="config.primaryColor === c.value ? 'border-slate-900 ring-2 ring-offset-2 ring-slate-900/20 scale-110 shadow-lg' : 'border-transparent hover:scale-105'" :title="c.name">
                                    <span v-if="config.primaryColor === c.value" class="absolute inset-0 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Custom Warna</label>
                            <div class="flex items-center gap-3">
                                <input v-model="config.bgColor1" type="color" class="w-10 h-10 rounded-lg border border-slate-200 cursor-pointer p-0.5">
                                <input v-model="config.bgColor1" type="text" class="flex-1 px-3 py-2 text-sm font-mono border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                            <input v-model="config.bgGradient" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" id="useGradient">
                            <label for="useGradient" class="text-sm font-medium text-slate-700 cursor-pointer select-none">Gunakan gradient background</label>
                        </div>
                        <div v-if="config.bgGradient">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Warna Gradient ke-2</label>
                            <div class="flex items-center gap-3">
                                <input v-model="config.bgColor2" type="color" class="w-10 h-10 rounded-lg border border-slate-200 cursor-pointer p-0.5">
                                <input v-model="config.bgColor2" type="text" class="flex-1 px-3 py-2 text-sm font-mono border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- ── TOMBOL ── -->
                    <div v-show="activeSection === 'tombol'" class="space-y-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Teks Tombol Login</label>
                            <input v-model="config.loginBtnText" type="text" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-shadow">
                        </div>
                        <div class="p-4 bg-slate-50 rounded-xl">
                            <p class="text-xs text-slate-500 mb-3">Preview tombol:</p>
                            <button class="w-full py-3 rounded-xl text-sm font-bold text-white shadow-lg transition-all" :style="{ backgroundColor: config.primaryColor }">
                                {{ config.loginBtnText }}
                            </button>
                        </div>
                    </div>

                    <!-- ── VOUCHER ── -->
                    <div v-show="activeSection === 'voucher'" class="space-y-5">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Paket Voucher</label>
                            <label class="flex items-center gap-2 text-xs cursor-pointer select-none">
                                <input v-model="config.showVoucher" type="checkbox" class="w-3.5 h-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-slate-500">Tampilkan</span>
                            </label>
                        </div>
                        <div v-if="config.showVoucher" class="space-y-3">
                            <div v-for="(v, i) in config.vouchers" :key="i" class="p-4 bg-slate-50 rounded-xl space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-slate-500">Paket {{ i + 1 }}</span>
                                    <label class="flex items-center gap-1.5 text-xs cursor-pointer select-none">
                                        <input v-model="v.highlight" type="checkbox" class="w-3 h-3 rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                                        <span class="text-slate-400">Highlight</span>
                                    </label>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <input v-model="v.name" type="text" class="px-2.5 py-2 text-xs border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" placeholder="Nama">
                                    <input v-model="v.price" type="text" class="px-2.5 py-2 text-xs border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" placeholder="Harga">
                                    <input v-model="v.duration" type="text" class="px-2.5 py-2 text-xs border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" placeholder="Durasi">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── KONTAK ── -->
                    <div v-show="activeSection === 'kontak'" class="space-y-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Nomor WhatsApp</label>
                            <input v-model="config.whatsapp" type="text" placeholder="0812-3456-7890" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-shadow">
                        </div>
                    </div>

                </div>
            </div>

            <!-- ═══ RIGHT: LIVE PREVIEW ═══ -->
            <div class="flex-1 flex items-start justify-center p-4 sm:p-8 overflow-y-auto bg-slate-200/50"
                :style="{ backgroundImage: 'radial-gradient(#cbd5e1 1px, transparent 1px)', backgroundSize: '20px 20px' }">

                <div class="w-[340px] shrink-0 sticky top-20">
                    <p class="text-center text-xs font-medium text-slate-400 mb-3">Live Preview</p>

                    <div class="bg-slate-900 rounded-[2.5rem] p-2.5 shadow-2xl shadow-slate-400/40 ring-1 ring-slate-300/20">
                        <div class="flex justify-center mb-2.5"><div class="w-16 h-4 bg-slate-800 rounded-full"></div></div>

                        <div class="rounded-[2rem] overflow-hidden aspect-[9/16] relative" :style="{ background: previewBg }">
                            <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px]"></div>

                            <div class="absolute inset-0 flex flex-col items-center justify-center px-6 py-8 text-center">

                                <!-- Logo in preview -->
                                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4 ring-1 ring-white/30 shadow-lg overflow-hidden"
                                    :class="logoPreview ? 'bg-transparent' : 'bg-white/20 backdrop-blur'">
                                    <img v-if="logoPreview" :src="logoPreview" alt="Logo"
                                        class="w-full h-full object-contain"
                                        :style="{ transform: showCrop ? `scale(${cropZoom}) translate(${cropX / cropZoom}px, ${cropY / cropZoom}px)` : 'none' }">
                                    <svg v-else class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.07c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0"/></svg>
                                </div>

                                <h2 class="text-2xl font-extrabold text-white mb-1 tracking-tight drop-shadow-lg">{{ config.businessName }}</h2>
                                <p class="text-xs text-white/80 mb-5 leading-relaxed drop-shadow">{{ config.runningText }}</p>

                                <div class="w-full space-y-2.5 mb-5">
                                    <input type="text" placeholder="Username" disabled class="w-full px-4 py-2.5 text-sm bg-white/15 backdrop-blur border border-white/20 rounded-xl text-white placeholder:text-white/50 outline-none">
                                    <input type="password" placeholder="Password" disabled class="w-full px-4 py-2.5 text-sm bg-white/15 backdrop-blur border border-white/20 rounded-xl text-white placeholder:text-white/50 outline-none">
                                    <button class="w-full py-3 text-sm font-bold text-white rounded-xl shadow-lg transition-all" :style="{ backgroundColor: config.primaryColor }">
                                        {{ config.loginBtnText }}
                                    </button>
                                </div>

                                <p class="text-[10px] text-white/60 mb-5">Internet cepat untuk menemanimu.</p>

                                <div v-if="config.showVoucher" class="w-full">
                                    <p class="text-[10px] font-semibold text-white/70 mb-2 uppercase tracking-wide">Paket Voucher WiFi</p>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div v-for="(v, i) in config.vouchers" :key="i" class="rounded-xl py-3 px-1.5 text-center transition-all relative"
                                            :class="v.highlight ? 'bg-white text-slate-900 shadow-lg scale-105' : 'bg-white/15 text-white backdrop-blur'">
                                            <div v-if="v.highlight" class="absolute -top-1.5 right-1 text-[7px] font-bold text-white bg-amber-500 px-1.5 py-0.5 rounded-full">TOP</div>
                                            <div class="text-xs font-bold" :class="v.highlight ? 'text-slate-900' : 'text-white'">{{ v.price }}</div>
                                            <div class="text-[9px] mt-0.5 opacity-75">{{ v.duration }}</div>
                                            <div class="text-[8px] mt-0.5 opacity-60">{{ v.name }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-auto pt-4 w-full border-t border-white/20">
                                    <div class="flex items-center justify-center gap-1.5 text-[10px] text-white/70">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        WA {{ config.whatsapp }}
                                    </div>
                                    <p class="text-[9px] text-white/50 mt-1.5">{{ config.footerText }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center mt-2.5"><div class="w-20 h-1 bg-slate-700 rounded-full"></div></div>
                    </div>
                    <p class="text-center text-xs text-slate-400 mt-4">Edit panel kiri — preview update otomatis</p>
                </div>
            </div>
        </div>
    </div>
</template>
