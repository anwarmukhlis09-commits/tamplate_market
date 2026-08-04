<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, watch, onMounted, computed } from 'vue';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout.vue';

const props = defineProps({
    initialTab: { type: String, default: 'cara-order' }
});

const activeTab = ref(props.initialTab);
const searchQuery = ref('');
const isMobileMenuOpen = ref(false);

// Watch for changes in initialTab (if user clicks another footer link when already on Bantuan page)
watch(() => props.initialTab, (newTab) => {
    activeTab.value = newTab;
});

const menuItems = [
    { 
        value: 'cara-order', 
        label: 'Cara Order', 
        category: 'bantuan',
        icon: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>`
    },
    { 
        value: 'instalasi', 
        label: 'Panduan Instalasi', 
        category: 'bantuan',
        icon: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`
    },
    { 
        value: 'faq', 
        label: 'FAQ (Tanya Jawab)', 
        category: 'bantuan',
        icon: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
    },
    { 
        value: 'kontak', 
        label: 'Kontak Support', 
        category: 'bantuan',
        icon: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>`
    },
    { 
        value: 'syarat-ketentuan', 
        label: 'Syarat & Ketentuan', 
        category: 'kebijakan',
        icon: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`
    },
    { 
        value: 'kebijakan-privasi', 
        label: 'Kebijakan Privasi', 
        category: 'kebijakan',
        icon: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>`
    },
    { 
        value: 'kebijakan-refund', 
        label: 'Kebijakan Refund', 
        category: 'kebijakan',
        icon: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>`
    },
    { 
        value: 'lisensi', 
        label: 'Lisensi Penggunaan', 
        category: 'kebijakan',
        icon: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 7a2 2 0 012 2m-5-2a2 2 0 012 2m-5-2a2 2 0 012 2m-5-2a2 2 0 012 2m-5-2a2 2 0 012 2m-5-2a2 2 0 012 2m-5-2a2 2 0 012 2m-5-2a2 2 0 012 2m-5-2a2 2 0 012 2m-5-2a2 2 0 012 2M3 19v-8.93a2 2 0 01.89-1.664l8-5.333a2 2 0 012.22 0l8 5.333A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-2.25-1.5m0 0l-2.25 1.5"/></svg>`
    },
];

