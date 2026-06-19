<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

// Contoh-contoh kode untuk copy-paste reference
const folderStructure = `template-folder/
│
├── login.html      (wajib)
├── status.html     (wajib)
├── logout.html     (wajib)
├── style.css       (wajib)
│
├── images/
│   ├── logo.png
│   ├── banner.jpg
│   └── ...
│
└── assets/
    ├── script.js
    └── ...`;

const textExample = `<h1 data-edit="brand_name" data-label="Nama Brand">
    Ipan Coffee
</h1>`;

const imageExample = `<img
    src="images/logo.png"
    data-edit-image="logo"
    data-label="Logo Brand">`;

const backgroundExample = `<section
    data-edit-bg="background_image"
    data-label="Background">
</section>`;

const linkExample = `<a
    href="https://wa.me/6281234567890"
    data-edit-link="whatsapp_link"
    data-label="Link WhatsApp">
</a>`;

const placeholderExample = `<input
    placeholder="Masukkan Username"
    data-edit-placeholder="username_placeholder"
    data-label="Placeholder Username">`;

const mikrotikFormExample = `<form action="$(link-login-only)" method="post">
    <input type="hidden" name="dst" value="$(link-orig)">
</form>`;

const supportedMarkers = [
    { name: 'data-edit',         desc: 'Untuk teks — replace innerText',         type: 'text' },
    { name: 'data-edit-image',   desc: 'Untuk gambar di tag <img> — replace src', type: 'image' },
    { name: 'data-edit-bg',      desc: 'Untuk background section — replace background-image CSS', type: 'image' },
    { name: 'data-edit-link',    desc: 'Untuk link di tag <a> — replace href',   type: 'link' },
    { name: 'data-edit-placeholder', desc: 'Untuk input placeholder — replace attribute', type: 'text' },
];

const requiredFiles = [
    { name: 'login.html',  desc: 'Halaman login MikroTik interaktif' },
    { name: 'status.html', desc: 'Halaman status koneksi user' },
    { name: 'logout.html', desc: 'Halaman logout setelah sesi' },
    { name: 'style.css',   desc: 'Stylesheet utama template' },
];

const scannerPreview = {
    files: [
        'login.html ditemukan',
        'status.html ditemukan',
        'logout.html ditemukan',
        'style.css ditemukan',
    ],
    fields: [
        '15 field teks ditemukan',
        '2 gambar ditemukan',
        '1 background ditemukan',
        '1 link ditemukan',
    ],
    status: 'Kompatibel dengan Editor',
};

const editorFieldPreview = [
    'Nama Brand',
    'Logo',
    'Background',
    'Teks Sambutan',
    'Nomor WhatsApp',
    'Footer',
];

const prohibitions = [
    'Menghapus form login MikroTik.',
    'Menghapus username input.',
    'Menghapus password input.',
    'Menghapus variabel MikroTik ( $(link-login-only), $(link-orig), dll ).',
    'Menggunakan file HTML yang rusak.',
    'Menggunakan script yang menghalangi editor membaca data-edit.',
];
</script>

<template>
<Head title="Standar Template Editor — MarketTemplate" />

