<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, reactive, watch, onMounted, computed } from 'vue';

const props = defineProps({
    template: { type: Object, default: null },
    canLogin: Boolean,
});

// Computed template (reactive proxy untuk akses di script)
const template = computed(() => props.template);

// ── State ────────────────────────────────
const fields = ref([]);        // Array dari backend
const defaultValues = ref({}); // snapshot untuk Reset
const values = reactive({});   // {brand_name: 'Ipan Coffee', ...}
const hasDataEdit = ref(false);
const saving = ref(false);
const resetting = ref(false);
const downloading = ref(false);
const lastSaved = ref(null);
const lastSavedPath = ref(null);
let lastSavedTimer = null;  // timer untuk auto-hide "Tersimpan pukul ..." indicator
const errorMsg = ref(null);
const previewKey = ref(0);     // increment untuk force re-render iframe (initial load, reset, manual refresh saja)
const previewSrc = ref('');    // URL iframe src — di-set SEKALI saat load, TIDAK berubah tiap keystroke
const previewMode = ref('desktop'); // 'desktop' | 'tablet' | 'mobile'
const appliedValues = reactive({}); // snapshot values yang sudah di-save ke server
const iframeRef = ref(null);   // ref ke <iframe> element — untuk contentDocument access
const iframeReady = ref(false); // true setelah iframe load selesai (pakai @load)
const defaults = ref({});      // default values dari master (untuk reset DOM)

// Computed: ada perubahan yang belum di-save ke server
// Reactive terhadap perubahan BOTH `values` (form state) DAN `appliedValues`
// (last-saved snapshot). Vue recompute otomatis saat salah satu berubah —
// tidak perlu manual set di watch.
const hasPendingChanges = computed(() => {
    if (!hasDataEdit.value) return false;
    return Object.keys(values).some(k => values[k] !== appliedValues[k])
        || Object.keys(appliedValues).some(k => !(k in values));
});