const filteredMenuItems = computed(() => {
    if (!searchQuery.value) return menuItems;
    return menuItems.filter(item => 
        item.label.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const activeTabLabel = computed(() => {
    const item = menuItems.find(i => i.value === activeTab.value);
    return item ? item.label : '';
});

const activeTabIllustration = computed(() => {
    const illustrations = {
        'cara-order': `<svg class="w-full h-full text-indigo-600/90" viewBox="0 0 100 100" fill="none" stroke="currentColor"><rect x="25" y="15" width="50" height="70" rx="6" stroke-width="2.5"/><circle cx="50" cy="40" r="12" stroke-width="2.5" stroke-dasharray="3 3"/><path d="M40 70h20M35 78h30" stroke-width="2.5" stroke-linecap="round"/><path d="M45 40l4 4 8-8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
        
        'instalasi': `<svg class="w-full h-full text-indigo-600/90" viewBox="0 0 100 100" fill="none" stroke="currentColor"><rect x="15" y="25" width="70" height="46" rx="4" stroke-width="2.5"/><path d="M10 71h80M40 71l-5 12h30l-5-12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M45 40v14m0 0l-4-4m4 4l4-4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="55" cy="47" r="6" stroke-width="2" stroke-dasharray="2 2"/></svg>`,
        
        'faq': `<svg class="w-full h-full text-indigo-600/90" viewBox="0 0 100 100" fill="none" stroke="currentColor"><path d="M25 55c0-11 9-20 20-20h20c11 0 20 9 20 20s-9 20-20 20H45c-4 0-8 2-10 5l-5 4v-9c-3-2-5-6-5-10z" stroke-width="2.5" stroke-linejoin="round"/><path d="M35 35c0-6 5-11 11-11h20c6 0 11 5 11 11" stroke-width="2" stroke-dasharray="3 3"/></svg>`,
        
        'kontak': `<svg class="w-full h-full text-indigo-600/90" viewBox="0 0 100 100" fill="none" stroke="currentColor"><circle cx="50" cy="50" r="30" stroke-width="2.5"/><path d="M40 45a5 5 0 0110 0v10a5 5 0 0010 0" stroke-width="2.5" stroke-linecap="round"/><path d="M30 50h40" stroke-width="1.5" stroke-dasharray="4 4"/></svg>`,
        
        'syarat-ketentuan': `<svg class="w-full h-full text-indigo-600/90" viewBox="0 0 100 100" fill="none" stroke="currentColor"><path d="M30 20h40v60H30z" stroke-width="2.5" stroke-linejoin="round"/><path d="M38 35h24M38 48h24M38 61h14" stroke-width="2.5" stroke-linecap="round"/></svg>`,
        
        'kebijakan-privasi': `<svg class="w-full h-full text-indigo-600/90" viewBox="0 0 100 100" fill="none" stroke="currentColor"><rect x="25" y="45" width="50" height="35" rx="5" stroke-width="2.5"/><path d="M35 45V32a15 15 0 1130 0v13" stroke-width="2.5" stroke-linecap="round"/><circle cx="50" cy="62" r="4" stroke-width="2"/></svg>`,
        
        'kebijakan-refund': `<svg class="w-full h-full text-indigo-600/90" viewBox="0 0 100 100" fill="none" stroke="currentColor"><circle cx="50" cy="50" r="30" stroke-width="2.5"/><path d="M50 32v18l12 6" stroke-width="2.5" stroke-linecap="round"/><path d="M32 50a18 18 0 0118-18" stroke-width="2" stroke-linecap="round"/></svg>`,
        
        'lisensi': `<svg class="w-full h-full text-indigo-600/90" viewBox="0 0 100 100" fill="none" stroke="currentColor"><path d="M50 15L20 30v25c0 18 13 30 30 30 17 0 30-12 30-30V30L50 15z" stroke-width="2.5" stroke-linejoin="round"/><path d="M38 52l8 8 16-16" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`
    };
    return illustrations[activeTab.value] || illustrations['cara-order'];
});

function selectTab(tab) {
    activeTab.value = tab;
    // Update URL query string without reloading page
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.pushState({}, '', url);
}

onMounted(() => {
    // Parse query param on load if exist
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');
    if (tab && menuItems.some(i => i.value === tab)) {
        activeTab.value = tab;
    }
});
</script>

<template>
    <Head title="Pusat Bantuan & Informasi — Template Hotspot" />

    <MarketplaceLayout>
        <div class="min-h-screen bg-[#F8FAFC] text-[#0F172A] antialiased pt-28 pb-20 animate-fade-in">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                

                <!-- Main Layout Container -->
                <div class="grid grid-cols-1 lg:grid-cols-[300px_1fr] gap-10 items-start">
                    
                    <!-- Mobile Category Selector Bar -->
                    <div class="lg:hidden flex items-center justify-between p-4 bg-white border border-[#EEF2F7] rounded-2xl shadow-xs mb-6">
                        <span class="text-sm font-semibold text-[#0F172A]">Topik Panduan</span>
                        <button @click="isMobileMenuOpen = true" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#4F46E5] hover:bg-[#4338CA] text-white text-xs font-bold rounded-lg transition-colors shadow-sm outline-none">
                            <span>Pilih Kategori</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                    </div>

                    <!-- Mobile Drawer (Offcanvas Menu) -->
                    <div v-if="isMobileMenuOpen" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
                        <!-- Backdrop -->
                        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" @click="isMobileMenuOpen = false"></div>
                        
                        <!-- Panel -->
                        <div class="fixed inset-y-0 left-0 w-full max-w-xs bg-white p-6 shadow-2xl flex flex-col border-r border-[#EEF2F7]">
                            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
                                <span class="text-base font-extrabold text-[#0F172A]">Daftar Topik</span>
                                <button @click="isMobileMenuOpen = false" class="text-slate-400 hover:text-slate-600 outline-none">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            
                            <!-- Search query (mobile) -->
                            <div class="relative mb-5">
                                <input v-model="searchQuery" type="text" placeholder="Cari panduan..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder:text-[#64748B] focus:border-[#4F46E5] focus:ring-2 focus:ring-[#4F46E5]/10 outline-none transition-all" />
                                <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            
                            <!-- Menu Links (mobile scrollable) -->
                            <div class="flex-1 overflow-y-auto space-y-6">
                                <div>
                                    <h3 class="text-[10px] font-extrabold text-[#64748B] uppercase tracking-wider mb-2.5 pl-3">Panduan &amp; Support</h3>
                                    <nav class="space-y-1">
                                        <button v-for="item in filteredMenuItems.filter(i => i.category === 'bantuan')" :key="item.value"
                                            @click="selectTab(item.value); isMobileMenuOpen = false"
                                            class="w-full text-left px-3.5 py-2.5 text-sm rounded-xl font-medium flex items-center gap-2.5 transition-all outline-none"
                                            :class="activeTab === item.value ? 'bg-indigo-50/75 text-[#4F46E5] font-semibold border-l-4 border-[#4F46E5] pl-2' : 'text-[#64748B] hover:bg-slate-50 hover:text-[#0F172A]'">
                                            <span class="shrink-0 text-slate-400 group-hover:text-[#4F46E5]" v-html="item.icon"></span>
                                            <span>{{ item.label }}</span>
                                        </button>
                                    </nav>
                                </div>
                                <div class="pt-4 border-t border-slate-100">
                                    <h3 class="text-[10px] font-extrabold text-[#64748B] uppercase tracking-wider mb-2.5 pl-3">Kebijakan &amp; Legal</h3>
                                    <nav class="space-y-1">
                                        <button v-for="item in filteredMenuItems.filter(i => i.category === 'kebijakan')" :key="item.value"
                                            @click="selectTab(item.value); isMobileMenuOpen = false"
                                            class="w-full text-left px-3.5 py-2.5 text-sm rounded-xl font-medium flex items-center gap-2.5 transition-all outline-none"
                                            :class="activeTab === item.value ? 'bg-indigo-50/75 text-[#4F46E5] font-semibold border-l-4 border-[#4F46E5] pl-2' : 'text-[#64748B] hover:bg-slate-50 hover:text-[#0F172A]'">
                                            <span class="shrink-0 text-slate-400 group-hover:text-[#4F46E5]" v-html="item.icon"></span>
                                            <span>{{ item.label }}</span>
                                        </button>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Left Sidebar (Desktop only) -->
                    <aside class="hidden lg:block bg-white border border-[#EEF2F7] rounded-3xl p-6 shadow-sm space-y-6 sticky top-28 self-start z-30">
                        <!-- Search Bar -->
                        <div class="relative">
                            <input v-model="searchQuery" type="text" placeholder="Cari panduan..." class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-[#E5E7EB] rounded-xl text-sm placeholder:text-[#64748B] focus:border-[#4F46E5] focus:ring-2 focus:ring-[#4F46E5]/10 outline-none transition-all" />
                            <svg class="absolute left-3 top-3.5 w-4 h-4 text-[#64748B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>

                        <!-- Menu Section 1 -->
                        <div>
                            <h3 class="text-[10px] font-extrabold text-[#64748B] uppercase tracking-wider mb-2.5 pl-3">Panduan &amp; Support</h3>
                            <nav class="space-y-1.5">
                                <button v-for="item in filteredMenuItems.filter(i => i.category === 'bantuan')" :key="item.value"
                                    @click="selectTab(item.value)"
                                    class="menu-btn w-full text-left px-3.5 py-2.5 text-sm rounded-xl font-medium flex items-center gap-2.5 transition-all outline-none"
                                    :class="activeTab === item.value ? 'bg-indigo-50/75 text-[#4F46E5] font-semibold border-l-4 border-[#4F46E5] pl-2.5' : 'text-[#64748B] hover:bg-slate-50 hover:text-[#0F172A]'">
                                    <span class="shrink-0 text-slate-400 group-hover:text-[#4F46E5]" v-html="item.icon"></span>
                                    <span>{{ item.label }}</span>
                                </button>
                            </nav>
                        </div>

                        <!-- Menu Section 2 -->
                        <div class="pt-4 border-t border-slate-100">
                            <h3 class="text-[10px] font-extrabold text-[#64748B] uppercase tracking-wider mb-2.5 pl-3">Kebijakan &amp; Legal</h3>
                            <nav class="space-y-1.5">
                                <button v-for="item in filteredMenuItems.filter(i => i.category === 'kebijakan')" :key="item.value"
                                    @click="selectTab(item.value)"
                                    class="menu-btn w-full text-left px-3.5 py-2.5 text-sm rounded-xl font-medium flex items-center gap-2.5 transition-all outline-none"
                                    :class="activeTab === item.value ? 'bg-indigo-50/75 text-[#4F46E5] font-semibold border-l-4 border-[#4F46E5] pl-2.5' : 'text-[#64748B] hover:bg-slate-50 hover:text-[#0F172A]'">
                                    <span class="shrink-0 text-slate-400 group-hover:text-[#4F46E5]" v-html="item.icon"></span>
                                    <span>{{ item.label }}</span>
                                </button>
                            </nav>
                        </div>
                    </aside>

                    <!-- Right Content Box (Elevated style card) -->
                    <main class="help-card bg-white border border-[#EEF2F7] p-8 sm:p-10 min-h-[550px] flex flex-col justify-between max-w-[900px] w-full">
                        
                        <div class="space-y-8">
                            <!-- Tab Header Area -->
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 pb-6 border-b border-slate-100">
                                <div class="space-y-2">
                                    <h2 class="text-3xl font-extrabold text-[#0F172A] tracking-tight leading-tight sm:text-4xl font-inter">
                                        {{ activeTabLabel }}
                                    </h2>
                                </div>
                                <!-- Flat Illustration Panel -->
                                <div class="shrink-0 w-20 h-20 sm:w-24 sm:h-24 bg-slate-50 border border-[#EEF2F7] rounded-2xl shadow-xs p-4 flex items-center justify-center" v-html="activeTabIllustration">
                                </div>
                            </div>

                            <!-- Content Tabs Switch -->
                            
                            <!-- TAB: CARA ORDER -->
                            <div v-if="activeTab === 'cara-order'" class="space-y-8 animate-fade-in">
                                <p class="text-[#64748B] text-base leading-[1.8] font-inter">Berikut adalah 3 langkah mudah untuk membeli dan memiliki template hotspot MikroTik impian Anda:</p>
                                
                                <div class="relative pl-6 space-y-10">
                                    <!-- Vertical connecting line -->
                                    <div class="absolute left-[15px] top-6 bottom-6 w-0.5 bg-indigo-100"></div>

                                    <!-- Step 1 -->
                                    <div class="relative pl-10">
                                        <div class="absolute left-0 top-0.5 w-8 h-8 rounded-full bg-indigo-50 border border-[#4F46E5] flex items-center justify-center text-[#4F46E5] font-bold text-sm shadow-xs ring-4 ring-white">
                                            1
                                        </div>
                                        <div class="space-y-2">
                                            <h3 class="text-xl font-bold text-[#0F172A] font-inter">Pilih &amp; Kustomisasi Template</h3>
                                            <p class="text-[#64748B] text-sm leading-[1.8] font-inter">
                                                Cari template terbaik di katalog kami. Anda bisa menguji tampilannya secara langsung menggunakan editor visual kami sebelum melakukan checkout.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Step 2 -->
                                    <div class="relative pl-10">
                                        <div class="absolute left-0 top-0.5 w-8 h-8 rounded-full bg-indigo-50 border border-[#4F46E5] flex items-center justify-center text-[#4F46E5] font-bold text-sm shadow-xs ring-4 ring-white">
                                            2
                                        </div>
                                        <div class="space-y-2">
                                            <h3 class="text-xl font-bold text-[#0F172A] font-inter">Lakukan Pembayaran Instan</h3>
                                            <p class="text-[#64748B] text-sm leading-[1.8] font-inter">
                                                Klik beli, masukkan alamat email aktif Anda, pilih metode pembayaran otomatis (QRIS, E-Wallet, Virtual Account, dll.), dan selesaikan pembayaran Anda.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Step 3 -->
                                    <div class="relative pl-10">
                                        <div class="absolute left-0 top-0.5 w-8 h-8 rounded-full bg-indigo-50 border border-[#4F46E5] flex items-center justify-center text-[#4F46E5] font-bold text-sm shadow-xs ring-4 ring-white">
                                            3
                                        </div>
                                        <div class="space-y-2">
                                            <h3 class="text-xl font-bold text-[#0F172A] font-inter">Unduh File Siap Pakai</h3>
                                            <p class="text-[#64748B] text-sm leading-[1.8] font-inter">
                                                Setelah pembayaran terkonfirmasi, masuk ke Dashboard akun Anda untuk mengunduh file template hotspot dalam format ZIP yang siap di-upload ke MikroTik Anda.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: INSTALASI -->
                            <div v-if="activeTab === 'instalasi'" class="space-y-8 animate-fade-in">
                                <p class="text-[#64748B] text-base leading-[1.8] font-inter">Setelah mengunduh file ZIP template, ikuti panduan berikut untuk memasangnya di router MikroTik Anda:</p>
                                
                                <div class="relative pl-6 space-y-10">
                                    <!-- Vertical connecting line -->
                                    <div class="absolute left-[15px] top-6 bottom-6 w-0.5 bg-indigo-100"></div>

                                    <!-- Step 1 -->
                                    <div class="relative pl-10">
                                        <div class="absolute left-0 top-0.5 w-8 h-8 rounded-full bg-indigo-50 border border-[#4F46E5] flex items-center justify-center text-[#4F46E5] font-bold text-sm shadow-xs ring-4 ring-white">
                                            1
                                        </div>
                                        <div class="space-y-2">
                                            <h3 class="text-xl font-bold text-[#0F172A] font-inter">Ekstrak File ZIP</h3>
                                            <p class="text-[#64748B] text-sm leading-[1.8] font-inter">
                                                Ekstrak file ZIP template di komputer Anda. Anda akan mendapatkan sebuah folder berisi file <code>login.html</code>, <code>status.html</code>, dan file pendukung lainnya.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Step 2 -->
                                    <div class="relative pl-10">
                                        <div class="absolute left-0 top-0.5 w-8 h-8 rounded-full bg-indigo-50 border border-[#4F46E5] flex items-center justify-center text-[#4F46E5] font-bold text-sm shadow-xs ring-4 ring-white">
                                            2
                                        </div>
                                        <div class="space-y-2">
                                            <h3 class="text-xl font-bold text-[#0F172A] font-inter">Hubungkan ke Winbox</h3>
                                            <p class="text-[#64748B] text-sm leading-[1.8] font-inter">
                                                Buka aplikasi <strong>Winbox</strong> dan hubungkan ke router MikroTik Anda.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Step 3 -->
                                    <div class="relative pl-10">
                                        <div class="absolute left-0 top-0.5 w-8 h-8 rounded-full bg-indigo-50 border border-[#4F46E5] flex items-center justify-center text-[#4F46E5] font-bold text-sm shadow-xs ring-4 ring-white">
                                            3
                                        </div>
                                        <div class="space-y-2">
                                            <h3 class="text-xl font-bold text-[#0F172A] font-inter">Upload File ke Files</h3>
                                            <p class="text-[#64748B] text-sm leading-[1.8] font-inter">
                                                Masuk ke menu <strong>Files</strong> di Winbox. Drag (seret) folder template yang sudah diekstrak tadi dari komputer Anda, lalu drop (lepaskan) ke dalam daftar file di Winbox.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Step 4 -->
                                    <div class="relative pl-10">
                                        <div class="absolute left-0 top-0.5 w-8 h-8 rounded-full bg-indigo-50 border border-[#4F46E5] flex items-center justify-center text-[#4F46E5] font-bold text-sm shadow-xs ring-4 ring-white">
                                            4
                                        </div>
                                        <div class="space-y-2">
                                            <h3 class="text-xl font-bold text-[#0F172A] font-inter">Aktifkan di Server Profile</h3>
                                            <p class="text-[#64748B] text-sm leading-[1.8] font-inter">
                                                Buka menu <strong>IP</strong> -&gt; <strong>Hotspot</strong> -&gt; tab <strong>Server Profiles</strong>. Klik dua kali profil hotspot aktif Anda. Pada bagian <strong>HTML Directory</strong>, pilih nama folder template yang barusan Anda upload. Klik <strong>Apply</strong> dan <strong>OK</strong>.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: FAQ -->
                            <div v-if="activeTab === 'faq'" class="space-y-6 animate-fade-in">
                                <div class="space-y-5 pt-2">
                                    <div class="border-b border-slate-100 pb-5">
                                        <h3 class="font-bold text-[#0F172A] text-lg font-inter">Q: Apakah template ini aman dari eksploitasi keamanan?</h3>
                                        <p class="text-[#64748B] text-sm mt-2 leading-[1.8] font-inter">A: Sangat aman. Semua template kami dibangun menggunakan kode HTML, CSS, dan JavaScript standar yang bersih tanpa skrip pihak ketiga yang mencurigakan. Seluruh data login diproses langsung oleh sistem internal RouterOS MikroTik Anda.</p>
                                    </div>
                                    <div class="border-b border-slate-100 pb-5">
                                        <h3 class="font-bold text-[#0F172A] text-lg font-inter">Q: Apakah mendukung RouterOS v6 dan v7?</h3>
                                        <p class="text-[#64748B] text-sm mt-2 leading-[1.8] font-inter">A: Ya. Semua template kami dirancang secara universal untuk berjalan dengan mulus di MikroTik RouterOS versi v6 maupun v7.</p>
                                    </div>
                                    <div class="pb-5">
                                        <h3 class="font-bold text-[#0F172A] text-lg font-inter">Q: Apakah saya bisa mengganti harga paket di template?</h3>
                                        <p class="text-[#64748B] text-sm mt-2 leading-[1.8] font-inter">A: Bisa. Anda dapat mengedit daftar harga paket menggunakan fitur visual editor kami sebelum melakukan checkout, atau Anda bisa langsung mengedit file <code>login.html</code> menggunakan text editor (seperti Notepad/VS Code) setelah file diunduh.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: KONTAK SUPPORT -->
                            <div v-if="activeTab === 'kontak'" class="space-y-6 animate-fade-in">
                                <p class="text-[#64748B] text-base leading-[1.8] font-inter">Tim bantuan kami siap melayani keluhan, pertanyaan seputar lisensi, maupun bantuan instalasi hotspot Anda:</p>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
                                    <div class="border border-[#EEF2F7] rounded-2xl p-6 bg-slate-50/50 hover:bg-slate-50 transition-colors">
                                        <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider mb-2">WhatsApp Admin</h3>
                                        <p class="text-lg font-extrabold text-[#0F172A]">0812-3456-7890</p>
                                        <a href="https://wa.me/6281234567890" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#4F46E5] hover:text-[#4338CA] mt-4 transition-colors">
                                            Chat Sekarang →
                                        </a>
                                    </div>
                                    <div class="border border-[#EEF2F7] rounded-2xl p-6 bg-slate-50/50 hover:bg-slate-50 transition-colors">
                                        <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider mb-2">Email Support</h3>
                                        <p class="text-lg font-extrabold text-[#0F172A]">support@templatehotspot.id</p>
                                        <a href="mailto:support@templatehotspot.id" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#4F46E5] hover:text-[#4338CA] mt-4 transition-colors">
                                            Kirim Email →
                                        </a>
                                    </div>
                                </div>

                                <p class="text-xs text-[#64748B] mt-4 leading-[1.8] font-inter">* Jam Operasional Bantuan: Senin - Minggu (Pukul 08:00 WIB hingga 22:00 WIB).</p>
                            </div>

                            <!-- TAB: SYARAT & KETENTUAN -->
                            <div v-if="activeTab === 'syarat-ketentuan'" class="space-y-6 animate-fade-in">
                                <div class="prose prose-slate max-w-none text-[#64748B] text-sm leading-[1.8] space-y-4 font-inter">
                                    <p>Dengan membeli dan menggunakan produk template hotspot dari situs kami, Anda menyetujui ketentuan berikut:</p>
                                    <ul class="list-disc pl-5 space-y-2">
                                        <li>Anda dilarang keras untuk mendistribusikan ulang, membagikan secara gratis, maupun menjual kembali file template yang telah Anda beli tanpa persetujuan tertulis dari kami.</li>
                                        <li>Seluruh hak kekayaan intelektual dan desain visual produk template hotspot tetap menjadi milik eksklusif pihak **Template Hotspot**.</li>
                                        <li>Kami berhak melakukan pembatasan atau penonaktifkan akun bagi pembeli yang kedapatan melanggar aturan distribusi file.</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- TAB: KEBIJAKAN PRIVASI -->
                            <div v-if="activeTab === 'kebijakan-privasi'" class="space-y-6 animate-fade-in">
                                <div class="prose prose-slate max-w-none text-[#64748B] text-sm leading-[1.8] space-y-4 font-inter">
                                    <p>Kami sangat menghargai privasi informasi pribadi Anda. Berikut adalah ringkasan kebijakan pengumpulan data kami:</p>
                                    <ul class="list-disc pl-5 space-y-2">
                                        <li>Kami hanya mengumpulkan informasi pribadi yang Anda berikan secara sadar saat melakukan pendaftaran akun atau pemesanan template (seperti nama lengkap, alamat email, dan nomor telepon).</li>
                                        <li>Informasi pribadi Anda hanya digunakan untuk memproses transaksi pembelian, pengiriman file unduhan, layanan support bantuan, serta informasi update produk terbaru kami.</li>
                                        <li>Kami menjamin 100% tidak akan pernah menjual, menyewakan, maupun membagikan informasi pribadi Anda kepada pihak ketiga mana pun demi keuntungan komersial.</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- TAB: KEBIJAKAN REFUND -->
                            <div v-if="activeTab === 'kebijakan-refund'" class="space-y-6 animate-fade-in">
                                <div class="prose prose-slate max-w-none text-[#64748B] text-sm leading-[1.8] space-y-4 font-inter">
                                    <p>Harap perhatikan ketentuan pengembalian dana berikut sebelum membeli produk kami:</p>
                                    <ul class="list-disc pl-5 space-y-2">
                                        <li>Karena produk berupa **aset digital (intangible goods)** yang langsung dapat diakses setelah pembelian, seluruh transaksi yang berhasil dilakukan bersifat final dan **tidak dapat dibatalkan atau direfund**.</li>
                                        <li>Refund hanya dapat diajukan jika file template terbukti rusak, cacat kode, atau tidak dapat dijalankan sama sekali, dan tim support kami tidak dapat memperbaiki kendala tersebut dalam batas waktu 3x24 jam sejak laporan kendala diterima.</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- TAB: LISENSI -->
                            <div v-if="activeTab === 'lisensi'" class="space-y-6 animate-fade-in">
                                <div class="prose prose-slate max-w-none text-[#64748B] text-sm leading-[1.8] space-y-4 font-inter">
                                    <p>Setiap template hotspot yang dibeli di platform kami tunduk pada ketentuan lisensi berikut:</p>
                                    
                                    <h3 class="font-bold text-[#0F172A] text-lg mt-6 mb-2">1. Lisensi Standar (Personal / Single Business)</h3>
                                    <ul class="list-disc pl-5 space-y-2">
                                        <li>Berlaku untuk pemasangan di **1 router MikroTik** / 1 lokasi usaha hotspot Anda.</li>
                                        <li>Bebas mengedit gambar, logo, teks, dan tata letak untuk keperluan branding usaha pribadi Anda.</li>
                                    </ul>

                                    <h3 class="font-bold text-[#0F172A] text-lg mt-6 mb-2">2. Lisensi Komersial / Reseller (Developer)</h3>
                                    <ul class="list-disc pl-5 space-y-2">
                                        <li>Jika Anda berniat menggunakan template ini untuk kebutuhan instalasi jaringan klien Anda secara berulang-ulang, Anda wajib membeli opsi Lisensi Reseller. Hubungi support kami untuk penawaran lisensi komersial.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Help Callout Footer Card -->
                        <div class="mt-12 pt-8 border-t border-slate-100">
                            <div class="bg-gradient-to-br from-indigo-50/50 via-white to-slate-50 border border-[#EEF2F7] rounded-3xl p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-xs hover:shadow-sm transition-all duration-300">
                                <div class="space-y-1.5 text-center sm:text-left">
                                    <h4 class="font-bold text-[#0F172A] text-lg font-inter">Masih mengalami kendala?</h4>
                                    <p class="text-[#64748B] text-sm leading-[1.7] font-inter">
                                        Tim support kami siap membantu proses instalasi maupun penggunaan template hotspot.
                                    </p>
                                </div>
                                <a href="https://wa.me/6281234567890" target="_blank" class="inline-flex items-center gap-2 px-5 py-3 text-sm font-bold text-white bg-[#4F46E5] hover:bg-[#4338CA] rounded-xl transition-all shadow-md shadow-indigo-100/50 hover:shadow-lg hover:shadow-indigo-200/50 hover:-translate-y-0.5 focus:ring-2 focus:ring-[#4F46E5]/20 outline-none shrink-0 font-inter">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.012 2c-5.506 0-9.97 4.46-9.97 9.963 0 1.76.458 3.475 1.33 4.987l-1.417 5.176 5.302-1.39a9.92 9.92 0 0 0 4.755 1.2c5.507 0 9.97-4.46 9.97-9.963C21.982 6.46 17.519 2 12.012 2zm5.782 14.28c-.244.687-1.42 1.252-1.95 1.332-.49.074-.98.082-1.57.085-1.127.006-2.58-.337-4.225-1.026-3.87-1.616-6.387-5.546-6.58-5.805-.194-.258-1.564-2.08-1.564-3.966 0-1.887.986-2.813 1.338-3.183.353-.37.77-.463 1.027-.463.256 0 .513.003.738.013.238.01.55-.04.858.704.316.76 1.082 2.64 1.176 2.83.094.19.157.41.03.66-.125.253-.187.41-.375.633-.188.223-.396.497-.565.666-.188.19-.384.398-.166.772.217.373.965 1.593 2.066 2.574 1.414 1.26 2.61 1.65 2.982 1.836.37.187.587.156.804-.094.218-.25.932-1.083 1.182-1.458.25-.375.5-.313.844-.188.344.125 2.186 1.03 2.56 1.218.375.188.625.282.719.438.094.156.094.906-.15 1.594z"/></svg>
                                    Hubungi WhatsApp Support
                                </a>
                            </div>
                        </div>

                    </main>

                </div>
            </div>
        </div>
    </MarketplaceLayout>
</template>

<style scoped>
.help-card {
    border-color: #EEF2F7;
    box-shadow: 0 8px 30px rgba(15, 23, 42, 0.04);
    border-radius: 24px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.help-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(15, 23, 42, 0.07);
}
.menu-btn {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.menu-btn:hover {
    transform: translateX(4px);
}
.animate-fade-in {
    animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.font-inter {
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
}
</style>
