<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout.vue';

const props = defineProps({
    initialTab: { type: String, default: 'cara-order' }
});

const activeTab = ref(props.initialTab);

// Watch for changes in initialTab (if user clicks another footer link when already on Bantuan page)
watch(() => props.initialTab, (newTab) => {
    activeTab.value = newTab;
});

const menuItems = [
    { value: 'cara-order', label: 'Cara Order', category: 'bantuan' },
    { value: 'instalasi', label: 'Panduan Instalasi', category: 'bantuan' },
    { value: 'faq', label: 'FAQ (Tanya Jawab)', category: 'bantuan' },
    { value: 'kontak', label: 'Kontak Support', category: 'bantuan' },
    { value: 'syarat-ketentuan', label: 'Syarat & Ketentuan', category: 'kebijakan' },
    { value: 'kebijakan-privasi', label: 'Kebijakan Privasi', category: 'kebijakan' },
    { value: 'kebijakan-refund', label: 'Kebijakan Refund', category: 'kebijakan' },
    { value: 'lisensi', label: 'Lisensi Penggunaan', category: 'kebijakan' },
];

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
        <div class="min-h-screen bg-slate-50 antialiased pt-24 pb-16">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Page Title -->
                <div class="mb-10 text-center sm:text-left">
                    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Pusat Bantuan &amp; Informasi</h1>
                    <p class="text-slate-500 text-sm sm:text-base mt-2">Temukan panduan pemesanan, instruksi instalasi MikroTik, dan kebijakan lisensi di satu tempat.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-[280px_1fr] gap-8 items-start">
                    
                    <!-- Left Sidebar Tabs Navigation -->
                    <aside class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-6">
                        <div>
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Panduan &amp; Support</h3>
                            <nav class="space-y-1">
                                <button v-for="item in menuItems.filter(i => i.category === 'bantuan')" :key="item.value"
                                    @click="selectTab(item.value)"
                                    class="w-full text-left px-3.5 py-2.5 text-sm rounded-xl font-medium transition-colors"
                                    :class="activeTab === item.value ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:bg-slate-50'">
                                    {{ item.label }}
                                </button>
                            </nav>
                        </div>

                        <div class="pt-4 border-t border-slate-100">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Kebijakan &amp; Legal</h3>
                            <nav class="space-y-1">
                                <button v-for="item in menuItems.filter(i => i.category === 'kebijakan')" :key="item.value"
                                    @click="selectTab(item.value)"
                                    class="w-full text-left px-3.5 py-2.5 text-sm rounded-xl font-medium transition-colors"
                                    :class="activeTab === item.value ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:bg-slate-50'">
                                    {{ item.label }}
                                </button>
                            </nav>
                        </div>
                    </aside>

                    <!-- Right Content Area -->
                    <main class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm min-h-[500px]">
                        
                        <!-- TAB: CARA ORDER -->
                        <div v-if="activeTab === 'cara-order'" class="space-y-6">
                            <h2 class="text-2xl font-extrabold text-slate-900">Cara Order Template Hotspot</h2>
                            <p class="text-slate-600 text-sm leading-relaxed">Berikut adalah 3 langkah mudah untuk membeli dan memiliki template hotspot MikroTik impian Anda:</p>
                            
                            <div class="space-y-6 pt-2">
                                <div class="flex gap-4">
                                    <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center shrink-0 text-sm">1</span>
                                    <div>
                                        <h3 class="font-bold text-slate-900 text-base">Pilih &amp; Kustomisasi Template</h3>
                                        <p class="text-slate-600 text-sm mt-1 leading-relaxed">Cari template terbaik di katalog kami. Anda bisa menguji tampilannya secara langsung menggunakan editor visual kami sebelum melakukan checkout.</p>
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center shrink-0 text-sm">2</span>
                                    <div>
                                        <h3 class="font-bold text-slate-900 text-base">Lakukan Pembayaran Instan</h3>
                                        <p class="text-slate-600 text-sm mt-1 leading-relaxed">Klik beli, masukkan alamat email aktif Anda, pilih metode pembayaran otomatis (QRIS, E-Wallet, Virtual Account, dll.), dan selesaikan pembayaran Anda.</p>
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center shrink-0 text-sm">3</span>
                                    <div>
                                        <h3 class="font-bold text-slate-900 text-base">Unduh File Siap Pakai</h3>
                                        <p class="text-slate-600 text-sm mt-1 leading-relaxed">Setelah pembayaran terkonfirmasi, masuk ke Dashboard akun Anda untuk mengunduh file template hotspot dalam format ZIP yang siap di-upload ke MikroTik Anda.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: INSTALASI -->
                        <div v-if="activeTab === 'instalasi'" class="space-y-6">
                            <h2 class="text-2xl font-extrabold text-slate-900">Panduan Instalasi MikroTik</h2>
                            <p class="text-slate-600 text-sm leading-relaxed">Setelah mengunduh file ZIP template, ikuti panduan berikut untuk memasangnya di router MikroTik Anda:</p>
                            
                            <div class="space-y-5 pt-2">
                                <div class="flex gap-3 items-start">
                                    <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-slate-600 text-sm leading-relaxed"><strong>Langkah 1:</strong> Ekstrak file ZIP template di komputer Anda. Anda akan mendapatkan sebuah folder berisi file <code>login.html</code>, <code>status.html</code>, dan file pendukung lainnya.</p>
                                </div>
                                <div class="flex gap-3 items-start">
                                    <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-slate-600 text-sm leading-relaxed"><strong>Langkah 2:</strong> Buka aplikasi <strong>Winbox</strong> dan hubungkan ke router MikroTik Anda.</p>
                                </div>
                                <div class="flex gap-3 items-start">
                                    <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-slate-600 text-sm leading-relaxed"><strong>Langkah 3:</strong> Masuk ke menu <strong>Files</strong> di Winbox. Drag (seret) folder template yang sudah diekstrak tadi dari komputer Anda, lalu drop (lepaskan) ke dalam daftar file di Winbox.</p>
                                </div>
                                <div class="flex gap-3 items-start">
                                    <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-slate-600 text-sm leading-relaxed"><strong>Langkah 4:</strong> Buka menu <strong>IP</strong> -&gt; <strong>Hotspot</strong> -&gt; tab <strong>Server Profiles</strong>. Klik dua kali profil hotspot aktif Anda. Pada bagian <strong>HTML Directory</strong>, pilih nama folder template yang barusan Anda upload. Klik <strong>Apply</strong> dan <strong>OK</strong>.</p>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: FAQ -->
                        <div v-if="activeTab === 'faq'" class="space-y-6">
                            <h2 class="text-2xl font-extrabold text-slate-900">FAQ (Tanya Jawab)</h2>
                            
                            <div class="space-y-5 pt-2">
                                <div class="border-b border-slate-100 pb-4">
                                    <h3 class="font-bold text-slate-900 text-base">Q: Apakah template ini aman dari eksploitasi keamanan?</h3>
                                    <p class="text-slate-600 text-sm mt-1.5 leading-relaxed">A: Sangat aman. Semua template kami dibangun menggunakan kode HTML, CSS, dan JavaScript standar yang bersih tanpa skrip pihak ketiga yang mencurigakan. Seluruh data login diproses langsung oleh sistem internal RouterOS MikroTik Anda.</p>
                                </div>
                                <div class="border-b border-slate-100 pb-4">
                                    <h3 class="font-bold text-slate-900 text-base">Q: Apakah mendukung RouterOS v6 dan v7?</h3>
                                    <p class="text-slate-600 text-sm mt-1.5 leading-relaxed">A: Ya. Semua template kami dirancang secara universal untuk berjalan dengan mulus di MikroTik RouterOS versi v6 maupun v7.</p>
                                </div>
                                <div class="border-b border-slate-100 pb-4">
                                    <h3 class="font-bold text-slate-900 text-base">Q: Apakah saya bisa mengganti harga paket di template?</h3>
                                    <p class="text-slate-600 text-sm mt-1.5 leading-relaxed">A: Bisa. Anda dapat mengedit daftar harga paket menggunakan fitur visual editor kami sebelum melakukan checkout, atau Anda bisa langsung mengedit file <code>login.html</code> menggunakan text editor (seperti Notepad/VS Code) setelah file diunduh.</p>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: KONTAK SUPPORT -->
                        <div v-if="activeTab === 'kontak'" class="space-y-6">
                            <h2 class="text-2xl font-extrabold text-slate-900">Hubungi Kontak Support</h2>
                            <p class="text-slate-600 text-sm leading-relaxed">Tim bantuan kami siap melayani keluhan, pertanyaan seputar lisensi, maupun bantuan instalasi hotspot Anda:</p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div class="border border-slate-200 rounded-2xl p-5 shadow-xs bg-slate-50/50">
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">WhatsApp Admin</h3>
                                    <p class="text-base font-extrabold text-slate-900">0812-3456-7890</p>
                                    <a href="https://wa.me/6281234567890" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-700 mt-3 transition-colors">
                                        Chat Sekarang →
                                    </a>
                                </div>
                                <div class="border border-slate-200 rounded-2xl p-5 shadow-xs bg-slate-50/50">
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Email Support</h3>
                                    <p class="text-base font-extrabold text-slate-900">support@templatehotspot.id</p>
                                    <a href="mailto:support@templatehotspot.id" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-700 mt-3 transition-colors">
                                        Kirim Email →
                                    </a>
                                </div>
                            </div>

                            <p class="text-xs text-slate-400 mt-4 leading-relaxed">* Jam Operasional Bantuan: Senin - Minggu (Pukul 08:00 WIB hingga 22:00 WIB).</p>
                        </div>

                        <!-- TAB: SYARAT & KETENTUAN -->
                        <div v-if="activeTab === 'syarat-ketentuan'" class="space-y-6">
                            <h2 class="text-2xl font-extrabold text-slate-900">Syarat &amp; Ketentuan Penggunaan</h2>
                            <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed space-y-4">
                                <p>Dengan membeli dan menggunakan produk template hotspot dari situs kami, Anda menyetujui ketentuan berikut:</p>
                                <ul class="list-disc pl-5 space-y-2">
                                    <li>Anda dilarang keras untuk mendistribusikan ulang, membagikan secara gratis, maupun menjual kembali file template yang telah Anda beli tanpa persetujuan tertulis dari kami.</li>
                                    <li>Seluruh hak kekayaan intelektual dan desain visual produk template hotspot tetap menjadi milik eksklusif pihak **Template Hotspot**.</li>
                                    <li>Kami berhak melakukan pembatasan atau penonaktifkan akun bagi pembeli yang kedapatan melanggar aturan distribusi file.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- TAB: KEBIJAKAN PRIVASI -->
                        <div v-if="activeTab === 'kebijakan-privasi'" class="space-y-6">
                            <h2 class="text-2xl font-extrabold text-slate-900">Kebijakan Privasi</h2>
                            <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed space-y-4">
                                <p>Kami sangat menghargai privasi informasi pribadi Anda. Berikut adalah ringkasan kebijakan pengumpulan data kami:</p>
                                <ul class="list-disc pl-5 space-y-2">
                                    <li>Kami hanya mengumpulkan informasi pribadi yang Anda berikan secara sadar saat melakukan pendaftaran akun atau pemesanan template (seperti nama lengkap, alamat email, dan nomor telepon).</li>
                                    <li>Informasi pribadi Anda hanya digunakan untuk memproses transaksi pembelian, pengiriman file unduhan, layanan support bantuan, serta informasi update produk terbaru kami.</li>
                                    <li>Kami menjamin 100% tidak akan pernah menjual, menyewakan, maupun membagikan informasi pribadi Anda kepada pihak ketiga mana pun demi keuntungan komersial.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- TAB: KEBIJAKAN REFUND -->
                        <div v-if="activeTab === 'kebijakan-refund'" class="space-y-6">
                            <h2 class="text-2xl font-extrabold text-slate-900">Kebijakan Pengembalian Dana (Refund)</h2>
                            <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed space-y-4">
                                <p>Harap perhatikan ketentuan pengembalian dana berikut sebelum membeli produk kami:</p>
                                <ul class="list-disc pl-5 space-y-2">
                                    <li>Karena produk berupa **aset digital (intangible goods)** yang langsung dapat diakses setelah pembelian, seluruh transaksi yang berhasil dilakukan bersifat final dan **tidak dapat dibatalkan atau direfund**.</li>
                                    <li>Refund hanya dapat diajukan jika file template terbukti rusak, cacat kode, atau tidak dapat dijalankan sama sekali, dan tim support kami tidak dapat memperbaiki kendala tersebut dalam batas waktu 3x24 jam sejak laporan kendala diterima.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- TAB: LISENSI -->
                        <div v-if="activeTab === 'lisensi'" class="space-y-6">
                            <h2 class="text-2xl font-extrabold text-slate-900">Lisensi Penggunaan Produk</h2>
                            <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed space-y-4">
                                <p>Setiap template hotspot yang dibeli di platform kami tunduk pada ketentuan lisensi berikut:</p>
                                <h3 class="font-bold text-slate-900 text-sm mt-4">1. Lisensi Standar (Personal / Single Business)</h3>
                                <ul class="list-disc pl-5 space-y-1">
                                    <li>Berlaku untuk pemasangan di **1 router MikroTik** / 1 lokasi usaha hotspot Anda.</li>
                                    <li>Bebas mengedit gambar, logo, teks, dan tata letak untuk keperluan branding usaha pribadi Anda.</li>
                                </ul>

                                <h3 class="font-bold text-slate-900 text-sm mt-4">2. Lisensi Komersial / Reseller (Developer)</h3>
                                <ul class="list-disc pl-5 space-y-1">
                                    <li>Jika Anda berniat menggunakan template ini untuk kebutuhan instalasi jaringan klien Anda secara berulang-ulang, Anda wajib membeli opsi Lisensi Reseller. Hubungi support kami untuk penawaran lisensi komersial.</li>
                                </ul>
                            </div>
                        </div>

                    </main>

                </div>
            </div>
        </div>
    </MarketplaceLayout>
</template>