// ── CSRF token resolver: meta tag dulu, fallback ke cookie XSRF-TOKEN ──────────
// fetch() bawaan tidak otomatis kirim header CSRF (axios yang di-setup di bootstrap.js
// otomatis, tapi kita pakai fetch untuk kontrol penuh atas request). Helper ini
// memastikan kita SELALU mengirim token valid, atau melempar error eksplisit
// (bukan header kosong yang bikin Laravel kirim 419 Page Expired).
function getCsrfToken() {
    // 1) Meta tag — sumber utama, di-render oleh app.blade.php
    const meta = document.head.querySelector('meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;

    // 2) Cookie XSRF-TOKEN — Laravel auto-set, URL-safe Base64 encoded.
    //    Decode di sini karena Laravel VerifyCsrfToken expect nilai decoded
    //    di header X-XSRF-TOKEN (atau exact match di X-CSRF-TOKEN).
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    if (match) {
        try {
            return decodeURIComponent(match[1]);
        } catch (_) {
            // ignore — lanjut ke fallback berikutnya
        }
    }

    // 3) Tidak ada token di mana pun — sesi kemungkinan rusak.
    throw new Error('CSRF token tidak ditemukan. Silakan refresh halaman.');
}

// ── Live preview: update DOM di dalam iframe TANPA reload ──────
//
// Strategi: iframe di-load SEKALI dari /templates/{id}/preview/login.html.
// Saat user mengetik di panel kiri → update DOM element di dalam iframe via
// contentDocument.querySelector(...), BUKAN re-parse srcdoc.
//
// Keuntungan:
//   - Tidak ada flicker putih (iframe stabil, hanya update textContent/src/href)
//   - Tidak ada network request per keystroke
//   - Tidak ada browser HTML re-parse (untuk HTML 200KB sangat mahal)
//   - Real-time feedback instan
//
// Element types yang di-handle:
//   - data-edit="name"          → update textContent
//   - data-edit-image="name"    → update src (untuk <img>) atau style.backgroundImage
//   - data-edit-bg="name"       → update style.backgroundImage (untuk non-img)
//   - data-edit-link="name"     → update href (untuk <a>)

/**
 * Update satu field di DOM iframe.
 * Return true kalau ada element yang di-update, false kalau tidak ketemu.
 */
function applyFieldToIframe(name, value) {
    const doc = getIframeDoc();
    if (!doc) return false;

    let updated = false;

    // 1) data-edit="name" → textContent
    doc.querySelectorAll(`[data-edit="${cssEscape(name)}"]`).forEach((el) => {
        el.textContent = value != null ? String(value) : '';
        updated = true;
    });

    // 2) data-edit-image="name" → <img> src, atau background-image kalau non-img
    doc.querySelectorAll(`[data-edit-image="${cssEscape(name)}"]`).forEach((el) => {
        if (el.tagName === 'IMG') {
            el.setAttribute('src', value != null ? String(value) : '');
        } else {
            el.style.backgroundImage = value ? `url("${cssAttr(String(value))}")` : '';
        }
        updated = true;
    });

    // 3) data-edit-bg="name" → backgroundImage saja (selalu, regardless tag)
    doc.querySelectorAll(`[data-edit-bg="${cssEscape(name)}"]`).forEach((el) => {
        el.style.backgroundImage = value ? `url("${cssAttr(String(value))}")` : '';
        updated = true;
    });

    // 4) data-edit-link="name" → href
    doc.querySelectorAll(`[data-edit-link="${cssEscape(name)}"]`).forEach((el) => {
        el.setAttribute('href', value != null ? String(value) : '#');
        updated = true;
    });

    return updated;
}

/**
 * Apply semua values ke DOM iframe (initial load, reset, manual refresh).
 * Return true kalau minimal satu element berhasil di-update.
 */
function applyAllToIframe() {
    const doc = getIframeDoc();
    if (!doc) return false;
    let any = false;
    for (const name in values) {
        if (applyFieldToIframe(name, values[name])) any = true;
    }
    return any;
}

/**
 * Resolve contentDocument dari iframe ref, dengan fallback yang robust.
 * - Vue ref string syntax (iframeRef.value) → contentDocument
 * - Kalau null (sandbox block / cross-origin), coba window.frames[name]
 * - Kalau masih null, return null (caller harus early-exit)
 */
function getIframeDoc() {
    const el = iframeRef.value;
    if (!el) return null;
    try {
        const doc = el.contentDocument;
        if (doc) return doc;
    } catch (_) { /* security exception */ }
    return null;
}

/**
 * Escape string untuk aman dipakai di CSS attribute selector [attr="..."].
 * Pakai native CSS.escape() kalau ada, fallback ke simple escape.
 */
function cssEscape(s) {
    if (typeof CSS !== 'undefined' && CSS.escape) return CSS.escape(s);
    return String(s).replace(/["\\]/g, '\\$&');
}

/**
 * Escape string untuk aman di CSS url("...").
 * Hilangkan " dan \ yang bisa break string syntax.
 */
function cssAttr(s) {
    return String(s).replace(/["\\]/g, '\\$&');
}

// ── Fetch editable fields dari backend ──────────
onMounted(async () => {
    try {
        const r = await fetch(`/template/${props.template.id}/editor/fields`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        const data = await r.json();
        fields.value = data.fields || [];
        hasDataEdit.value = !!data.has_data_edit;
        for (const f of fields.value) {
            values[f.name] = f.default || '';
            defaultValues.value[f.name] = f.default || '';
        }
        // Init appliedValues = values (preview sama dengan form awal)
        for (const key in values) {
            appliedValues[key] = values[key];
        }
        // Set initial preview src — di-load oleh <iframe :src> SEKALI via @load.
        // TIDAK di-update lagi tiap keystroke. Cache buster agar draft baru
        // muncul setelah reset (lihat resetChanges yang increment previewKey).
        previewSrc.value = `/templates/${props.template.id}/preview/login.html?v=${Date.now()}`;
    } catch (e) {
        errorMsg.value = 'Gagal baca fields: ' + e.message;
    }
});

/**
 * Handler @load pada <iframe>: dipanggil SEKALI saat iframe selesai load.
 * Apply semua values ke DOM iframe. Setelah ini, watch(values) yang update
 * field individual via applyFieldToIframe().
 */
function onIframeLoad() {
    iframeReady.value = true;
    // Tunggu 1 frame supaya DOM siap (beberapa browser butuh ini)
    requestAnimationFrame(() => {
        const ok = applyAllToIframe();
        // Apply field yang antri dari watch (kalau user sudah ketik sebelum iframe load)
        if (pendingFields.size > 0) {
            for (const name of pendingFields) {
                applyFieldToIframe(name, values[name]);
            }
            pendingFields.clear();
        }
        // Debug: kalau tidak ada element yang ke-update, kemungkinan
        // data-edit attribute tidak ada di HTML, atau sandbox block akses.
        if (!ok && window.console && console.warn) {
            console.warn('[EditTemplate] iframe loaded but no data-edit* elements found. Cek apakah HTML master template punya atribut data-edit / data-edit-image / data-edit-link / data-edit-bg.');
        }
    });
}

// ── Reset perubahan ke nilai default ──────────
async function resetChanges() {
    if (resetting.value) return;
    if (!confirm('Reset semua perubahan ke nilai default? Tindakan ini tidak dapat dibatalkan.')) return;
    resetting.value = true;
    try {
        // Reset values → watch real-time akan auto-update DOM iframe
        for (const key in values) {
            values[key] = defaultValues.value[key] || '';
        }
        // Save ke server supaya draft konsisten dengan reset
        await save({ silent: true });
        lastSaved.value = null;
        lastSavedPath.value = null;
    } finally {
        resetting.value = false;
    }
}

// ── Real-time preview: update DOM di dalam iframe saat user mengetik ──────
//
// Strategi FINAL: iframe di-load sekali, watch(values) → apply field langsung
// ke DOM (textContent/src/href/backgroundImage). TIDAK ada re-parse HTML,
// TIDAK ada srcdoc update, TIDAK ada flicker putih.
//
// Debounce 50ms — coalesce multiple keystrokes per frame. Cukup cepat untuk
// terasa instant (well under 100ms perceived threshold), cukup lambat untuk
// tidak hit DOM API tiap karakter.
let previewUpdateTimer = null;
let pendingFields = new Set();
watch(values, (newVals, oldVals) => {
    if (!hasDataEdit.value) return;
    if (!iframeReady.value) {
        // Iframe belum siap — antre field yang berubah, apply saat load
        for (const name in newVals) {
            const oldVal = oldVals ? oldVals[name] : undefined;
            if (newVals[name] !== oldVal) pendingFields.add(name);
        }
        return;
    }

    // Deteksi field yang berubah (deep watch → diff old vs new)
    for (const name in newVals) {
        const oldVal = oldVals ? oldVals[name] : undefined;
        if (newVals[name] !== oldVal) {
            pendingFields.add(name);
        }
    }
    // Field yang dihapus dari newVals
    if (oldVals) {
        for (const name in oldVals) {
            if (!(name in newVals)) pendingFields.add(name);
        }
    }

    if (previewUpdateTimer) clearTimeout(previewUpdateTimer);
    previewUpdateTimer = setTimeout(() => {
        const doc = getIframeDoc();
        if (!doc) {
            // Sandbox block / cross-origin. Fallback: reload iframe dengan src baru
            // agar render fresh dari server (master + injected values via save).
            console.warn('[EditTemplate] contentDocument null saat apply values. Iframe akan di-reload dari server.');
            pendingFields.clear();
            previewUpdateTimer = null;
            return;
        }
        // Update hanya field yang berubah, bukan semua (efisien)
        for (const name of pendingFields) {
            applyFieldToIframe(name, values[name]);
        }
        pendingFields.clear();
        previewUpdateTimer = null;
    }, 50);
}, { deep: true });

// ── Auto-save ke server (debounced 1.5s) — supaya draft persisted ──────
// Beda dengan real-time render di atas: ini PERSIST ke server, bukan update
// UI. Trigger setelah user berhenti ngetik 1.5s. Cukup lama agar tidak spam
// server saat user masih aktif, tapi cukup cepat agar draft tidak hilang
// kalau browser tertutup. Tombol Simpan manual juga tersedia.
let autoSaveTimer = null;
watch(values, () => {
    if (!hasDataEdit.value) return;
    if (autoSaveTimer) clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        save({ silent: true });
    }, 1500);
}, { deep: true });

// (applyToPreview() dihapus — preview sekarang real-time via srcdoc,
//  tidak perlu POST ke server untuk update UI. Auto-save dilakukan
//  oleh watch debounced 1.5s yang memanggil save({silent:true}).)

// ── Download template hasil edit sebagai ZIP ──────────
// Strategi:
//   1) Backend cek payment. Kalau belum bayar → return 402 + JSON {redirect:'/checkout/{id}'}.
//   2) Kalau sudah bayar → return ZIP blob → trigger download via <a>.
//   3) Kalau dapat signal redirect (402) → navigate ke /checkout via Inertia (SPA).
//
// TIDAK pakai window.location.href (= navigasi browser) karena reload halaman
// dan reset semua state Vue (values, fields, appliedValues). Pakai Inertia
// supaya navigasi SPA, state editor TETAP HIDUP.
async function downloadZip() {
    if (downloading.value) return;
    downloading.value = true;
    errorMsg.value = null;
    try {
        const r = await fetch(`/template/${props.template.id}/download`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/octet-stream, application/zip, application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        // Backend return 402 Payment Required → user belum bayar
        if (r.status === 402) {
            const data = await r.json().catch(() => ({}));
            const redirectTo = data.redirect || route('checkout.show', { id: props.template.id });
            // Inertia visit (SPA navigation) — state editor tetap utuh
            router.visit(redirectTo);
            return;
        }

        if (!r.ok) throw new Error(`HTTP ${r.status}`);

        // Ambil filename dari Content-Disposition header.
        // Default: Template_ID{id}_{name}_edited.rar (include ID template yang sedang
        // diedit, suffix .rar — file tetap ZIP, WinRAR/7-Zip bisa extract).
        const disp = r.headers.get('Content-Disposition') || '';
        const match = disp.match(/filename="?([^"]+)"?/i);
        const fallbackName = `Template_ID${props.template.id}_${(props.template.name || 'template').replace(/[^A-Za-z0-9\-]/g, '_')}_edited.rar`;
        const filename = match ? match[1] : fallbackName;

        // Konversi response ke blob → klik <a> untuk download
        const blob = await r.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    } catch (e) {
        errorMsg.value = 'Gagal download: ' + e.message;
    } finally {
        setTimeout(() => { downloading.value = false; }, 500);
    }
}

// ── Image upload → base64 (MVP, simple) ──────────
function onImageUpload(e, name) {
    const file = e.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => { values[name] = ev.target.result; };
    reader.readAsDataURL(file);
}

// ── Live preview: TIDAK auto-refresh ──────────
// Preview hanya re-render saat user klik "Terapkan ke Preview" atau "Simpan".
// (Live watcher lama sudah dihapus untuk mencegah focus stealing & auto-refresh.)
// (Watch di atas yang set hasPendingChanges sudah cukup — dia tidak trigger preview render.)

// ── Save ────────────────────────────────
// PATCH: support {silent:true} untuk auto-save dari watch debounced.
// Kalau silent, error tidak munculkan toast merah — hanya console.warn.
// Klik manual tombol Simpan → silent:false (default) → error tampil di UI.
async function save(opts = {}) {
    if (saving.value) return;
    const silent = !!opts.silent;
    saving.value = true;
    if (!silent) errorMsg.value = null;
    try {
        let r;
        try {
            r = await fetch(`/template/${props.template.id}/editor/save`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ values }),
            });
        } catch (networkErr) {
            if (silent) console.warn('Auto-save gagal (network):', networkErr.message);
            else errorMsg.value = 'Tidak bisa terhubung ke server. Periksa koneksi Anda.';
            return;
        }

        if (r.status === 419) {
            if (!silent) errorMsg.value = 'Sesi telah berakhir. Menyegarkan halaman…';
            setTimeout(() => window.location.reload(), 800);
            return;
        }

        const data = await r.json();
        if (r.ok && data.ok) {
            lastSaved.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            lastSavedPath.value = data.path || null;
            // Update appliedValues supaya hasPendingChanges akurat
            for (const key in values) appliedValues[key] = values[key];

            // Auto-hide indicator setelah 4 detik — supaya tidak menumpuk &
            // ganggu UI kalau user auto-save berkali-kali (tiap 1.5s).
            // Pakai clearTimeout kalau save lagi dalam window 4 detik.
            if (lastSavedTimer) clearTimeout(lastSavedTimer);
            lastSavedTimer = setTimeout(() => {
                lastSaved.value = null;
                lastSavedPath.value = null;
            }, 4000);
        } else {
            if (silent) console.warn('Auto-save gagal:', data.error || `HTTP ${r.status}`);
            else errorMsg.value = data.error || `HTTP ${r.status}`;
        }
    } catch (e) {
        if (silent) console.warn('Auto-save exception:', e.message);
        else errorMsg.value = 'Gagal save: ' + e.message;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
<Head :title="`Edit ${template?.name || 'Template'} — MarketTemplate`" />

<div class="h-screen bg-slate-50 flex flex-col overflow-hidden" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; color: #0F172A;">

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

            <!-- Tengah: Toggle Desktop / Tablet / Mobile -->
            <div class="hidden md:flex items-center gap-1 bg-slate-100 rounded-lg p-1">
                <button @click="previewMode = 'desktop'" :class="previewMode === 'desktop' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-xs font-semibold transition-all" title="Preview Desktop">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span class="hidden lg:inline">Desktop</span>
                </button>
                <button @click="previewMode = 'tablet'" :class="previewMode === 'tablet' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-xs font-semibold transition-all" title="Preview Tablet">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span class="hidden lg:inline">Tablet</span>
                </button>
                <button @click="previewMode = 'mobile'" :class="previewMode === 'mobile' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-xs font-semibold transition-all" title="Preview Mobile">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span class="hidden lg:inline">Mobile</span>
                </button>
            </div>

            <!-- Kanan: Action buttons -->
            <div class="flex items-center gap-1.5 shrink-0">
                <a :href="`/templates/${template?.id || ''}/preview/login.html`" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors" title="Buka preview di tab baru">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span class="hidden md:inline">Live Preview</span>
                </a>
                <button @click="resetChanges" :disabled="resetting || !hasDataEdit" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" title="Reset semua perubahan ke nilai default">
                    <svg v-if="!resetting" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <svg v-else class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    <span class="hidden md:inline">Reset</span>
                </button>
                <button @click="downloadZip" :disabled="downloading" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors disabled:opacity-50" title="Download template sebagai ZIP">
                    <svg v-if="!downloading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <svg v-else class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    <span class="hidden md:inline">Download ZIP</span>
                </button>
                <!-- Tombol Terapkan dihapus: preview REAL-TIME via srcdoc, tidak perlu klik manual -->
                <button @click="save" :disabled="saving" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors disabled:opacity-50">
                    <svg v-if="!saving" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <svg v-else class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    {{ saving ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
        </div>
        <!-- Saved indicator — auto-hide setelah 4 detik (lihat save() di script) -->
        <div v-if="lastSaved" class="px-4 sm:px-6 py-1.5 bg-emerald-50 border-t border-emerald-100 text-[11px] text-emerald-700 font-medium flex items-center gap-1.5 transition-all duration-300">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
            <span>Tersimpan pukul {{ lastSaved }} di <code class="bg-emerald-100 px-1.5 py-0.5 rounded text-[10px]">{{ lastSavedPath }}</code></span>
        </div>
        <!-- Unsaved-changes indicator (preview belum di-update) -->
        <div v-else-if="hasPendingChanges" class="px-4 sm:px-6 py-1.5 bg-amber-50 border-t border-amber-100 text-[11px] text-amber-700 font-medium flex items-center gap-1.5">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Ada perubahan belum diterapkan ke preview. Klik <strong>"Terapkan ke Preview"</strong> untuk melihat hasil, atau <strong>"Simpan"</strong> untuk menyimpan.</span>
        </div>
    </header>

    <!-- ════════════ MAIN CONTENT (2 kolom) ════════════ -->
    <!-- min-h-0 → critical: izinkan flex item shrink ke 0 supaya child overflow-y-auto
         bisa kerja. Tanpa min-h-0, child akan overflow ke body dan trigger page scroll. -->
    <div class="flex-1 flex overflow-hidden min-h-0">

        <!-- ════ KIRI: SETTINGS PANEL (auto-generate dari data-edit) ════ -->
        <aside class="w-[280px] sm:w-[320px] shrink-0 bg-white border-r border-slate-200 flex flex-col">
            <div class="p-3 border-b border-slate-100">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2">Field Editable</p>
                <p class="text-[10px] text-slate-400 px-2 mt-1">
                    <span v-if="hasDataEdit">{{ fields.length }} field dari <code class="bg-slate-100 px-1 rounded">data-edit</code></span>
                    <span v-else>Template belum punya atribut <code class="bg-slate-100 px-1 rounded">data-edit</code></span>
                </p>
            </div>

            <!-- Dynamic form fields -->
            <div class="flex-1 overflow-y-auto p-5 space-y-4">
                <div v-if="!hasDataEdit" class="text-center py-12 px-4">
                    <div class="w-12 h-12 mx-auto mb-3 bg-slate-100 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-700 mb-1">Tidak ada field editable</p>
                    <p class="text-xs text-slate-500 leading-relaxed">Template ini belum menambahkan atribut <code class="bg-slate-100 px-1 py-0.5 rounded text-[10px]">data-edit</code> di HTML-nya. Tambahkan atribut <code class="bg-slate-100 px-1 py-0.5 rounded text-[10px]">data-edit</code>, <code class="bg-slate-100 px-1 py-0.5 rounded text-[10px]">data-edit-image</code>, atau <code class="bg-slate-100 px-1 py-0.5 rounded text-[10px]">data-edit-link</code> ke elemen HTML untuk mengaktifkannya.</p>
                </div>

                <div v-for="f in fields" :key="f.name" class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-700">
                        {{ f.label }}
                        <span class="text-[10px] font-mono text-slate-400 ml-1">{{ f.name }}</span>
                    </label>

                    <!-- Text -->
                    <textarea v-if="f.type === 'text' && f.tag === 'p'" v-model="values[f.name]" rows="3" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none resize-none"></textarea>
                    <input v-else-if="f.type === 'text'" v-model="values[f.name]" type="text" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none" />

                    <!-- Image -->
                    <div v-else-if="f.type === 'image'" class="space-y-2">
                        <div v-if="values[f.name]" class="border border-slate-200 rounded-lg p-2 bg-slate-50">
                            <img :src="values[f.name]" :alt="f.label" class="w-full h-24 object-contain rounded" />
                        </div>
                        <input :id="`img-${f.name}`" type="file" accept="image/*" @change="onImageUpload($event, f.name)" class="hidden" />
                        <label :for="`img-${f.name}`" class="block w-full text-center px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors">
                            {{ values[f.name] ? 'Ganti Gambar' : 'Upload Gambar' }}
                        </label>
                        <button v-if="values[f.name]" type="button" @click="values[f.name] = ''" class="w-full py-1.5 text-xs font-medium text-rose-600 bg-rose-50 rounded-lg hover:bg-rose-100">Hapus</button>
                    </div>

                    <!-- Link -->
                    <input v-else-if="f.type === 'link'" v-model="values[f.name]" type="url" placeholder="https://..." class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none font-mono" />
                </div>
            </div>
        </aside>

        <!-- ════ KANAN: LIVE PREVIEW (iframe ke template asli) ════ -->
        <!-- min-h-0 → izinkan flex item shrink supaya overflow-y-auto bekerja dengan benar -->
        <main class="flex-1 min-h-0 overflow-y-auto bg-slate-100 p-6 sm:p-8">
            <div class="max-w-5xl mx-auto">
                <!-- Error message -->
                <div v-if="errorMsg" class="mb-4 p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-sm text-rose-700 flex items-start gap-2.5">
                    <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ errorMsg }}</span>
                </div>

                <!-- Loading state -->
                <div v-if="!hasDataEdit && !errorMsg" class="text-center py-20">
                    <div class="inline-block w-8 h-8 border-2 border-slate-300 border-t-blue-500 rounded-full animate-spin"></div>
                    <p class="mt-3 text-sm text-slate-500">Memuat field editable...</p>
                </div>

                <!-- DESKTOP preview -->
                <div v-if="previewMode === 'desktop' && hasDataEdit" class="w-full">
                    <div class="bg-slate-900 rounded-t-xl px-4 py-2.5 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                        <div class="flex-1 mx-3 bg-slate-800 rounded-md px-3 py-1 text-[10px] text-slate-400 font-mono truncate flex items-center gap-2">
                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2V5a2 2 0 00-2-2H6a2 2 0 00-2 2v14a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            hotspot.{{ template?.name?.toLowerCase().replace(/\s+/g, '-') }}/login
                        </div>
                    </div>
                    <iframe :key="previewKey" ref="iframeRef" :src="previewSrc" @load="onIframeLoad" class="w-full bg-white border border-slate-200 border-t-0 rounded-b-xl shadow-xl" style="height: 70vh; min-height: 500px; pointer-events: none;" sandbox="allow-scripts allow-same-origin" tabindex="-1" inert></iframe>
                </div>

                <!-- MOBILE preview -->
                <div v-if="previewMode === 'mobile' && hasDataEdit" class="w-full flex justify-center">
                    <div class="bg-slate-900 rounded-[2.5rem] p-3 shadow-2xl" style="width: 360px;">
                        <div class="flex justify-center mb-2"><div class="w-24 h-5 bg-slate-800 rounded-full"></div></div>
                        <iframe :key="previewKey" ref="iframeRef" :src="previewSrc" @load="onIframeLoad" class="w-full bg-white rounded-[2rem] ring-4 ring-slate-800" style="height: 70vh; min-height: 600px; aspect-ratio: 9/16; pointer-events: none;" sandbox="allow-scripts allow-same-origin" tabindex="-1" inert></iframe>
                    </div>
                </div>

                <!-- TABLET preview -->
                <div v-if="previewMode === 'tablet' && hasDataEdit" class="w-full flex justify-center">
                    <div class="bg-slate-900 rounded-[1.75rem] p-3 shadow-2xl" style="width: 768px; max-width: 100%;">
                        <div class="flex justify-center mb-2"><div class="w-1.5 h-1.5 bg-slate-800 rounded-full"></div></div>
                        <iframe :key="previewKey" ref="iframeRef" :src="previewSrc" @load="onIframeLoad" class="w-full bg-white rounded-[1.25rem] ring-2 ring-slate-800" style="height: 70vh; min-height: 600px; pointer-events: none;" sandbox="allow-scripts allow-same-origin" tabindex="-1" inert></iframe>
                    </div>
                </div>

                <!-- Tip -->
                <p class="text-center text-xs text-slate-400 mt-6">
                    💡 Preview update real-time setiap ketukan tombol. Auto-save ke server setiap ~1.5 detik, atau klik <strong>Simpan</strong> untuk simpan manual.
                </p>
            </div>
        </main>
    </div>
</div>
</template>