<AdminLayout>
    <template #title>Standar Template Editor</template>

    <!-- ═══════════════ HEADER ═══════════════ -->
    <div class="mb-8">
        <p class="text-sm font-semibold text-indigo-600 uppercase tracking-wider mb-2">Panduan Creator</p>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Standar Template Editor</h1>
        <p class="text-slate-500 mt-2 max-w-3xl">Panduan ini menjelaskan seluruh aturan template yang kompatibel dengan sistem editor. Buat template hotspot yang langsung terbaca oleh editor tanpa perlu menebak-nebak format yang benar.</p>
    </div>

    <!-- ═══════════════ 1. STRUKTUR FOLDER ═══════════════ -->
    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 mb-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-extrabold text-sm">1</span>
            <h2 class="text-xl font-extrabold text-slate-900">Struktur Folder Template</h2>
        </div>
        <p class="text-sm text-slate-600 mb-4">Template wajib memiliki struktur folder sebagai berikut:</p>
        <pre class="bg-slate-900 text-slate-100 rounded-xl p-5 text-xs font-mono leading-relaxed overflow-x-auto whitespace-pre">{{ folderStructure }}</pre>
    </section>

    <!-- ═══════════════ 2. FILE WAJIB ═══════════════ -->
    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 mb-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-extrabold text-sm">2</span>
            <h2 class="text-xl font-extrabold text-slate-900">File Wajib</h2>
        </div>
        <p class="text-sm text-slate-600 mb-5">Jika salah satu file di bawah ini tidak ada, template dianggap tidak kompatibel dengan editor:</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div v-for="f in requiredFiles" :key="f.name" class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                <span class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </span>
                <div>
                    <p class="font-mono font-bold text-slate-900 text-sm">{{ f.name }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ f.desc }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════ 3. PENANDA EDITOR ═══════════════ -->
    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 mb-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-extrabold text-sm">3</span>
            <h2 class="text-xl font-extrabold text-slate-900">Penanda Editor yang Didukung</h2>
        </div>
        <p class="text-sm text-slate-600 mb-5">Tambahkan salah satu atribut di bawah ini ke elemen HTML untuk membuatnya bisa diedit dari panel user.</p>

        <div class="space-y-5">
            <!-- Teks -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div class="bg-slate-50 border-b border-slate-200 px-4 py-2.5 flex items-center gap-2">
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-indigo-100 text-indigo-700 rounded">TEKS</span>
                    <code class="text-xs font-mono text-slate-700">data-edit</code>
                </div>
                <pre class="bg-slate-900 text-slate-100 p-5 text-xs font-mono leading-relaxed overflow-x-auto">{{ textExample }}</pre>
            </div>

            <!-- Gambar -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div class="bg-slate-50 border-b border-slate-200 px-4 py-2.5 flex items-center gap-2">
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-700 rounded">GAMBAR</span>
                    <code class="text-xs font-mono text-slate-700">data-edit-image</code>
                </div>
                <pre class="bg-slate-900 text-slate-100 p-5 text-xs font-mono leading-relaxed overflow-x-auto">{{ imageExample }}</pre>
            </div>

            <!-- Background -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div class="bg-slate-50 border-b border-slate-200 px-4 py-2.5 flex items-center gap-2">
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-purple-100 text-purple-700 rounded">BACKGROUND</span>
                    <code class="text-xs font-mono text-slate-700">data-edit-bg</code>
                </div>
                <pre class="bg-slate-900 text-slate-100 p-5 text-xs font-mono leading-relaxed overflow-x-auto">{{ backgroundExample }}</pre>
            </div>

            <!-- Link -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div class="bg-slate-50 border-b border-slate-200 px-4 py-2.5 flex items-center gap-2">
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-700 rounded">LINK</span>
                    <code class="text-xs font-mono text-slate-700">data-edit-link</code>
                </div>
                <pre class="bg-slate-900 text-slate-100 p-5 text-xs font-mono leading-relaxed overflow-x-auto">{{ linkExample }}</pre>
            </div>

            <!-- Placeholder -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div class="bg-slate-50 border-b border-slate-200 px-4 py-2.5 flex items-center gap-2">
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-700 rounded">PLACEHOLDER</span>
                    <code class="text-xs font-mono text-slate-700">data-edit-placeholder</code>
                </div>
                <pre class="bg-slate-900 text-slate-100 p-5 text-xs font-mono leading-relaxed overflow-x-auto">{{ placeholderExample }}</pre>
            </div>
        </div>
    </section>

    <!-- ═══════════════ 4. VARIABLE MIKROTIK ═══════════════ -->
    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 mb-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-extrabold text-sm">4</span>
            <h2 class="text-xl font-extrabold text-slate-900">Variabel MikroTik Wajib</h2>
        </div>
        <p class="text-sm text-slate-600 mb-4">Template login harus tetap menggunakan variabel MikroTik untuk kompatibilitas penuh dengan router:</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mb-5">
            <code class="px-3 py-2 bg-amber-50 border border-amber-200 text-amber-800 text-xs font-mono rounded-lg text-center">$(link-login-only)</code>
            <code class="px-3 py-2 bg-amber-50 border border-amber-200 text-amber-800 text-xs font-mono rounded-lg text-center">$(link-orig)</code>
            <code class="px-3 py-2 bg-amber-50 border border-amber-200 text-amber-800 text-xs font-mono rounded-lg text-center">$(link-status)</code>
        </div>
        <p class="text-xs text-slate-500 mb-2">Contoh form login dengan variabel MikroTik:</p>
        <pre class="bg-slate-900 text-slate-100 rounded-xl p-5 text-xs font-mono leading-relaxed overflow-x-auto">{{ mikrotikFormExample }}</pre>
    </section>

    <!-- ═══════════════ 5. ATURAN EDITOR ═══════════════ -->
    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 mb-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-extrabold text-sm">5</span>
            <h2 class="text-xl font-extrabold text-slate-900">Aturan Editor</h2>
        </div>
        <ul class="space-y-2.5">
            <li class="flex items-start gap-3 text-sm text-slate-700">
                <span class="w-5 h-5 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </span>
                <span>Jangan mengubah struktur HTML saat proses edit.</span>
            </li>
            <li class="flex items-start gap-3 text-sm text-slate-700">
                <span class="w-5 h-5 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </span>
                <span>Jangan mengubah CSS saat proses edit.</span>
            </li>
            <li class="flex items-start gap-3 text-sm text-slate-700">
                <span class="w-5 h-5 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 3l18 18"/></svg>
                </span>
                <span>Editor hanya boleh mengubah elemen yang memiliki penanda:</span>
            </li>
        </ul>
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
            <code v-for="m in supportedMarkers" :key="m.name" class="px-2.5 py-1.5 bg-slate-100 text-slate-700 text-[11px] font-mono rounded text-center">{{ m.name }}</code>
        </div>
    </section>

    <!-- ═══════════════ 6. PANEL EDITOR ═══════════════ -->
    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 mb-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-extrabold text-sm">6</span>
            <h2 class="text-xl font-extrabold text-slate-900">Yang Akan Tampil di Panel Editor</h2>
        </div>
        <p class="text-sm text-slate-600 mb-4">Editor otomatis membuat field berdasarkan penanda yang ditemukan di template. Contoh panel editor:</p>
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 max-w-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Field Editable</p>
            <div class="space-y-2.5">
                <div v-for="f in editorFieldPreview" :key="f">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">{{ f }}</label>
                    <input type="text" :placeholder="f" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white" />
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════ 7. HASIL SCAN ═══════════════ -->
    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 mb-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-extrabold text-sm">7</span>
            <h2 class="text-xl font-extrabold text-slate-900">Hasil Scan Template</h2>
        </div>
        <p class="text-sm text-slate-600 mb-4">Saat upload, sistem menampilkan hasil scan untuk verifikasi kompatibilitas. Contoh output:</p>

        <div class="bg-slate-900 text-slate-100 rounded-2xl p-5 font-mono text-xs leading-relaxed">
            <div class="space-y-1">
                <div v-for="f in scannerPreview.files" :key="f" class="text-emerald-400">✓ {{ f }}</div>
            </div>
            <div class="my-3 border-t border-slate-700"></div>
            <div class="space-y-1">
                <div v-for="f in scannerPreview.fields" :key="f" class="text-emerald-400">✓ {{ f }}</div>
            </div>
            <div class="my-3 border-t border-slate-700"></div>
            <div class="text-slate-400 text-[10px] uppercase tracking-wider font-bold">Status</div>
            <div class="text-emerald-400 font-bold mt-1">✓ {{ scannerPreview.status }}</div>
        </div>
    </section>

    <!-- ═══════════════ 8. LARANGAN ═══════════════ -->
    <section class="bg-white rounded-2xl border border-rose-200 shadow-sm p-6 sm:p-8 mb-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-extrabold text-sm">8</span>
            <h2 class="text-xl font-extrabold text-slate-900">Larangan</h2>
        </div>
        <p class="text-sm text-slate-600 mb-4">Tidak diperbolehkan:</p>
        <ul class="space-y-2.5">
            <li v-for="(item, i) in prohibitions" :key="i" class="flex items-start gap-3 text-sm text-slate-700">
                <span class="w-5 h-5 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                </span>
                <span>{{ item }}</span>
            </li>
        </ul>
    </section>

    <!-- ═══════════════ TUJUAN AKHIR ═══════════════ -->
    <section class="bg-gradient-to-br from-indigo-50 to-violet-50 border border-indigo-200 rounded-2xl p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center text-white text-lg">🎯</span>
            <h2 class="text-xl font-extrabold text-slate-900">Tujuan Akhir</h2>
        </div>
        <p class="text-sm text-slate-700 leading-relaxed">
            Agar setiap creator dapat membuat template yang <strong>langsung kompatibel dengan editor</strong> tanpa perlu modifikasi tambahan dari admin. Cukup ikuti 8 aturan di atas, lalu upload folder template seperti biasa.
        </p>
        <div class="mt-5 flex flex-wrap gap-2.5">
            <Link href="/admin/templates/create" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Upload Template Baru
            </Link>
            <Link href="/admin/templates" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                Lihat Daftar Template
            </Link>
        </div>
    </section>
</AdminLayout>
</template>
