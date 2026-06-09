<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast.js';
import { formatUploadErrors } from '@/Utils/formatErrors.js';

const form = useForm({
    name: '', category: 'modern', short_desc: '', long_desc: '', price: 49000, discount_price: null,
    badge: '', features: [], status: 'draft', allow_edit_before_checkout: true,
    preview_gradients: ['bg-gradient-to-br from-blue-500 to-cyan-400'],
    preview_image: null, template_files: [], relative_paths: []
});

const featureInput = ref('');
function addFeature() { if (featureInput.value.trim()) { form.features.push(featureInput.value.trim()); featureInput.value = ''; } }
function removeFeature(i) { form.features.splice(i, 1); }

// ── Preview image ────────────────────
const previewFileName = ref('');
const previewFileSize = ref('');
function onPreviewChange(e) {
    const f = e.target.files?.[0];
    if (f) { previewFileName.value = f.name; previewFileSize.value = formatSize(f.size); form.preview_image = f; }
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

// ═══════════════════════════════════════
// VALIDATION SYSTEM
// ═══════════════════════════════════════

// 3-tier validation rules
const requiredFiles = ['login.html'];                        // ❌ Wajib — tanpa ini ditolak
const recommendedFiles = ['status.html', 'logout.html'];     // ⚠️ Direkomendasikan
const optionalFiles = [
    'alogin.html', 'error.html', 'voucher.html', 'kontak.html', 'bantuan.html'
];                                                          // ℹ️ Opsional
const optionalFolders = ['css/', 'js/', 'img/', 'icon/', 'assets/'];  // ℹ️ Opsional

const scanResults = ref([]);     // { name, status: 'ok'|'warn'|'error', message }
const scanDone = ref(false);
const hasErrors = ref(false);
const hasWarnings = ref(false);
const mikrotikValid = ref(null); // null=not checked, true=valid, false=invalid
const totalFileCount = ref(0);
const totalSize = ref('');

function resetScan() {
    scanResults.value = [];
    scanDone.value = false;
    hasErrors.value = false;
    hasWarnings.value = false;
    mikrotikValid.value = null;
    totalFileCount.value = 0;
    totalSize.value = '';
}

function onFolderChange(e) {
    const files = Array.from(e.target.files);
    if (!files.length) return;

    form.template_files = [];
    form.relative_paths = [];
    resetScan();

    const fileList = [];
    let total = 0;

    files.forEach(f => {
        const path = (f.webkitRelativePath || f.name).toLowerCase();
        form.template_files.push(f);
        form.relative_paths.push(f.webkitRelativePath || f.name);
        fileList.push({ file: f, path: path, originalPath: f.webkitRelativePath || f.name });
        total += f.size;
    });

    totalFileCount.value = files.length;
    totalSize.value = formatSize(total);

    // Scan required files
    requiredFiles.forEach(req => {
        const found = fileList.find(f => f.path.endsWith(req) || f.path === req);
        scanResults.value.push({
            name: req,
            label: req,
            status: found ? 'ok' : 'error',
            message: found ? 'Ditemukan' : 'Wajib — template tidak dapat diproses tanpa file ini',
        });
        if (!found) hasErrors.value = true;
    });

    // Scan recommended files (warning only)
    recommendedFiles.forEach(rec => {
        const found = fileList.find(f => f.path.endsWith(rec) || f.path === rec);
        scanResults.value.push({
            name: rec,
            label: rec,
            status: found ? 'ok' : 'warn',
            message: found ? 'Ditemukan' : 'Disarankan — beberapa fitur preview mungkin tidak tersedia',
        });
        if (!found) hasWarnings.value = true;
    });

    // Scan optional files (info only — no blocking)
    optionalFiles.forEach(opt => {
        const found = fileList.find(f => f.path.endsWith(opt) || f.path === opt);
        scanResults.value.push({
            name: opt,
            label: opt,
            status: found ? 'ok' : 'info',
            message: found ? 'Ditemukan' : 'Opsional — tidak wajib, fitur tambahan',
        });
    });

    // Scan optional folders (info only — no blocking)
    optionalFolders.forEach(folder => {
        const found = fileList.find(f => f.path.includes(folder));
        scanResults.value.push({
            name: folder,
            label: 'Folder ' + folder.replace('/', ''),
            status: found ? 'ok' : 'info',
            message: found ? 'Ditemukan' : 'Opsional — tidak wajib',
        });
    });

    // Read login.html content for MikroTik validation (using DOMParser)
    // Ambil nama file paling akhir dari path
    // Contoh:
    // default mikrotik/login.html  => login.html
    // default mikrotik/alogin.html => alogin.html
    function getFileName(path) {
        return path.split('/').pop().toLowerCase();
    }

    // Cari login.html secara exact
    // Jangan pakai endsWith('login.html')
    // karena alogin.html juga akan dianggap cocok
    const loginFile = fileList.find(f => {
        return getFileName(f.path) === 'login.html';
    });
    if (loginFile) {
        scanDone.value = false;
        loginFile.file.text().then(content => {
            const preview = content.substring(0, 800).replace(/</g, '&lt;').replace(/>/g, '&gt;');

            // ── 1. RAW STRING SCAN ──────────────
            const rawChecks = {
                linkLoginOnly: content.includes('link-login-only'),
                linkLogin: content.includes('link-login'),
                linkOrig: content.includes('link-orig'),
                nameUsername: /name\s*=\s*["']username["']/i.test(content),
                namePassword: /name\s*=\s*["']password["']/i.test(content),
                typePassword: /type\s*=\s*["']password["']/i.test(content),
                dollarVar: /\$\([a-z]/.test(content),
            };

            // ── 2. DOM PARSER SCAN ──────────────
            const parser = new DOMParser();
            const doc = parser.parseFromString(content, 'text/html');

            // Find ALL forms (with action)
            const allForms = doc.querySelectorAll('form');
            const formsInfo = [...allForms].map(f => {
                const action = f.getAttribute('action') || '(kosong)';
                const method = f.getAttribute('method') || 'get';
                const inputCount = f.querySelectorAll('input').length;
                return '<' + f.tagName.toLowerCase() + ' action="' + action + '" method="' + method + '" inputs=' + inputCount + '>';
            });

            // Find forms with link-login
            const loginForms = [];
            allForms.forEach(f => {
                const action = f.getAttribute('action') || '';
                if (action.includes('link-login')) loginForms.push(action);
            });

            // Find all inputs (any name)
            const allInputs = doc.querySelectorAll('input');
            const allInputsInfo = [...allInputs].map(i => {
                const n = i.getAttribute('name') || '(tanpa-name)';
                const t = i.getAttribute('type') || 'text';
                return '<' + t + ' name="' + n + '">';
            });

            // Find username & password inputs (flexible — check name, type, and raw)
            const userInputs = doc.querySelectorAll('input[name="username"], input[name*="user" i], input[type="text"][placeholder*="user" i]');
            const passInputs = doc.querySelectorAll('input[name="password"], input[name*="pass" i], input[type="password"]');

            // Find MikroTik variables
            const mtVars = [...new Set((content.match(/\$\([a-z][a-z-]*/g) || []))];

            // ── Build results ────────────
            const checks = [
                {
                    label: 'File login.html terbaca',
                    ok: content.length > 50,
                    found: content.length + ' karakter',
                    detail: 'File: ' + loginFile.originalPath,
                },
                {
                    label: 'Raw scan: link-login-only',
                    ok: rawChecks.linkLoginOnly,
                    found: rawChecks.linkLoginOnly ? '$(link-login-only) ✓' : (rawChecks.linkLogin ? '$(link-login) — tanpa -only' : '—'),
                    detail: 'Cek string mentah di file',
                },
                {
                    label: 'Total <form> ditemukan (DOMParser)',
                    ok: allForms.length > 0,
                    found: allForms.length + ' form',
                    detail: formsInfo.length ? formsInfo.join('\n') : 'Tidak ada <form> sama sekali di HTML',
                },
                {
                    label: 'Form dengan action link-login',
                    ok: loginForms.length > 0,
                    found: loginForms.length + ' form',
                    detail: loginForms.length ? loginForms.map(a => 'action="' + a + '"').join('\n') : 'Tidak ada form dengan link-login di action attribute',
                },
                {
                    label: 'Total <input> ditemukan',
                    ok: allInputs.length > 0,
                    found: allInputs.length + ' input',
                    detail: allInputsInfo.length ? allInputsInfo.join('\n') : 'Tidak ada <input> sama sekali di HTML',
                },
                {
                    label: 'Input username',
                    ok: userInputs.length > 0 || rawChecks.nameUsername,
                    found: userInputs.length + ' input via DOM' + (rawChecks.nameUsername ? ' + raw scan' : ''),
                    detail: userInputs.length ? [...userInputs].map(i => '<input name="' + (i.getAttribute('name') || i.type) + '">').join('\n') : (rawChecks.nameUsername ? 'Raw scan: name="username" ditemukan di HTML source' : 'name="username" tidak ditemukan'),
                },
                {
                    label: 'Input password',
                    ok: passInputs.length > 0 || rawChecks.namePassword || rawChecks.typePassword,
                    found: passInputs.length + ' input via DOM' + (rawChecks.namePassword || rawChecks.typePassword ? ' + raw scan' : ''),
                    detail: passInputs.length ? [...passInputs].map(i => '<input name="' + (i.getAttribute('name') || i.type) + '">').join('\n') : (rawChecks.namePassword || rawChecks.typePassword ? 'Raw scan: name="password" atau type="password" ditemukan' : 'name="password" tidak ditemukan'),
                },
                {
                    label: 'Raw scan: link-orig',
                    ok: rawChecks.linkOrig,
                    found: rawChecks.linkOrig ? '$(link-orig) ✓' : '—',
                    detail: 'String di file: ' + (content.match(/link-orig[^"'\s)]*/)?.[0] || 'tidak ada'),
                },
                {
                    label: 'Total <input> ditemukan (DOM)',
                    ok: allInputs.length >= 2,
                    found: allInputs.length + ' input',
                    detail: allInputsInfo.length ? allInputsInfo.slice(0, 8).join('\n') : '—',
                },
                {
                    label: 'MikroTik variables total',
                    ok: mtVars.length >= 2,
                    found: mtVars.length + ' variabel',
                    detail: mtVars.slice(0, 8).join(', ') + (mtVars.length > 8 ? ', ...' : ''),
                },
                {
                    label: 'MikroTik variables total',
                    ok: mtVars.length >= 2,
                    found: mtVars.length + ' variabel',
                    detail: mtVars.slice(0, 8).join(', ') + (mtVars.length > 8 ? ', ...' : ''),
                },
            ];

            // Minimal hanya login.html yang wajib
            let allMikrotikOk = true;

            // Jangan jadikan validasi DOM sebagai blocker
            checks.forEach(c => {
                scanResults.value.push({
                    name: 'mikrotik-' + c.label,
                    label: c.label,
                    status: c.ok ? 'ok' : 'warn',
                    message: c.ok ? 'OK' : 'Template menggunakan struktur berbeda',
                    found: c.found,
                    detail: c.detail,
                });
            });

            // Template dianggap valid jika login.html ada
            allMikrotikOk = true;
        }).catch(err => {
            scanResults.value.push({ name: 'mikrotik-error', label: 'Gagal membaca file', status: 'error', message: err.message, found: '—', detail: err.stack || '' });
            hasErrors.value = true;
            scanDone.value = true;
        });
    } else {
        mikrotikValid.value = false;
        scanDone.value = true;
    }
}

// ── Can submit? ──────────────────────
const canSubmit = computed(() => !hasErrors.value && form.name.length > 0);

// Vue 3's `reactive()` wraps File objects in a Proxy when they're pushed
// into `form.template_files`, which breaks Inertia's default FormData
// serializer. We build the FormData manually in `transform()` so files,
// relative_paths, and array fields like `features` are appended with the
// correct syntax the controller expects.
//
// We also defensively sanitize array values:
//   - filter out empty strings / null / undefined
//   - coerce primitives to string
//   - unwrap objects like { label, name, value } → their string value
// This protects against `features.0 must be a string` validation errors
// when the form's reactive state somehow contains non-string entries.
function coerceToStringArray(arr) {
    if (!Array.isArray(arr)) return [];
    return arr
        .map((v) => {
            if (v === null || v === undefined) return null;
            if (typeof v === 'string') return v.trim();
            if (typeof v === 'number' || typeof v === 'boolean') return String(v);
            if (typeof v === 'object') {
                // Try common label fields
                return (v.label ?? v.name ?? v.value ?? v.text ?? '').toString().trim();
            }
            return String(v).trim();
        })
        .filter((v) => v !== null && v !== undefined && v !== '');
}

function submit() {
    form.transform((data) => {
        const fd = new FormData();
        for (const [key, value] of Object.entries(data)) {
            if (value === null || value === undefined) continue;
            if (key === 'template_files' || key === 'relative_paths') continue;
            if (Array.isArray(value)) {
                const sanitized = coerceToStringArray(value);
                sanitized.forEach((v) => fd.append(`${key}[]`, v));
            } else if (value instanceof File || value instanceof Blob) {
                fd.append(key, value);
            } else if (typeof value === 'boolean') {
                fd.append(key, value ? '1' : '0');
            } else {
                fd.append(key, value);
            }
        }
        // Files: append each with [i] syntax
        (data.template_files || []).forEach((file, i) => {
            fd.append(`template_files[${i}]`, file);
        });
        // Relative paths: append each with [i] syntax
        (data.relative_paths || []).forEach((p, i) => {
            fd.append(`relative_paths[${i}]`, p);
        });
        return fd;
    }).post('/admin/templates', {
        onSuccess: () => {
            useToast().success(
                'Upload Berhasil',
                'Template berhasil diupload dan siap dikelola.'
            );
            resetScan();
            previewFileName.value = '';
            form.reset();
        },
        onError: (errors) => {
            useToast().error('Upload Gagal', formatUploadErrors(errors));
        },
    });
}
</script>

<template>

    <Head title="Tambah Template — Admin" />
    <AdminLayout>
        <template #title>Tambah Template Baru</template>

        <!-- ═══ LOADING OVERLAY ═══ -->
        <div v-if="form.processing"
            class="fixed inset-0 z-[9998] bg-black/40 backdrop-blur-sm flex items-center justify-center">
            <div class="bg-white rounded-2xl p-8 shadow-2xl text-center max-w-sm mx-4">
                <div
                    class="w-12 h-12 mx-auto mb-4 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin">
                </div>
                <h3 class="font-bold text-slate-900 mb-1">Menyimpan Template...</h3>
                <p class="text-sm text-slate-500">File sedang diupload ke server.</p>
            </div>
        </div>

        <div class="max-w-3xl">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- ═══ BASIC INFO ═══ -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <h2 class="font-semibold text-slate-900">Informasi Dasar</h2>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Nama Template *</label>
                        <input v-model="form.name" type="text" required
                            class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Kategori *</label>
                            <select v-model="form.category" required
                                class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl">
                                <option value="modern">Modern</option>
                                <option value="classic">Classic</option>
                                <option value="minimalis">Minimalis</option>
                                <option value="pro">Professional</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Badge</label>
                            <select v-model="form.badge"
                                class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl">
                                <option value="">Tanpa Badge</option>
                                <option value="Best Seller">Best Seller</option>
                                <option value="Baru">Baru</option>
                                <option value="Populer">Populer</option>
                                <option value="Sale">Sale</option>
                                <option value="Trending">Trending</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Harga (Rp)
                                *</label><input v-model="form.price" type="number" min="0" required
                                class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl"></div>
                        <div><label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Diskon
                                (Rp)</label><input v-model="form.discount_price" type="number" min="0"
                                class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl"></div>
                    </div>
                    <div><label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Status
                            *</label><select v-model="form.status" required
                            class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select></div>
                </div>

                <!-- ═══ DESCRIPTION ═══ -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <h2 class="font-semibold text-slate-900">Deskripsi</h2>
                    <div><label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Deskripsi
                            Singkat</label><textarea v-model="form.short_desc" rows="2" maxlength="500"
                            class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl resize-none"></textarea>
                    </div>
                    <div><label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Deskripsi
                            Lengkap</label><textarea v-model="form.long_desc" rows="4"
                            class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl resize-none"></textarea>
                    </div>
                </div>

                <!-- ═══ FEATURES ═══ -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <h2 class="font-semibold text-slate-900">Fitur / Tags</h2>
                    <div class="flex flex-wrap gap-2 mb-3"><span v-for="(f, i) in form.features" :key="i"
                            class="inline-flex items-center gap-1 text-xs bg-indigo-50 text-indigo-700 px-2.5 py-1 rounded-lg font-medium">{{
                                f }}<button @click="removeFeature(i)"
                                class="ml-1 hover:text-red-500">&times;</button></span></div>
                    <div class="flex gap-2"><input v-model="featureInput" @keyup.enter="addFeature" type="text"
                            placeholder="Tambah fitur..."
                            class="flex-1 px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl"><button
                            type="button" @click="addFeature"
                            class="px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-xl">Tambah</button>
                    </div>
                </div>

                <!-- ═══ FOLDER UPLOAD + VALIDATION ═══ -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <h2 class="font-semibold text-slate-900">Upload Template *</h2>
                    <p class="text-xs text-slate-400">Pilih folder template hotspot dari komputer Anda.</p>

                    <input type="file" webkitdirectory directory multiple @change="onFolderChange"
                        class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">

                    <!-- ═══ VALIDATION CHECKLIST ═══ -->
                    <div v-if="totalFileCount > 0" class="mt-4 space-y-3">
                        <!-- Summary -->
                        <div class="text-xs flex items-center gap-2">
                            <span class="text-slate-500">{{ totalFileCount }} file · {{ totalSize }}</span>
                            <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                            <span v-if="!scanDone" class="text-amber-500 flex items-center gap-1">
                                <span
                                    class="w-3 h-3 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></span>
                                Memindai...
                            </span>
                            <span v-else-if="hasErrors" class="text-red-500 font-semibold">Ada masalah — harap
                                diperbaiki</span>
                            <span v-else-if="hasWarnings" class="text-amber-500 font-semibold">Dapat diupload dengan
                                catatan</span>
                            <span v-else class="text-emerald-500 font-semibold">Template valid dan siap diupload</span>
                        </div>

                        <!-- Checklist -->
                        <div class="bg-slate-50 rounded-xl p-4 space-y-1.5 max-h-64 overflow-y-auto">
                            <!-- Section: Files -->
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">File
                                Template</p>
                            <div v-for="item in scanResults.filter(r => !r.name.startsWith('mikrotik-') && !r.name.endsWith('/'))"
                                :key="item.name" class="flex items-center gap-2 text-xs py-1">
                                <span v-if="item.status === 'ok'" class="text-emerald-500 shrink-0">✅</span>
                                <span v-else-if="item.status === 'warn'" class="text-amber-500 shrink-0">⚠️</span>
                                <span v-else-if="item.status === 'info'" class="text-slate-400 shrink-0">ℹ️</span>
                                <span v-else class="text-red-500 shrink-0">❌</span>
                                <span class="font-medium"
                                    :class="item.status === 'error' ? 'text-red-600' : item.status === 'warn' ? 'text-amber-600' : 'text-slate-700'">{{
                                        item.label }}</span>
                                <span class="text-slate-400">{{ item.message }}</span>
                            </div>

                            <!-- Section: Structure -->
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-3 mb-1">
                                Struktur
                                Folder (opsional)</p>
                            <div v-for="item in scanResults.filter(r => r.name.endsWith('/'))" :key="item.name"
                                class="flex items-center gap-2 text-xs py-1">
                                <span v-if="item.status === 'ok'" class="text-emerald-500 shrink-0">✅</span>
                                <span v-else-if="item.status === 'info'" class="text-slate-400 shrink-0">ℹ️</span>
                                <span class="font-medium text-slate-700">{{ item.label }}</span>
                                <span class="text-slate-400">{{ item.message }}</span>
                            </div>

                            <!-- Section: MikroTik Validation -->
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-3 mb-1">
                                Validasi DOM
                                (DOMParser)</p>
                            <div v-for="item in scanResults.filter(r => r.name.startsWith('mikrotik-') && r.name !== 'mikrotik-preview')"
                                :key="item.name" class="py-1.5">
                                <div class="flex items-start gap-2">
                                    <span v-if="item.status === 'ok'" class="text-emerald-500 shrink-0 mt-0.5">✅</span>
                                    <span v-else class="text-red-500 shrink-0 mt-0.5">❌</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-baseline gap-2 flex-wrap">
                                            <span class="text-xs font-medium"
                                                :class="item.status === 'error' ? 'text-red-600' : 'text-slate-700'">{{
                                                    item.label }}</span>
                                            <code class="text-[10px] font-mono px-1.5 py-0.5 rounded"
                                                :class="item.status === 'ok' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600'">{{
                                                    item.found }}</code>
                                        </div>
                                        <p v-if="item.detail" class="text-[10px] text-slate-400 mt-0.5 leading-relaxed">
                                            {{
                                                item.detail }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Content Preview -->
                            <div v-for="item in scanResults.filter(r => r.name === 'mikrotik-preview')" :key="item.name"
                                class="mt-2">
                                <details class="group">
                                    <summary
                                        class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-600 select-none">
                                        Cuplikan login.html (600 karakter pertama) ▾</summary>
                                    <pre
                                        class="text-[10px] bg-slate-200 rounded-lg p-2.5 overflow-x-auto max-h-32 font-mono text-slate-600 leading-relaxed whitespace-pre-wrap break-all mt-1">
                                {{ item.preview }}</pre>
                                </details>
                            </div>
                        </div>

                        <!-- Action hint -->
                        <div v-if="hasErrors"
                            class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700">
                            <strong>Upload tidak dapat dilanjutkan.</strong> File wajib tidak ditemukan atau form login
                            tidak
                            valid. Tombol simpan dinonaktifkan.
                        </div>
                        <div v-else-if="hasWarnings"
                            class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-700">
                            <strong>Template dapat diupload,</strong> namun beberapa fitur mungkin tidak tersedia. Anda
                            tetap
                            dapat menyimpan dan publish.
                        </div>
                        <div v-else
                            class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-700">
                            <strong>Template valid!</strong> Semua file dan validasi MikroTik lengkap. Siap diupload dan
                            dijual.
                        </div>
                    </div>
                </div>

                <!-- ═══ PREVIEW IMAGE ═══ -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <h2 class="font-semibold text-slate-900">Preview Image (opsional)</h2>
                    <div><label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Gambar Preview
                            (PNG/JPG, max
                            2MB)</label>
                        <input type="file" accept="image/png,image/jpeg" @change="onPreviewChange"
                            class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <div v-if="previewFileName"
                            class="mt-2 flex items-center gap-2 text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-medium">{{ previewFileName }}</span><span class="text-emerald-400">({{
                                previewFileSize }})</span>
                        </div>
                    </div>
                </div>

                <!-- ═══ OPTIONS ═══ -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <label class="flex items-center gap-3 cursor-pointer select-none"><input
                            v-model="form.allow_edit_before_checkout" type="checkbox"
                            class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span
                            class="text-sm text-slate-700">Izinkan pembeli mengedit sebelum checkout</span></label>
                </div>

                <!-- ═══ SUBMIT ═══ -->
                <div class="flex justify-end gap-3">
                    <Link href="/admin/templates"
                        class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50">
                        Batal</Link>
                    <button type="submit" :disabled="!canSubmit || form.processing"
                        class="px-5 py-2.5 text-sm font-semibold text-white rounded-xl transition-all flex items-center gap-2 shadow-sm"
                        :class="canSubmit ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-slate-300 text-slate-500 cursor-not-allowed'">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Template' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
