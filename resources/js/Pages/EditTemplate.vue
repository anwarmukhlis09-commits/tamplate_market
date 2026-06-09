<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, reactive, computed, watch, onMounted } from 'vue';

const props = defineProps({
    template: { type: Object, default: null },
    canLogin: Boolean,
});

// ── Editor state (real-time) ───────────
const config = reactive({
    // General
    businessName: props.template?.name || 'WIFI HOTSPOT',
    subtitle: 'Selamat datang! Silakan login untuk mulai menggunakan internet.',
    footerText: 'Powered by Your ISP',
    // Login button
    loginBtnText: 'Login Hotspot',
    // Colors
    primaryColor: '#2563EB',
    buttonColor: '#2563EB',
    // Logo
    logoUrl: '',
    // Background
    bgStyle: 'gradient', // 'gradient' | 'solid' | 'image'
    bgColor1: '#2563EB',
    bgColor2: '#7C3AED',
    bgImageUrl: '',
    // Social login
    showSocial: true,
});

// ── UI state ───────────────────────────
const activeSection = ref('umum'); // umum, logo, background, warna, teks, tombol, sosial, css
const previewMode = ref('desktop'); // 'desktop' | 'mobile'
const saving = ref(false);
const lastSaved = ref(null);

const sections = [
    { id: 'umum',       label: 'Umum',        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z' },
    { id: 'logo',       label: 'Logo',        icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { id: 'background', label: 'Background',  icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { id: 'warna',      label: 'Warna',       icon: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-1.657 1.657m-4.99-1.243a2 2 0 01-1.414 0l-1.414-1.414a2 2 0 010-2.828l1.414-1.414a2 2 0 011.414 0l1.414 1.414a2 2 0 010 2.828l-1.414 1.414a2 2 0 01-1.414 0z' },
    { id: 'teks',       label: 'Teks',        icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
    { id: 'tombol',     label: 'Tombol',      icon: 'M15 7h3a2 2 0 012 2v8a2 2 0 01-2 2h-3m-6-12h-3a2 2 0 00-2 2v8a2 2 0 002 2h3m6-12v12' },
    { id: 'sosial',     label: 'Sosial Media', icon: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1' },
    { id: 'css',        label: 'CSS',         icon: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4' },
];

// ── Computed: live preview style ────────
const previewStyle = computed(() => {
    if (config.bgStyle === 'image' && config.bgImageUrl) {
        return { backgroundImage: `url(${config.bgImageUrl})`, backgroundSize: 'cover', backgroundPosition: 'center' };
    }
    if (config.bgStyle === 'solid') {
        return { background: config.bgColor1 };
    }
    return { background: `linear-gradient(135deg, ${config.bgColor1}, ${config.bgColor2})` };
});

// ── Save (placeholder) ────────────────
function save() {
    saving.value = true;
    // Real implementation: POST config to /template/{id}/editor
    setTimeout(() => {
        saving.value = false;
        lastSaved.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    }, 800);
}

// ── File upload handlers (placeholder) ──
function onLogoUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => { config.logoUrl = ev.target.result; };
    reader.readAsDataURL(file);
}
function onBgUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => { config.bgImageUrl = ev.target.result; };
    reader.readAsDataURL(file);
}

// ── Section icon helper ────────────────
function sectionIcon(id) {
    return sections.find(s => s.id === id)?.icon || '';
}
function sectionLabel(id) {
    return sections.find(s => s.id === id)?.label || '';
}
</script>

<template>
<Head :title="`Edit ${template?.name || 'Template'} — MarketTemplate`" />

<div class="min-h-screen bg-slate-50 flex flex-col" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; color: #0F172A;">

    <!-- ════════════ TOP HEADER ════════════ -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex items-center justify-between gap-4">
            <!-- Kiri: Back + nama template -->
            <div class="flex items-center gap-3 min-w-0">
                <Link :href="`/template/${template?.id || ''}`" class="shrink-0 w-9 h-9 flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors" title="Kembali ke Template Saya">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <div class="min-w-0">
                    <p class="text-xs text-slate-500 font-medium">Sedang mengedit</p>
                    <h1 class="text-sm sm:text-base font-bold text-slate-900 truncate">{{ template?.name || 'Template' }}</h1>
                </div>
            </div>

            <!-- Tengah: Toggle Desktop/Mobile -->
            <div class="hidden md:flex items-center gap-2 bg-slate-100 rounded-lg p-1">
                <button @click="previewMode = 'desktop'" :class="previewMode === 'desktop' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Desktop
                </button>
                <button @click="previewMode = 'mobile'" :class="previewMode === 'mobile' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Mobile
                </button>
            </div>

            <!-- Kanan: Action buttons -->
            <div class="flex items-center gap-2 shrink-0">
                <a :href="`/preview/${template?.slug || template?.id || ''}/login.html`" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Live Preview
                </a>
                <button @click="save" :disabled="saving" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors disabled:opacity-50">
                    <svg v-if="!saving" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <svg v-else class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    {{ saving ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
        </div>
        <!-- Saved indicator -->
        <div v-if="lastSaved" class="px-4 sm:px-6 py-1.5 bg-emerald-50 border-t border-emerald-100 text-[11px] text-emerald-700 font-medium flex items-center gap-1.5">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
            Tersimpan pukul {{ lastSaved }}
        </div>
    </header>

    <!-- ════════════ MAIN CONTENT (2 kolom) ════════════ -->
    <div class="flex-1 flex overflow-hidden">

        <!-- ════ KIRI: SETTINGS PANEL (28%) ════ -->
        <aside class="w-[280px] sm:w-[320px] shrink-0 bg-white border-r border-slate-200 flex flex-col">
            <!-- Section menu (icon-based) -->
            <nav class="p-3 border-b border-slate-100">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-2">Pengaturan</p>
                <ul class="space-y-0.5">
                    <li v-for="s in sections" :key="s.id">
                        <button @click="activeSection = s.id"
                            :class="activeSection === s.id ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-50'"
                            class="w-full flex items-center gap-2.5 px-2.5 py-2 text-sm rounded-lg transition-colors text-left">
                            <svg class="w-4 h-4 shrink-0" :class="activeSection === s.id ? 'text-blue-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="s.icon"/></svg>
                            {{ s.label }}
                        </button>
                    </li>
                </ul>
            </nav>

            <!-- Section content (scrollable) -->
            <div class="flex-1 overflow-y-auto p-5 space-y-4">

                <!-- UMUM -->
                <div v-if="activeSection === 'umum'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Halaman</label>
                        <input v-model="config.businessName" type="text" placeholder="WIFI HOTSPOT" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Subtitle</label>
                        <textarea v-model="config.subtitle" rows="3" placeholder="Selamat datang! Silakan login..." class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Footer Text</label>
                        <input v-model="config.footerText" type="text" placeholder="Powered by Your ISP" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
                    </div>
                </div>

                <!-- LOGO -->
                <div v-if="activeSection === 'logo'" class="space-y-4">
                    <div class="border-2 border-dashed border-slate-200 rounded-xl p-5 text-center hover:border-blue-400 transition-colors">
                        <input type="file" accept="image/*" @change="onLogoUpload" class="hidden" id="logo-upload">
                        <label for="logo-upload" class="cursor-pointer flex flex-col items-center gap-2">
                            <div v-if="!config.logoUrl" class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <img v-else :src="config.logoUrl" class="w-20 h-20 object-contain rounded-lg" alt="Logo">
                            <p class="text-xs font-semibold text-slate-700">Upload Logo</p>
                            <p class="text-[10px] text-slate-500">PNG, JPG, SVG · max 2MB</p>
                        </label>
                    </div>
                    <div v-if="config.logoUrl" class="flex gap-2">
                        <button @click="config.logoUrl = ''" class="flex-1 py-1.5 text-xs font-medium text-rose-600 bg-rose-50 rounded-lg hover:bg-rose-100">Hapus Logo</button>
                    </div>
                </div>

                <!-- BACKGROUND -->
                <div v-if="activeSection === 'background'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-2">Tipe Background</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button v-for="t in ['gradient', 'solid', 'image']" :key="t" @click="config.bgStyle = t"
                                :class="config.bgStyle === t ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'"
                                class="px-3 py-2.5 text-xs font-semibold border-2 rounded-lg capitalize transition-colors">
                                {{ t === 'gradient' ? 'Gradient' : t === 'solid' ? 'Solid' : 'Gambar' }}
                            </button>
                        </div>
                    </div>
                    <div v-if="config.bgStyle === 'gradient'" class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1 uppercase">Warna 1</label>
                            <div class="flex items-center gap-2 border border-slate-200 rounded-lg p-1.5">
                                <input v-model="config.bgColor1" type="color" class="w-8 h-8 rounded border-0 cursor-pointer">
                                <input v-model="config.bgColor1" type="text" class="flex-1 text-xs font-mono bg-transparent outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1 uppercase">Warna 2</label>
                            <div class="flex items-center gap-2 border border-slate-200 rounded-lg p-1.5">
                                <input v-model="config.bgColor2" type="color" class="w-8 h-8 rounded border-0 cursor-pointer">
                                <input v-model="config.bgColor2" type="text" class="flex-1 text-xs font-mono bg-transparent outline-none">
                            </div>
                        </div>
                    </div>
                    <div v-if="config.bgStyle === 'solid'">
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1 uppercase">Warna Solid</label>
                        <div class="flex items-center gap-2 border border-slate-200 rounded-lg p-1.5">
                            <input v-model="config.bgColor1" type="color" class="w-8 h-8 rounded border-0 cursor-pointer">
                            <input v-model="config.bgColor1" type="text" class="flex-1 text-xs font-mono bg-transparent outline-none">
                        </div>
                    </div>
                    <div v-if="config.bgStyle === 'image'">
                        <div class="border-2 border-dashed border-slate-200 rounded-xl p-5 text-center hover:border-blue-400 transition-colors">
                            <input type="file" accept="image/*" @change="onBgUpload" class="hidden" id="bg-upload">
                            <label for="bg-upload" class="cursor-pointer flex flex-col items-center gap-2">
                                <div v-if="!config.bgImageUrl" class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <img v-else :src="config.bgImageUrl" class="w-full h-24 object-cover rounded-lg" alt="Background">
                                <p class="text-xs font-semibold text-slate-700">Upload Gambar Background</p>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- WARNA -->
                <div v-if="activeSection === 'warna'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Warna Utama (Primary)</label>
                        <div class="flex items-center gap-2 border border-slate-200 rounded-lg p-1.5">
                            <input v-model="config.primaryColor" type="color" class="w-8 h-8 rounded border-0 cursor-pointer">
                            <input v-model="config.primaryColor" type="text" class="flex-1 text-xs font-mono bg-transparent outline-none">
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">Dipakai untuk icon, link, dan elemen brand</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Warna Tombol Login</label>
                        <div class="flex items-center gap-2 border border-slate-200 rounded-lg p-1.5">
                            <input v-model="config.buttonColor" type="color" class="w-8 h-8 rounded border-0 cursor-pointer">
                            <input v-model="config.buttonColor" type="text" class="flex-1 text-xs font-mono bg-transparent outline-none">
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">Pakai warna brand bisnis Anda</p>
                    </div>
                </div>

                <!-- TEKS -->
                <div v-if="activeSection === 'teks'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Teks Tombol Login</label>
                        <input v-model="config.loginBtnText" type="text" placeholder="Login Hotspot" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
                        <p class="text-[10px] text-slate-500 mt-1">Maks 24 karakter agar pas di tombol</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Placeholder Username</label>
                        <input type="text" value="Masukkan username" disabled class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-slate-50 text-slate-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Placeholder Password</label>
                        <input type="text" value="Masukkan password" disabled class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-slate-50 text-slate-400">
                    </div>
                </div>

                <!-- TOMBOL -->
                <div v-if="activeSection === 'tombol'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bentuk Tombol</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button class="px-3 py-2.5 text-xs font-semibold border-2 border-blue-500 bg-blue-50 text-blue-700 rounded-lg">Rounded (default)</button>
                            <button class="px-3 py-2.5 text-xs font-semibold border-2 border-slate-200 text-slate-700 rounded-none">Square</button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ukuran Tombol</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button class="py-1.5 text-xs font-semibold border-2 border-slate-200 text-slate-700 rounded-lg">Kecil</button>
                            <button class="py-2.5 text-xs font-semibold border-2 border-blue-500 bg-blue-50 text-blue-700 rounded-lg">Sedang</button>
                            <button class="py-3.5 text-xs font-semibold border-2 border-slate-200 text-slate-700 rounded-lg">Besar</button>
                        </div>
                    </div>
                </div>

                <!-- SOSIAL -->
                <div v-if="activeSection === 'sosial'" class="space-y-4">
                    <label class="flex items-center justify-between p-3 bg-slate-50 rounded-lg cursor-pointer">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Tampilkan Login Sosial</p>
                            <p class="text-xs text-slate-500">Google, Facebook, Apple</p>
                        </div>
                        <input v-model="config.showSocial" type="checkbox" class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                    </label>
                </div>

                <!-- CSS -->
                <div v-if="activeSection === 'css'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Custom CSS</label>
                        <textarea rows="8" placeholder="/* CSS tambahan untuk kustomisasi lanjutan */" class="w-full px-3 py-2 text-xs font-mono border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none resize-none"></textarea>
                        <p class="text-[10px] text-slate-500 mt-1">⚠️ Untuk pengguna advanced. CSS akan di-inject ke template.</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ════ KANAN: LIVE PREVIEW (72%) ════ -->
        <main class="flex-1 overflow-y-auto bg-slate-100 p-4 sm:p-6 lg:p-10">
            <div class="max-w-5xl mx-auto">

                <!-- Preview device frame -->
                <div class="flex items-center justify-center">
                    <!-- DESKTOP preview -->
                    <div v-if="previewMode === 'desktop'" class="w-full max-w-4xl">
                        <div class="bg-slate-200 rounded-t-xl px-4 py-2.5 flex items-center gap-1.5 border border-slate-200 border-b-0">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            <div class="flex-1 mx-3 bg-white rounded-md px-3 py-1 text-[10px] text-slate-400 font-mono truncate">
                                hotspot.{{ config.businessName.toLowerCase().replace(/\s+/g, '-') }}.test/login
                            </div>
                        </div>
                        <div class="bg-white border border-slate-200 border-t-0 rounded-b-xl overflow-hidden shadow-xl" style="min-height: 500px;">
                            <!-- Login page mockup -->
                            <div class="flex items-center justify-center p-10" :style="previewStyle" style="min-height: 500px;">
                                <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-7 text-center">
                                    <!-- Logo -->
                                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl flex items-center justify-center overflow-hidden"
                                        :style="config.logoUrl ? '' : `background: ${config.primaryColor}`"
                                        :class="config.logoUrl ? '' : ''">
                                        <img v-if="config.logoUrl" :src="config.logoUrl" class="w-full h-full object-contain">
                                        <span v-else class="text-white font-extrabold text-2xl">{{ config.businessName.charAt(0) }}</span>
                                    </div>
                                    <h2 class="text-2xl font-extrabold text-slate-900 mb-1">{{ config.businessName }}</h2>
                                    <p class="text-sm text-slate-500 mb-5 leading-relaxed">{{ config.subtitle }}</p>
                                    <div class="space-y-2.5 mb-4">
                                        <input type="text" placeholder="Username" disabled class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-700">
                                        <input type="password" placeholder="Password" disabled class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-700">
                                    </div>
                                    <button class="w-full py-3 text-sm font-bold text-white rounded-xl shadow-md transition-colors" :style="`background: ${config.buttonColor}`">{{ config.loginBtnText }}</button>
                                    <!-- Social login -->
                                    <div v-if="config.showSocial" class="mt-4 flex items-center justify-center gap-2">
                                        <span class="text-[10px] text-slate-400">atau login dengan</span>
                                        <div class="flex gap-1.5">
                                            <span class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs">G</span>
                                            <span class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs">F</span>
                                        </div>
                                    </div>
                                    <p class="mt-5 text-[10px] text-slate-400">{{ config.footerText }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MOBILE preview -->
                    <div v-else class="w-full max-w-[360px]">
                        <div class="bg-slate-900 rounded-[2.5rem] p-3 shadow-2xl">
                            <div class="flex justify-center mb-2"><div class="w-24 h-5 bg-slate-800 rounded-full"></div></div>
                            <div class="bg-slate-900 rounded-[2rem] overflow-hidden ring-4 ring-slate-800 relative" style="aspect-ratio: 9/16;">
                                <div class="w-full h-full flex items-center justify-center p-5" :style="previewStyle">
                                    <div class="w-full bg-white rounded-2xl shadow-2xl p-5 text-center">
                                        <div class="w-12 h-12 mx-auto mb-3 rounded-2xl flex items-center justify-center overflow-hidden"
                                            :style="config.logoUrl ? '' : `background: ${config.primaryColor}`">
                                            <img v-if="config.logoUrl" :src="config.logoUrl" class="w-full h-full object-contain">
                                            <span v-else class="text-white font-extrabold text-lg">{{ config.businessName.charAt(0) }}</span>
                                        </div>
                                        <h2 class="text-lg font-extrabold text-slate-900 mb-1">{{ config.businessName }}</h2>
                                        <p class="text-[11px] text-slate-500 mb-4 leading-snug">{{ config.subtitle }}</p>
                                        <div class="space-y-2 mb-3">
                                            <input type="text" placeholder="Username" disabled class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg">
                                            <input type="password" placeholder="Password" disabled class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg">
                                        </div>
                                        <button class="w-full py-2.5 text-xs font-bold text-white rounded-lg shadow-md" :style="`background: ${config.buttonColor}`">{{ config.loginBtnText }}</button>
                                        <div v-if="config.showSocial" class="mt-3 flex items-center justify-center gap-1.5">
                                            <span class="text-[9px] text-slate-400">atau</span>
                                            <span class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-[8px]">G</span>
                                            <span class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-[8px]">F</span>
                                        </div>
                                        <p class="mt-3 text-[9px] text-slate-400">{{ config.footerText }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tip bawah -->
                <p class="text-center text-xs text-slate-400 mt-6">
                    💡 Perubahan pada panel kiri langsung terlihat di preview ini. Klik <strong>Simpan</strong> untuk apply ke template.
                </p>
            </div>
        </main>
    </div>
</div>
</template>
