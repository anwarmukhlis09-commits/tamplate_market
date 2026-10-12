<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    templateName: {
        type: String,
        default: 'template-hotspot',
    },
    defaultStep: {
        type: Number,
        default: 1,
    },
    showSupportButton: {
        type: Boolean,
        default: true,
    },
    whatsappNumber: {
        type: String,
        default: '081234567890',
    },
    isModal: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const currentStep = ref(props.defaultStep);
const viewMode = ref('stepper'); // 'stepper' | 'all'

const safeFolderName = computed(() => {
    return (props.templateName || 'template-hotspot')
        .toLowerCase()
        .replace(/[^a-z0-9]/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '') || 'template-hotspot';
});

const steps = [
    {
        num: 1,
        title: 'Ekstrak File ZIP',
        subtitle: 'Buka file ZIP di komputer Anda',
        badge: 'Tahap 1',
        icon: 'archive',
    },
    {
        num: 2,
        title: 'Upload ke Winbox',
        subtitle: 'Drag & Drop ke menu Files',
        badge: 'Tahap 2',
        icon: 'upload',
    },
    {
        num: 3,
        title: 'Ganti HTML Directory',
        subtitle: 'Aktifkan di Hotspot Server Profile',
        badge: 'Kunci Utama',
        icon: 'settings',
    },
    {
        num: 4,
        title: 'Uji Coba & Solusi Kendala',
        subtitle: 'Testing & tips cache browser',
        badge: 'Selesai',
        icon: 'check',
    },
];

const faqs = ref([
    {
        q: 'Kenapa tampilan di HP belum berubah padahal sudah diganti di Winbox?',
        a: 'Browser smartphone menyimpan cache login page lama secara agresif. Solusinya: buka browser di HP, akses url router (misal http://192.168.88.1/logout), atau pilih "Lupakan Jaringan (Forget Wi-Fi)" di pengaturan HP lalu sambungkan kembali.',
        open: true,
    },
    {
        q: 'Muncul error "file not found" atau halaman blank putih?',
        a: 'Periksa kembali menu Files di Winbox. Pastikan nama folder di kolom "HTML Directory" sama persis (huruf besar/kecil berpengaruh) dengan nama folder yang ada di daftar Files, dan pastikan di dalam folder tersebut terdapat file login.html.',
        open: false,
    },
    {
        q: 'Apakah file .zip bisa langsung di-upload ke MikroTik?',
        a: 'TIDAK BISA. MikroTik RouterOS tidak memiliki fitur unzip internal. Anda wajib mengekstrak file ZIP di komputer terlebih dahulu, lalu upload folder hasil ekstraknya.',
        open: false,
    },
]);

function toggleFaq(index) {
    faqs.value[index].open = !faqs.value[index].open;
}

const waUrl = computed(() => {
    const cleanNum = props.whatsappNumber.replace(/[^0-9]/g, '').replace(/^0/, '62');
    const msg = encodeURIComponent(`Halo Admin, saya baru saja membeli template "${props.templateName}". Saya butuh panduan untuk pemasangan di MikroTik. Terima kasih!`);
    return `https://wa.me/${cleanNum}?text=${msg}`;
});
</script>

<template>
<div class="bg-white rounded-3xl border border-slate-200/80 shadow-xl overflow-hidden font-sans text-slate-800">
    <!-- ═══ HEADER BANNER ═══ -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 md:p-8 text-white relative overflow-hidden">
        <!-- Glow decoration -->
        <div class="absolute -top-12 -right-12 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-10 -left-10 w-48 h-48 bg-violet-500/20 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 text-xs font-semibold tracking-wide uppercase mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Panduan Cepat — 3 Menit Siap Pakai
                </div>
                <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight text-white">
                    Cara Pasang Template ke MikroTik
                </h2>
                <p class="text-sm md:text-base text-slate-300 mt-1 max-w-2xl leading-relaxed">
                    Panduan visual langkah demi langkah untuk pemula menggunakan Winbox. Tanpa ribet script terminal!
                </p>
            </div>

            <div class="flex items-center gap-2 self-start md:self-center shrink-0">
                <!-- Toggle View Mode -->
                <div class="bg-white/10 p-1 rounded-xl flex items-center border border-white/10 text-xs">
                    <button 
                        type="button"
                        @click="viewMode = 'stepper'" 
                        class="px-3 py-1.5 rounded-lg font-medium transition-all"
                        :class="viewMode === 'stepper' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:text-white'"
                    >
                        Langkah per Langkah
                    </button>
                    <button 
                        type="button"
                        @click="viewMode = 'all'" 
                        class="px-3 py-1.5 rounded-lg font-medium transition-all"
                        :class="viewMode === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:text-white'"
                    >
                        Lihat Semua
                    </button>
                </div>

                <!-- Close button for modal -->
                <button 
                    v-if="isModal"
                    @click="$emit('close')"
                    type="button"
                    class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors"
                    title="Tutup"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <!-- ═══ STEPPER NAV TABS (Jika viewMode = 'stepper') ═══ -->
        <div v-if="viewMode === 'stepper'" class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 mt-6 pt-6 border-t border-white/10">
            <button
                v-for="s in steps"
                :key="s.num"
                type="button"
                @click="currentStep = s.num"
                class="flex items-center gap-3 p-3 rounded-2xl text-left transition-all relative overflow-hidden group"
                :class="currentStep === s.num 
                    ? 'bg-white text-slate-900 shadow-lg ring-2 ring-indigo-400' 
                    : 'bg-white/5 text-slate-300 hover:bg-white/10 hover:text-white border border-white/5'"
            >
                <div 
                    class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-sm shrink-0 transition-colors"
                    :class="currentStep === s.num ? 'bg-indigo-600 text-white' : 'bg-white/10 text-slate-300 group-hover:bg-white/20'"
                >
                    {{ s.num }}
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] uppercase font-bold tracking-wider" :class="currentStep === s.num ? 'text-indigo-600' : 'text-indigo-300/70'">
                        {{ s.badge }}
                    </div>
                    <div class="text-xs font-bold truncate leading-tight mt-0.5">
                        {{ s.title }}
                    </div>
                </div>
            </button>
        </div>
    </div>

    <!-- ═══ BODY CONTENT ═══ -->
    <div class="p-6 md:p-8 space-y-10">

        <!-- ═════════════════════════════════════════════════════════════ -->
        <!-- STEP 1: EKSTRAK FILE ZIP -->
        <!-- ═════════════════════════════════════════════════════════════ -->
        <section v-show="viewMode === 'all' || currentStep === 1" class="space-y-5">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 font-black text-sm flex items-center justify-center shrink-0">1</span>
                <div>
                    <h3 class="text-xl font-extrabold text-slate-900">Langkah 1: Ekstrak File ZIP di Komputer</h3>
                    <p class="text-sm text-slate-500">Keluarkan seluruh isi template dari format .zip menjadi folder biasa.</p>
                </div>
            </div>

            <!-- Visual Mockup File Explorer -->
            <div class="bg-slate-900 rounded-2xl p-4 md:p-6 shadow-inner border border-slate-800 text-slate-200">
                <!-- Windows/Mac Title Bar -->
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-800 text-xs text-slate-400">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-rose-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                        <span class="ml-2 font-mono text-[11px] text-slate-400">C:\Users\Downloads\</span>
                    </div>
                    <span class="text-slate-400 text-[11px]">File Explorer</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                    <!-- Zip file state -->
                    <div class="bg-slate-800/90 rounded-xl p-4 border border-slate-700 space-y-3">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                            1. File yang baru Anda unduh
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-slate-900/80 rounded-lg border border-slate-700/60">
                            <div class="w-10 h-10 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                            </div>
                            <div class="min-w-0">
                                <div class="font-mono text-xs font-bold text-white truncate">Template_{{ safeFolderName }}.zip</div>
                                <div class="text-[11px] text-slate-400">Arsip Terkompresi (~1.2 MB)</div>
                            </div>
                        </div>
                        <div class="bg-indigo-950/60 border border-indigo-500/40 rounded-lg p-2.5 text-xs text-indigo-200 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                            <span>Klik kanan pada file ZIP &rarr; Pilih <strong>"Extract All"</strong> atau <strong>"Ekstrak di sini"</strong>.</span>
                        </div>
                    </div>

                    <!-- Extracted folder state -->
                    <div class="bg-slate-800/90 rounded-xl p-4 border border-emerald-500/40 space-y-3 relative overflow-hidden">
                        <div class="absolute -right-4 -top-4 w-20 h-20 bg-emerald-500/10 rounded-full blur-xl"></div>
                        <div class="text-xs font-semibold text-emerald-400 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            2. Hasil Ekstrak (Folder Siap Pakai)
                        </div>
                        <div class="p-3 bg-slate-900/90 rounded-lg border border-slate-700/80 space-y-2">
                            <div class="flex items-center gap-2.5 text-emerald-300 font-bold text-xs font-mono">
                                <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                                📁 {{ safeFolderName }}/
                            </div>
                            <!-- Folder contents -->
                            <div class="pl-6 space-y-1 text-[11px] font-mono text-slate-300 border-l border-slate-800">
                                <div class="flex items-center gap-1.5 text-emerald-400 font-semibold">
                                    <span>📄 login.html</span>
                                    <span class="text-[9px] px-1.5 py-0.2 bg-emerald-500/20 text-emerald-300 rounded">Wajib</span>
                                </div>
                                <div class="text-slate-400">📄 status.html, logout.html, error.html</div>
                                <div class="text-slate-400">📁 img/ &nbsp; 📁 css/</div>
                            </div>
                        </div>
                        <div class="text-[11px] text-slate-400 leading-tight">
                            Folder <strong class="text-white">{{ safeFolderName }}</strong> inilah yang akan kita pindahkan ke MikroTik!
                        </div>
                    </div>
                </div>
            </div>

            <!-- Warning Box -->
            <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-xl flex items-start gap-3 text-amber-900 text-sm">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <span class="font-bold">PENTING (Jangan Salah Langkah):</span>
                    Jangan meng-upload file bertipe <code class="bg-amber-100 text-amber-800 px-1 py-0.5 rounded font-mono text-xs">.zip</code> ke MikroTik. MikroTik tidak bisa mengekstrak file zip sendiri. Pastikan yang di-upload adalah <strong>folder hasil ekstraknya</strong>.
                </div>
            </div>
        </section>

        <!-- ═════════════════════════════════════════════════════════════ -->
        <!-- STEP 2: DRAG & DROP KE WINBOX (FILES) -->
        <!-- ═════════════════════════════════════════════════════════════ -->
        <section v-show="viewMode === 'all' || currentStep === 2" class="space-y-5">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 font-black text-sm flex items-center justify-center shrink-0">2</span>
                <div>
                    <h3 class="text-xl font-extrabold text-slate-900">Langkah 2: Buka Winbox &amp; Masuk ke Menu "Files"</h3>
                    <p class="text-sm text-slate-500">Tarik (Drag) folder template dari komputer Anda lalu lepas (Drop) ke dalam daftar file di Winbox.</p>
                </div>
            </div>

            <!-- Realistic Winbox Mockup UI -->
            <div class="bg-[#2B3544] rounded-2xl shadow-2xl border border-slate-700 overflow-hidden text-slate-100">
                <!-- Winbox Window Header Bar -->
                <div class="bg-[#1F2733] px-4 py-2.5 flex items-center justify-between border-b border-slate-700 select-none">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-indigo-600 flex items-center justify-center text-[10px] font-bold text-white">M</div>
                        <span class="font-mono text-xs font-semibold text-slate-200">admin@192.168.88.1 (MikroTik) - Winbox v7</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-slate-400">
                        <span class="w-3 h-0.5 bg-slate-400 inline-block"></span>
                        <span class="w-2.5 h-2.5 border border-slate-400 inline-block"></span>
                        <span class="w-3 h-3 text-[10px] flex items-center justify-center">✕</span>
                    </div>
                </div>

                <!-- Winbox Workspace Grid -->
                <div class="grid grid-cols-1 md:grid-cols-12 min-h-[340px]">
                    <!-- Left Sidebar Menu (MikroTik Tree) -->
                    <div class="md:col-span-4 lg:col-span-3 bg-[#242C3A] border-r border-slate-700 p-2 space-y-1 text-xs select-none">
                        <div class="text-[10px] uppercase font-bold text-slate-500 px-2 py-1">Menu Utama</div>
                        <div class="px-2.5 py-1.5 text-slate-400 hover:bg-slate-700/50 rounded flex items-center gap-2">
                            <span class="text-slate-500">▶</span> Quick Set
                        </div>
                        <div class="px-2.5 py-1.5 text-slate-400 hover:bg-slate-700/50 rounded flex items-center gap-2">
                            <span class="text-slate-500">▶</span> Interfaces
                        </div>
                        <div class="px-2.5 py-1.5 text-slate-400 hover:bg-slate-700/50 rounded flex items-center gap-2">
                            <span class="text-slate-500">▶</span> Wireless
                        </div>
                        <div class="px-2.5 py-1.5 text-slate-400 hover:bg-slate-700/50 rounded flex items-center gap-2">
                            <span class="text-slate-500">▶</span> IP
                        </div>

                        <!-- HIGHLIGHTED MENU FILES -->
                        <div class="px-2.5 py-2 bg-indigo-600 text-white font-bold rounded-lg shadow-md flex items-center justify-between ring-2 ring-indigo-400 ring-offset-2 ring-offset-[#242C3A] animate-pulse">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                <span>Files</span>
                            </div>
                            <span class="text-[10px] px-1.5 py-0.5 bg-white text-indigo-700 font-extrabold rounded">KLIK INI</span>
                        </div>

                        <div class="px-2.5 py-1.5 text-slate-400 hover:bg-slate-700/50 rounded flex items-center gap-2">
                            <span class="text-slate-500">▶</span> Log
                        </div>
                        <div class="px-2.5 py-1.5 text-slate-400 hover:bg-slate-700/50 rounded flex items-center gap-2">
                            <span class="text-slate-500">▶</span> System
                        </div>
                    </div>

                    <!-- Right Window: File List with Drag & Drop Simulation -->
                    <div class="md:col-span-8 lg:col-span-9 p-4 flex flex-col justify-between space-y-4">
                        <!-- File List Window Chrome -->
                        <div class="bg-[#1E2530] rounded-xl border border-slate-700 p-3 shadow-lg flex-1 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between pb-2 border-b border-slate-700 text-xs">
                                    <div class="flex items-center gap-2 text-slate-300 font-semibold">
                                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                                        Jendela "File List" di MikroTik
                                    </div>
                                    <div class="flex gap-1">
                                        <span class="px-2 py-0.5 bg-slate-800 text-slate-400 text-[10px] rounded border border-slate-700">Backup</span>
                                        <span class="px-2 py-0.5 bg-slate-800 text-slate-400 text-[10px] rounded border border-slate-700">Restore</span>
                                    </div>
                                </div>

                                <!-- File items preview table -->
                                <div class="mt-2 text-xs font-mono space-y-1">
                                    <div class="grid grid-cols-12 text-[10px] text-slate-500 pb-1 border-b border-slate-800">
                                        <div class="col-span-6"># Name</div>
                                        <div class="col-span-3">Type</div>
                                        <div class="col-span-3 text-right">Size</div>
                                    </div>
                                    <div class="grid grid-cols-12 text-slate-400 py-0.5">
                                        <div class="col-span-6 flex items-center gap-1.5 truncate">
                                            <span>📁 hotspot</span>
                                        </div>
                                        <div class="col-span-3 text-slate-500">directory</div>
                                        <div class="col-span-3 text-right text-slate-500">--</div>
                                    </div>
                                    <div class="grid grid-cols-12 text-slate-400 py-0.5">
                                        <div class="col-span-6 flex items-center gap-1.5 truncate">
                                            <span>📄 backup-router.backup</span>
                                        </div>
                                        <div class="col-span-3 text-slate-500">backup</div>
                                        <div class="col-span-3 text-right text-slate-500">240 KB</div>
                                    </div>

                                    <!-- NEW DROPPED FOLDER HIGHLIGHT -->
                                    <div class="grid grid-cols-12 text-emerald-300 bg-emerald-500/10 border border-emerald-500/40 rounded px-1 py-1 font-bold items-center">
                                        <div class="col-span-6 flex items-center gap-1.5 truncate">
                                            <span>📁 {{ safeFolderName }}</span>
                                            <span class="text-[9px] px-1 bg-emerald-500/20 text-emerald-300 rounded font-sans">Folder Baru</span>
                                        </div>
                                        <div class="col-span-3 text-emerald-400">directory</div>
                                        <div class="col-span-3 text-right text-emerald-400">1.2 MB</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Animated Drag & Drop Action Card -->
                            <div class="mt-4 p-4 rounded-xl border-2 border-dashed border-indigo-400/60 bg-indigo-950/40 text-center relative overflow-hidden">
                                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                                    <div class="flex items-center gap-2 bg-slate-900 px-3 py-2 rounded-lg border border-slate-700 text-xs text-amber-300 font-mono font-bold shadow-md">
                                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                                        {{ safeFolderName }}
                                    </div>
                                    <div class="flex items-center gap-1 text-indigo-300 text-xs font-bold">
                                        <span>──── Tarik &amp; Lepaskan (Drag &amp; Drop) ────▶</span>
                                    </div>
                                    <div class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-bold shadow-md">
                                        Area Kosong File List
                                    </div>
                                </div>
                                <p class="text-[11px] text-indigo-200/80 mt-2">
                                    💡 <strong>Tips:</strong> Lepaskan folder di area baris kosong atau paling atas agar folder masuk ke root router (bukan masuk ke dalam sub-folder lain).
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═════════════════════════════════════════════════════════════ -->
        <!-- STEP 3: SERVER PROFILES (KUNCI UTAMA!) -->
        <!-- ═════════════════════════════════════════════════════════════ -->
        <section v-show="viewMode === 'all' || currentStep === 3" class="space-y-5">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-amber-500 text-white font-black text-sm flex items-center justify-center shrink-0">3</span>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xl font-extrabold text-slate-900">Langkah 3: Atur "HTML Directory" di Server Profile</h3>
                        <span class="px-2 py-0.5 bg-rose-100 text-rose-700 text-[10px] font-extrabold uppercase rounded-full">Kunci Sukses!</span>
                    </div>
                    <p class="text-sm text-slate-500">Arahkan profil hotspot router ke nama folder template yang baru saja Anda upload.</p>
                </div>
            </div>

            <!-- Path Breadcrumbs Indicator -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3.5 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-700">
                <span class="text-slate-400 font-normal">Urutan Menu:</span>
                <span class="px-2 py-1 bg-white border border-slate-200 rounded shadow-xs">Menu "IP"</span>
                <span class="text-indigo-400">&rarr;</span>
                <span class="px-2 py-1 bg-white border border-slate-200 rounded shadow-xs">"Hotspot"</span>
                <span class="text-indigo-400">&rarr;</span>
                <span class="px-2 py-1 bg-white border border-slate-200 rounded shadow-xs">Tab "Server Profiles"</span>
                <span class="text-indigo-400">&rarr;</span>
                <span class="px-2 py-1 bg-indigo-600 text-white rounded shadow-xs">Dobel Klik Profil Aktif (mis. hsprof1)</span>
            </div>

            <!-- Realistic Server Profile Dialog Window Mockup -->
            <div class="bg-[#2B3544] rounded-2xl shadow-2xl border border-slate-700 overflow-hidden text-slate-100 max-w-2xl mx-auto">
                <!-- Dialog Title Bar -->
                <div class="bg-[#1F2733] px-4 py-2 flex items-center justify-between border-b border-slate-700 text-xs">
                    <span class="font-bold text-slate-200">Hotspot Server Profile &lt;hsprof1&gt;</span>
                    <span class="text-slate-400 cursor-pointer">✕</span>
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-slate-700 bg-[#242C3A] text-xs px-3 pt-2 gap-1">
                    <div class="px-3 py-1.5 bg-[#2B3544] text-white font-bold rounded-t border-t-2 border-indigo-400">General</div>
                    <div class="px-3 py-1.5 text-slate-400 hover:text-slate-200">Login</div>
                    <div class="px-3 py-1.5 text-slate-400 hover:text-slate-200">RADIUS</div>
                    <div class="px-3 py-1.5 text-slate-400 hover:text-slate-200">Queue</div>
                </div>

                <!-- Form Body -->
                <div class="p-6 space-y-4 text-xs font-sans">
                    <!-- Name field -->
                    <div class="grid grid-cols-3 items-center gap-3">
                        <span class="text-slate-400 text-right">Name:</span>
                        <div class="col-span-2">
                            <input type="text" value="hsprof1" disabled class="w-full bg-[#1F2733] border border-slate-700 rounded px-2.5 py-1.5 text-slate-300 font-mono text-xs cursor-not-allowed">
                        </div>
                    </div>

                    <!-- Hotspot Address -->
                    <div class="grid grid-cols-3 items-center gap-3">
                        <span class="text-slate-400 text-right">Hotspot Address:</span>
                        <div class="col-span-2">
                            <input type="text" value="192.168.88.1" disabled class="w-full bg-[#1F2733] border border-slate-700 rounded px-2.5 py-1.5 text-slate-300 font-mono text-xs cursor-not-allowed">
                        </div>
                    </div>

                    <!-- DNS Name -->
                    <div class="grid grid-cols-3 items-center gap-3">
                        <span class="text-slate-400 text-right">DNS Name:</span>
                        <div class="col-span-2">
                            <input type="text" value="hotspot.net" disabled class="w-full bg-[#1F2733] border border-slate-700 rounded px-2.5 py-1.5 text-slate-300 font-mono text-xs cursor-not-allowed">
                        </div>
                    </div>

                    <!-- ═══ THE CRUCIAL FIELD: HTML DIRECTORY ═══ -->
                    <div class="grid grid-cols-3 items-center gap-3 bg-amber-500/10 -mx-6 px-6 py-4 border-y border-amber-500/30 relative">
                        <div class="text-right flex items-center justify-end gap-1.5 font-bold text-amber-300">
                            <svg class="w-4 h-4 text-amber-400 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            <span>HTML Directory:</span>
                        </div>
                        <div class="col-span-2 relative">
                            <!-- Dropdown select mockup with glowing border -->
                            <div class="relative">
                                <div class="w-full bg-slate-900 border-2 border-amber-400 rounded-lg px-3 py-2 text-emerald-300 font-mono font-bold text-sm shadow-lg flex items-center justify-between ring-4 ring-amber-400/20">
                                    <div class="flex items-center gap-2 truncate">
                                        <span>📁 {{ safeFolderName }}</span>
                                    </div>
                                    <span class="text-amber-400">▼</span>
                                </div>
                            </div>
                            <div class="mt-1.5 text-[11px] text-amber-200 font-medium leading-tight">
                                👈 <strong>Klik dropdown ini</strong> dan pilih nama folder template Anda (<span class="font-mono text-amber-300">{{ safeFolderName }}</span>).
                            </div>
                        </div>
                    </div>

                    <!-- HTML Directory to default button note -->
                    <div class="grid grid-cols-3 items-center gap-3 pt-2">
                        <div></div>
                        <div class="col-span-2 flex items-center gap-2">
                            <button type="button" disabled class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded text-xs shadow-md">
                                Apply
                            </button>
                            <button type="button" disabled class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded text-xs shadow-md">
                                OK
                            </button>
                            <span class="text-[11px] text-slate-400 ml-1">Lalu klik <strong>Apply</strong> &rarr; <strong>OK</strong>.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pro-tip note -->
            <div class="bg-indigo-50/70 border border-indigo-200 rounded-xl p-4 text-indigo-900 text-sm flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <strong>Punya lebih dari 1 server profile?</strong><br>
                    Jika router Anda memiliki beberapa nama hotspot profile (misal untuk Wi-Fi Cafe dan Wi-Fi Kantor), ubah kolom <em>HTML Directory</em> pada profil yang sesuai dengan interface Wi-Fi yang ingin Anda ganti tampilannya.
                </div>
            </div>
        </section>

        <!-- ═════════════════════════════════════════════════════════════ -->
        <!-- STEP 4: UJI COBA & TROUBLESHOOTING -->
        <!-- ═════════════════════════════════════════════════════════════ -->
        <section v-show="viewMode === 'all' || currentStep === 4" class="space-y-6">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-emerald-500 text-white font-black text-sm flex items-center justify-center shrink-0">4</span>
                <div>
                    <h3 class="text-xl font-extrabold text-slate-900">Langkah 4: Uji Coba &amp; Solusi Jika Belum Berubah</h3>
                    <p class="text-sm text-slate-500">Uji coba koneksi di smartphone dan cara mengatasi cache browser yang bandel.</p>
                </div>
            </div>

            <!-- 2-Column: Phone Test Mockup & Troubleshooting Accordion -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Mobile Phone Mockup -->
                <div class="lg:col-span-5 bg-gradient-to-b from-slate-100 to-slate-200 rounded-3xl p-6 border border-slate-300 shadow-md text-center">
                    <div class="w-60 mx-auto bg-slate-900 rounded-[32px] p-2.5 shadow-2xl border-4 border-slate-700 relative">
                        <!-- Camera notch -->
                        <div class="w-20 h-4 bg-slate-800 rounded-full mx-auto mb-2"></div>
                        <!-- Screen area -->
                        <div class="bg-gradient-to-br from-indigo-600 to-violet-800 rounded-[22px] p-4 text-white min-h-[260px] flex flex-col justify-between text-left">
                            <div class="flex justify-between items-center text-[10px] text-white/80">
                                <span>10:30</span>
                                <span>📶 Wi-Fi Terhubung</span>
                            </div>
                            <div class="space-y-2 py-4">
                                <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center text-xs font-bold">📡</div>
                                <div class="text-xs font-bold leading-tight">Selamat Datang di Hotspot!</div>
                                <div class="text-[10px] text-white/80">Masukkan kode voucher Anda</div>
                                <div class="bg-white/10 rounded p-1.5 border border-white/20 text-[10px]">
                                    <div class="text-white/60">Kode Voucher:</div>
                                    <div class="font-mono font-bold text-white">XXXX-XXXX</div>
                                </div>
                            </div>
                            <div class="w-full py-1.5 bg-emerald-400 text-slate-900 font-bold rounded text-center text-[10px]">
                                LOGIN SEKARANG
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-3 font-medium">
                        Tampilan login baru otomatis muncul saat pengguna menghubungkan perangkat ke Wi-Fi.
                    </p>
                </div>

                <!-- Troubleshooting Accordion -->
                <div class="lg:col-span-7 space-y-3">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5 mb-2">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Solusi Masalah Umum (FAQ Pemula)
                    </div>

                    <div 
                        v-for="(faq, idx) in faqs" 
                        :key="idx"
                        class="border rounded-2xl transition-all overflow-hidden"
                        :class="faq.open ? 'border-indigo-200 bg-indigo-50/30 shadow-xs' : 'border-slate-200 bg-white hover:border-slate-300'"
                    >
                        <button
                            type="button"
                            @click="toggleFaq(idx)"
                            class="w-full p-4 text-left flex items-center justify-between gap-3 text-xs md:text-sm font-bold text-slate-800"
                        >
                            <span>{{ faq.q }}</span>
                            <span 
                                class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 transition-transform duration-200 text-xs font-bold"
                                :class="faq.open ? 'bg-indigo-600 text-white rotate-180' : 'bg-slate-100 text-slate-600'"
                            >
                                ▾
                            </span>
                        </button>
                        <div v-show="faq.open" class="px-4 pb-4 text-xs text-slate-600 leading-relaxed border-t border-indigo-100/60 pt-3">
                            {{ faq.a }}
                        </div>
                    </div>

                    <!-- Quick shortcut action: Cara Logout Mikrotik -->
                    <div class="bg-slate-900 text-white rounded-2xl p-4 text-xs space-y-2 mt-4">
                        <div class="font-bold flex items-center gap-1.5 text-amber-300">
                            <span>⚡ Cara Cepat Tes Tampilan Baru:</span>
                        </div>
                        <p class="text-slate-300 leading-relaxed">
                            Buka browser di HP/laptop yang tersambung ke Wi-Fi, lalu ketik alamat ini di address bar untuk memaksa logout:
                        </p>
                        <div class="bg-black/60 font-mono text-emerald-400 px-3 py-2 rounded-lg border border-slate-700 select-all text-center font-bold">
                            http://192.168.88.1/logout
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ FOOTER STEPPER NAVIGATION ═══ -->
        <div v-if="viewMode === 'stepper'" class="pt-6 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
            <button
                type="button"
                @click="currentStep = Math.max(1, currentStep - 1)"
                :disabled="currentStep === 1"
                class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-1.5"
            >
                &larr; Langkah Sebelumnya
            </button>

            <div class="text-xs text-slate-400 font-medium">
                Langkah <span class="font-bold text-indigo-600">{{ currentStep }}</span> dari <span class="font-bold text-slate-700">4</span>
            </div>

            <button
                v-if="currentStep < 4"
                type="button"
                @click="currentStep = Math.min(4, currentStep + 1)"
                class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-colors shadow-md shadow-indigo-100 flex items-center justify-center gap-1.5"
            >
                Langkah Selanjutnya &rarr;
            </button>
            <button
                v-else
                type="button"
                @click="isModal ? $emit('close') : (currentStep = 1)"
                class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-colors shadow-md shadow-emerald-100 flex items-center justify-center gap-1.5"
            >
                ✓ Selesai Membaca Panduan
            </button>
        </div>

        <!-- ═══ WHATSAPP SUPPORT CTA BANNER ═══ -->
        <div v-if="showSupportButton" class="bg-gradient-to-r from-emerald-500/10 via-teal-500/10 to-indigo-500/10 border border-emerald-500/20 rounded-2xl p-5 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3.5 text-left">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shadow-lg shadow-emerald-200 shrink-0">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                </div>
                <div>
                    <h4 class="text-sm font-extrabold text-slate-900">Masih Mengalami Kendala Instalasi?</h4>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Tim teknis kami siap memandu pemasangan template ke router MikroTik Anda secara langsung via chat WhatsApp.
                    </p>
                </div>
            </div>

            <a
                :href="waUrl"
                target="_blank"
                rel="noopener"
                class="w-full md:w-auto px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-md shadow-emerald-200 shrink-0 inline-flex items-center justify-center gap-2"
            >
                <span>Tanya Admin via WhatsApp</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </div>
</div>
</template>
