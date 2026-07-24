<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, reactive, watch, onMounted, onUnmounted, computed, nextTick } from 'vue';

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
const loadingFields = ref(true);   // status loading initial fetch fields
const lastSaved = ref(null);
const lastSavedPath = ref(null);
let lastSavedTimer = null;  // timer untuk auto-hide "Tersimpan pukul ..." indicator
const errorMsg = ref(null);
const previewKey = ref(0);     // increment untuk force re-render iframe (initial load, reset, manual refresh saja)
const previewSrc = ref('');    // URL iframe src — di-set SEKALI saat load, TIDAK berubah tiap keystroke
const iframeReady = ref(false); // Flag untuk mengetahui apakah iframe sudah selesai load

const previewMode = ref('desktop'); // 'desktop' | 'tablet' | 'mobile'
const activePage = ref('login');   // 'login' | 'status' | 'logout'

watch(activePage, (newPage) => {
    previewSrc.value = `/templates/${props.template.id}/preview/${newPage}.html?source=edited&v=${Date.now()}`;
    previewKey.value++; // force iframe reload
});
const appliedValues = reactive({}); // snapshot values yang sudah di-save ke server
const iframeRef = ref(null);   // ref ke <iframe> element — untuk contentDocument access
const defaults = ref({});      // default values dari master (untuk reset DOM)
const showSessionExpiredModal = ref(false); // Modal popup sesi habis
const showSuccessToast = ref(false);        // Popup toast berhasil menyimpan
let successToastTimer = null;

function reloadSession() {
    window.location.reload();
}

// ── History (Undo/Redo) ──────────────────
const history = ref([]);
const historyIndex = ref(-1);
let isUndoing = false;
let historyTimer = null;

function debouncedPushToHistory() {
    if (isUndoing) return;
    if (historyTimer) clearTimeout(historyTimer);
    historyTimer = setTimeout(() => {
        if (historyIndex.value < history.value.length - 1) {
            history.value = history.value.slice(0, historyIndex.value + 1);
        }
        const currentState = JSON.stringify(values);
        if (historyIndex.value >= 0 && history.value[historyIndex.value] === currentState) return;
        
        history.value.push(currentState);
        if (history.value.length > 50) history.value.shift();
        else historyIndex.value++;
    }, 400);
}

function undo() {
    if (historyIndex.value > 0) {
        isUndoing = true;
        historyIndex.value--;
        const state = JSON.parse(history.value[historyIndex.value]);
        for (const key in values) values[key] = state[key] !== undefined ? state[key] : '';
        nextTick(() => { postValuesToIframe(); isUndoing = false; });
    }
}

function redo() {
    if (historyIndex.value < history.value.length - 1) {
        isUndoing = true;
        historyIndex.value++;
        const state = JSON.parse(history.value[historyIndex.value]);
        for (const key in values) values[key] = state[key] !== undefined ? state[key] : '';
        nextTick(() => { postValuesToIframe(); isUndoing = false; });
    }
}

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
// Strategi FINAL: postMessage dari parent ke iframe. Iframe punya listener
// yang update DOM berdasarkan data yang dikirim. Reliable across sandbox
// (postMessage works even with sandbox).
//
// Element types yang di-handle:
//   - data-edit="name"          → update textContent
//   - data-edit-image="name"    → update src (untuk <img>) atau style.backgroundImage
//   - data-edit-bg="name"       → update style.backgroundImage (untuk non-img)
//   - data-edit-link="name"     → update href (untuk <a>)

/**
 * Kirim values ke iframe via postMessage. Iframe punya listener
 * (di-inject oleh route handler, lihat fallback di onIframeLoad) yang
 * apply values ke DOM.
 */
function postValuesToIframe() {
    const iframes = document.querySelectorAll('iframe.preview-iframe');
    for (const el of iframes) {
        // Direct DOM update (instant 0ms update pada iframe content)
        applyAllToIframeDirect(el);
        // PostMessage fallback untuk cross-origin/sandboxed frames
        try {
            if (el.contentWindow) {
                el.contentWindow.postMessage({
                    type: 'edit-template-values',
                    values: { ...values },
                }, '*');
            }
        } catch (e) {}
    }
}

/**
 * Fallback: update DOM langsung via contentDocument. Dipakai kalau
 * postMessage gagal (sandbox tanpa allow-scripts di iframe content,
 * atau browser block). Lebih reliable tapi lebih lambat.
 */
function applyAllToIframeDirect(iframeEl) {
    try {
        const doc = iframeEl.contentDocument;
        if (!doc) return;
        for (const name in values) {
            applyFieldToDoc(doc, name, values[name]);
        }
    } catch (e) { /* security exception */ }
}

function applyFieldToDoc(doc, name, value) {
    doc.querySelectorAll(`[data-edit="${cssEscape(name)}"]`).forEach((el) => {
        const valStr = value != null ? String(value) : '';
        const svgEl = el.querySelector('svg');
        const spanEl = el.querySelector('span');
        if (svgEl && spanEl) {
            if (/<[a-z][\s\S]*>/i.test(valStr)) {
                spanEl.innerHTML = valStr;
            } else {
                spanEl.textContent = valStr;
            }
        } else if (/<[a-z][\s\S]*>/i.test(valStr)) {
            el.innerHTML = valStr;
        } else {
            el.textContent = valStr;
        }
    });
    doc.querySelectorAll(`[data-edit-image="${cssEscape(name)}"]`).forEach((el) => {
        if (el.tagName === 'IMG') {
            el.setAttribute('src', value != null ? String(value) : '');
        } else {
            el.style.backgroundImage = value ? `url("${cssAttr(String(value))}")` : '';
        }
    });
    doc.querySelectorAll(`[data-edit-bg="${cssEscape(name)}"]`).forEach((el) => {
        el.style.backgroundImage = value ? `url("${cssAttr(String(value))}")` : '';
    });
    doc.querySelectorAll(`[data-edit-link="${cssEscape(name)}"], [data-edit-href="${cssEscape(name)}"]`).forEach((el) => {
        el.setAttribute('href', value != null ? String(value) : '#');
    });
    doc.querySelectorAll(`[data-edit-color="${cssEscape(name)}"]`).forEach((el) => {
        el.style.color = value != null ? String(value) : '';
    });
    doc.querySelectorAll(`[data-edit-bg-color="${cssEscape(name)}"]`).forEach((el) => {
        el.style.backgroundColor = value != null ? String(value) : '';
    });
    doc.querySelectorAll(`[data-edit-visible="${cssEscape(name)}"]`).forEach((el) => {
        const isVisible = value === true || value === 'true' || value === '1' || value === 1;
        el.style.display = isVisible ? '' : 'none';
    });
    doc.querySelectorAll(`[data-edit-width="${cssEscape(name)}"]`).forEach((el) => {
        el.style.width = value != null ? String(value) : '';
    });
    doc.querySelectorAll(`[data-edit-height="${cssEscape(name)}"]`).forEach((el) => {
        el.style.height = value != null ? String(value) : '';
    });
}

/**
 * Update satu field di DOM iframe.
 * Return true kalau ada element yang di-update, false kalau tidak ketemu.
 */
function applyFieldToIframe(name, value) {
    const doc = getIframeDoc();
    if (!doc) {
        if (window.console && console.debug) {
            console.debug('[EditTemplate] applyFieldToIframe: no doc', { name, value });
        }
        return false;
    }

    let updated = false;

    // 1) data-edit="name" → innerHTML (kalau ada tag HTML) atau textContent
    doc.querySelectorAll(`[data-edit="${cssEscape(name)}"]`).forEach((el) => {
        const valStr = value != null ? String(value) : '';
        if (/<[a-z][\s\S]*>/i.test(valStr)) {
            el.innerHTML = valStr;
        } else {
            el.textContent = valStr;
        }
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

    // 5) data-edit-color="name" → text color
    doc.querySelectorAll(`[data-edit-color="${cssEscape(name)}"]`).forEach((el) => {
        el.style.color = value != null ? String(value) : '';
        updated = true;
    });

    // 6) data-edit-bg-color="name" → background color
    doc.querySelectorAll(`[data-edit-bg-color="${cssEscape(name)}"]`).forEach((el) => {
        el.style.backgroundColor = value != null ? String(value) : '';
        updated = true;
    });

    // 7) data-edit-visible="name" → display none/block
    doc.querySelectorAll(`[data-edit-visible="${cssEscape(name)}"]`).forEach((el) => {
        const isVisible = value === true || value === 'true' || value === '1' || value === 1;
        el.style.display = isVisible ? '' : 'none';
        updated = true;
    });

    // 8) data-edit-width="name" → width style
    doc.querySelectorAll(`[data-edit-width="${cssEscape(name)}"]`).forEach((el) => {
        el.style.width = value != null ? String(value) : '';
        updated = true;
    });

    // 9) data-edit-height="name" → height style
    doc.querySelectorAll(`[data-edit-height="${cssEscape(name)}"]`).forEach((el) => {
        el.style.height = value != null ? String(value) : '';
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
 * Resolve contentDocument dari iframe yang sedang di-render.
 *
 * PENTING: ada 3 <iframe> di template (desktop/tablet/mobile, masing-masing
 * v-if), semuanya pake ref="iframeRef" yang sama. Vue 3 string ref di
 * multiple v-if element TIDAK reliable — bisa null atau menunjuk ke element
 * yang sudah di-unmount. Solusi: query DOM langsung dengan marker class
 * (lihat :class di template).
 */
function getIframeDoc() {
    // Cari iframe yang sedang visible (display !== 'none') dan di-load.
    const iframes = document.querySelectorAll('iframe.preview-iframe');
    let lastErr = null;
    for (const el of iframes) {
        // Skip iframe yang hidden (display: none) atau offsetParent null
        if (el.offsetParent === null && getComputedStyle(el).display !== 'none') continue;
        try {
            const doc = el.contentDocument;
            if (doc && doc.readyState === 'complete') {
                return doc;
            }
        } catch (e) {
            lastErr = e;
            /* security exception */
        }
    }
    // Debug: log kalau gagal resolve — supaya kita tahu root cause-nya
    if (window.console && console.debug && iframes.length > 0) {
        console.debug('[EditTemplate] getIframeDoc() returned null.', {
            iframeCount: iframes.length,
            firstSrc: iframes[0]?.src,
            firstReadyState: iframes[0]?.contentDocument?.readyState,
            lastError: lastErr?.message,
        });
    }
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

// ── Peringatan Unsaved Changes ──────────
function handleBeforeUnload(e) {
    if (hasPendingChanges.value) {
        e.preventDefault();
        e.returnValue = ''; // Browser standard
    }
}

// ── Fetch editable fields dari backend ──────────
onMounted(async () => {
    window.addEventListener('beforeunload', handleBeforeUnload);
    window.addEventListener('keydown', handleKeyDown);
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
            let def = f.default || '';
            if (f.type === 'boolean') def = (def === '1' || def === true || def === 'true');
            values[f.name] = def;
            defaultValues.value[f.name] = def;
        }
        // Init appliedValues = values (preview sama dengan form awal)
        for (const key in values) {
            appliedValues[key] = values[key];
        }
        
        // Push initial state to history
        history.value.push(JSON.stringify(values));
        historyIndex.value = 0;

        // Set initial preview src
        previewSrc.value = `/templates/${props.template.id}/preview/login.html?source=edited&v=${Date.now()}`;
    } catch (e) {
        errorMsg.value = 'Gagal baca fields: ' + e.message;
    } finally {
        loadingFields.value = false;
    }
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
    window.removeEventListener('keydown', handleKeyDown);
});

function handleKeyDown(e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
        e.preventDefault();
        undo();
    } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y') {
        e.preventDefault();
        redo();
    }
}

/**
 * Handler @load pada <iframe>: dipanggil SEKALI saat iframe selesai load.
 * Apply semua values ke DOM iframe. Setelah ini, watch(values) yang update
 * field individual via applyFieldToIframe().
 */
function onIframeLoad() {
    iframeReady.value = true;
    // Inject postMessage listener ke dalam iframe. Listener ini apply values
    // ke DOM saat parent kirim message. Reliable across sandbox karena
    // postMessage works even with sandbox='allow-scripts' (no allow-same-origin).
    const iframes = document.querySelectorAll('iframe.preview-iframe');
    for (const el of iframes) {
        try {
            const win = el.contentWindow;
            if (!win) continue;
            // Idempotent: hapus listener lama sebelum pasang baru (kalau iframe reload)
            if (win.__editTemplateListenerInstalled) continue;
            win.__editTemplateListenerInstalled = true;
            
            // Inject custom slim scrollbar styles
            const doc = el.contentDocument;
            if (doc && doc.head) {
                let style = doc.getElementById('editor-slim-scrollbar');
                if (!style) {
                    style = doc.createElement('style');
                    style.id = 'editor-slim-scrollbar';
                    style.textContent = `
                        /* Custom slim scrollbar for editor preview */
                        ::-webkit-scrollbar {
                            width: 6px !important;
                            height: 6px !important;
                        }
                        ::-webkit-scrollbar-track {
                            background: rgba(255, 255, 255, 0.02) !important;
                        }
                        ::-webkit-scrollbar-thumb {
                            background: rgba(148, 163, 184, 0.3) !important;
                            border-radius: 10px !important;
                        }
                        ::-webkit-scrollbar-thumb:hover {
                            background: rgba(148, 163, 184, 0.5) !important;
                        }
                        /* Firefox */
                        * {
                            scrollbar-width: thin !important;
                            scrollbar-color: rgba(148, 163, 184, 0.3) rgba(255, 255, 255, 0.02) !important;
                        }
                    `;
                    doc.head.appendChild(style);
                }
            }
            
            // Cegah klik link di dalam iframe agar tidak pindah halaman (merusak preview) tapi tetap bisa di-scroll
            win.addEventListener('click', (ev) => {
                ev.preventDefault();
            }, { capture: true });

            win.addEventListener('message', (ev) => {
                if (!ev.data || ev.data.type !== 'edit-template-values') return;
                const doc = el.contentDocument;
                if (!doc) return;
                for (const name in ev.data.values) {
                    applyFieldToDoc(doc, name, ev.data.values[name]);
                }
            });
            // Kirim values saat ini supaya preview langsung sync
            win.postMessage({
                type: 'edit-template-values',
                values: { ...values },
            }, '*');
        } catch (e) {
            if (window.console && console.warn) {
                console.warn('[EditTemplate] Gagal inject postMessage listener:', e.message);
            }
        }
    }
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

// ── Real-time preview: update iframe DOM secara instan pada setiap ketukan ──────
watch(values, () => {
    debouncedPushToHistory();
    postValuesToIframe();
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
//   1) Cek payment dulu via HEAD request (no body, no download).
//      Kalau 402 → user belum bayar, redirect ke checkout via Inertia.
//      Kalau 200/OK → lanjut download.
//   2) Kalau sudah bayar → trigger download via plain <a download>.
//      BUKAN fetch+blob — fetch() dari Inertia SPA auto-add X-Inertia
//      header, server return HTML page (bukan binary ZIP), r.blob()
//      terima HTML → corrupt ZIP saat user extract.
//   3) Pakai anchor native = browser handle download langsung via HTTP.
async function downloadZip() {
    if (downloading.value) return;
    downloading.value = true;
    errorMsg.value = null;
    try {
        // 1) Cek payment status dengan HEAD — biar bisa detect 402 tanpa download body
        const headRes = await fetch(`/template/${props.template.id}/download`, {
            method: 'HEAD',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (headRes.status === 402) {
            // User belum bayar — Inertia visit ke checkout, state editor TETAP HIDUP
            router.visit(route('checkout.show', { id: props.template.id }));
            return;
        }

        if (!headRes.ok && headRes.status !== 200) {
            throw new Error(`HTTP ${headRes.status}`);
        }

        // 2) Payment OK — trigger download via plain <a> (browser native, no Inertia).
        // Pada konteks EDITOR, user sudah edit template → download versi edit
        // (master + overlay login.html). Query ?source=edited.
        const safeName = (props.template.name || 'template').replace(/[^A-Za-z0-9\-]/g, '_');
        const filename = `Template_ID${props.template.id}_${safeName}_edited.zip`;

        const a = document.createElement('a');
        a.href = `/template/${props.template.id}/download?source=edited`;
        a.download = filename;
        a.rel = 'noopener';
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
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

        if (r.status === 401 || r.status === 419) {
            if (!silent) {
                showSessionExpiredModal.value = true;
            } else {
                console.warn('Auto-save skipped (session expired)');
            }
            return;
        }

        const contentType = r.headers.get('content-type');
        if (contentType && contentType.includes('text/html')) {
            if (!silent) {
                showSessionExpiredModal.value = true;
            } else {
                console.warn('Auto-save skipped (session HTML returned)');
            }
            return;
        }

        const data = await r.json();
        if (r.ok && data.ok) {
            lastSaved.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            lastSavedPath.value = data.path || null;
            // Update appliedValues supaya hasPendingChanges akurat
            for (const key in values) appliedValues[key] = values[key];

            // Tampilkan toast popup "Berhasil Menyimpan Perubahan" bila simpan manual
            if (!silent) {
                showSuccessToast.value = true;
                if (successToastTimer) clearTimeout(successToastTimer);
                successToastTimer = setTimeout(() => {
                    showSuccessToast.value = false;
                }, 3500);
            }

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

            <!-- Tengah: Toggle Viewport & Active Page Switcher -->
            <div class="hidden md:flex items-center gap-3">
                <!-- Toggle Desktop / Tablet / Mobile -->
                <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1">
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

                <!-- Toggle Halaman: login.html / status.html / logout.html -->
                <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1" v-if="hasDataEdit">
                    <button @click="activePage = 'login'" :class="activePage === 'login' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-2.5 py-1.5 rounded-md text-xs font-semibold transition-all" title="Pratinjau Halaman Login (login.html)">
                        Login
                    </button>
                    <button @click="activePage = 'status'" :class="activePage === 'status' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-2.5 py-1.5 rounded-md text-xs font-semibold transition-all" title="Pratinjau Halaman Status (status.html)">
                        Status
                    </button>
                    <button @click="activePage = 'logout'" :class="activePage === 'logout' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-2.5 py-1.5 rounded-md text-xs font-semibold transition-all" title="Pratinjau Halaman Logout (logout.html)">
                        Logout
                    </button>
                </div>
            </div>

            <!-- Kanan: Action buttons -->
            <div class="flex items-center gap-1.5 shrink-0">
                <!-- Undo / Redo -->
                <div class="hidden sm:flex items-center bg-white border border-slate-200 rounded-lg mr-1 overflow-hidden">
                    <button @click="undo" :disabled="historyIndex <= 0" class="px-2.5 py-2 text-slate-500 hover:text-slate-900 hover:bg-slate-50 transition-colors disabled:opacity-30 disabled:cursor-not-allowed border-r border-slate-100" title="Undo (Mundur)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    </button>
                    <button @click="redo" :disabled="historyIndex >= history.length - 1" class="px-2.5 py-2 text-slate-500 hover:text-slate-900 hover:bg-slate-50 transition-colors disabled:opacity-30 disabled:cursor-not-allowed" title="Redo (Maju)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6"/></svg>
                    </button>
                </div>
                
                <a :href="`/templates/${template?.id || ''}/preview/login.html?source=edited`" target="_blank" class="hidden md:inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors" title="Buka preview di tab baru">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span class="hidden lg:inline">Live Preview</span>
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
        <!-- Saved indicator (Floating Toast) -->
        <Transition enter-active-class="transition ease-out duration-300" enter-from-class="transform translate-y-10 opacity-0" enter-to-class="transform translate-y-0 opacity-100" leave-active-class="transition ease-in duration-200" leave-from-class="transform translate-y-0 opacity-100" leave-to-class="transform translate-y-10 opacity-0">
            <div v-if="lastSaved" class="fixed bottom-6 right-6 z-50 shadow-xl px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center shrink-0 text-emerald-600">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-emerald-800">Perubahan Tersimpan</p>
                    <p class="text-[10px] text-emerald-600 mt-0.5">Pukul {{ lastSaved }}</p>
                </div>
            </div>
        </Transition>
        
        <!-- Unsaved-changes indicator (Floating Toast) -->
        <Transition enter-active-class="transition ease-out duration-300" enter-from-class="transform translate-y-10 opacity-0" enter-to-class="transform translate-y-0 opacity-100" leave-active-class="transition ease-in duration-200" leave-from-class="transform translate-y-0 opacity-100" leave-to-class="transform translate-y-10 opacity-0">
            <div v-if="hasPendingChanges && !lastSaved" class="fixed bottom-6 right-6 z-40 shadow-xl px-4 py-3 bg-amber-50 border border-amber-200 rounded-2xl flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center shrink-0 text-amber-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-amber-800">Ada Perubahan Baru</p>
                    <p class="text-[10px] text-amber-600 mt-0.5">Menyimpan otomatis...</p>
                </div>
            </div>
        </Transition>
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

                    <!-- Color -->
                    <div v-else-if="f.type === 'color'" class="flex items-center gap-3">
                        <input type="color" v-model="values[f.name]" class="w-10 h-10 p-1 bg-white border border-slate-200 rounded-lg cursor-pointer shrink-0" />
                        <input type="text" v-model="values[f.name]" class="flex-1 px-3 py-2 text-sm font-mono border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none" />
                    </div>

                    <!-- Boolean / Toggle -->
                    <label v-else-if="f.type === 'boolean'" class="flex items-center cursor-pointer pt-1">
                        <div class="relative">
                            <input type="checkbox" v-model="values[f.name]" class="sr-only peer" />
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </div>
                        <span class="ml-3 text-sm font-medium text-slate-700">{{ values[f.name] ? 'Ditampilkan' : 'Disembunyikan' }}</span>
                    </label>

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
        <main class="flex-1 min-h-0 bg-slate-100 p-6 sm:p-8 flex flex-col overflow-y-auto">
            <div class="max-w-5xl mx-auto w-full flex-1 flex flex-col min-h-0">
                <!-- Error message -->
                <div v-if="errorMsg" class="mb-4 p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-sm text-rose-700 flex items-start gap-2.5">
                    <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ errorMsg }}</span>
                </div>

                <!-- Loading state -->
                <div v-if="loadingFields && !errorMsg" class="text-center py-20">
                    <div class="inline-block w-8 h-8 border-2 border-slate-300 border-t-blue-500 rounded-full animate-spin"></div>
                    <p class="mt-3 text-sm text-slate-500">Memuat field editable...</p>
                </div>

                <!-- DESKTOP preview -->
                <div v-if="previewMode === 'desktop' && !loadingFields" class="w-full flex-1 flex flex-col items-center justify-center">
                    <div class="mb-2.5 flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <span>Desktop Mode</span>
                        <span class="text-slate-400 font-normal">| 16:10 Aspect Ratio</span>
                    </div>
                    <div class="w-full flex-1 flex flex-col min-h-0 rounded-xl overflow-hidden shadow-2xl border border-slate-200 bg-white shrink-0" style="height: 70vh; max-height: 800px; aspect-ratio: 16 / 10; margin: 0 auto;">
                        <div class="bg-slate-900 px-4 py-2.5 flex items-center gap-1.5 shrink-0">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            <div class="flex-1 mx-3 bg-slate-800 rounded-md px-3 py-1 text-[10px] text-slate-400 font-mono truncate flex items-center gap-2">
                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2V5a2 2 0 00-2-2H6a2 2 0 00-2 2v14a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                hotspot.{{ template?.name?.toLowerCase().replace(/\s+/g, '-') }}/login
                            </div>
                        </div>
                        <iframe :key="previewKey" ref="iframeRef" :src="previewSrc" @load="onIframeLoad" class="preview-iframe w-full bg-white flex-1 min-h-0" style="border: none;" sandbox="allow-scripts allow-same-origin" tabindex="-1"></iframe>
                    </div>
                </div>

                <!-- MOBILE preview -->
                <div v-if="previewMode === 'mobile' && !loadingFields" class="w-full flex-1 flex flex-col items-center justify-center">
                    <div class="mb-2.5 flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <span>Mobile Portrait</span>
                        <span class="text-slate-400 font-normal">| 360 × 640 (aspect ratio 9:16)</span>
                    </div>
                    <div class="border-[6px] border-slate-900 rounded-[28px] overflow-hidden shadow-2xl bg-white shrink-0" style="height: 70vh; max-height: 640px; aspect-ratio: 9 / 16;">
                        <iframe :key="previewKey" ref="iframeRef" :src="previewSrc" @load="onIframeLoad" class="preview-iframe w-full h-full bg-white block" style="border: none;" sandbox="allow-scripts allow-same-origin" tabindex="-1"></iframe>
                    </div>
                </div>

                <!-- TABLET preview -->
                <div v-if="previewMode === 'tablet' && !loadingFields" class="w-full flex-1 flex flex-col items-center justify-center">
                    <div class="mb-2.5 flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <span>Tablet Portrait</span>
                        <span class="text-slate-400 font-normal">| 768 × 1024 (aspect ratio 3:4)</span>
                    </div>
                    <div class="border-[6px] border-slate-900 rounded-[24px] overflow-hidden shadow-2xl bg-white shrink-0" style="height: 70vh; max-height: 800px; aspect-ratio: 3 / 4;">
                        <iframe :key="previewKey" ref="iframeRef" :src="previewSrc" @load="onIframeLoad" class="preview-iframe w-full h-full bg-white block" style="border: none;" sandbox="allow-scripts allow-same-origin" tabindex="-1"></iframe>
                    </div>
                </div>

                <!-- Tip -->
                <p class="text-center text-xs text-slate-400 mt-6 shrink-0">
                    💡 Preview update real-time setiap ketukan tombol. Auto-save ke server setiap ~1.5 detik, atau klik <strong>Simpan</strong> untuk simpan manual.
                </p>
            </div>
        </main>
    </div>

    <!-- ═══════════ MODAL POPUP: SESI HABIS (CENTERED) ═══════════ -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showSessionExpiredModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl text-center border border-slate-100 relative">
                    <!-- Close Icon -->
                    <button @click="showSessionExpiredModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition-colors p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>

                    <!-- Icon Badge -->
                    <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-5 shadow-inner">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>

                    <!-- Title & Description -->
                    <h3 class="text-xl font-extrabold text-slate-900 mb-2">Sesi Login Telah Berakhir</h3>
                    <p class="text-sm text-slate-500 leading-relaxed mb-6">
                        Sesi login Anda telah habis demi keamanan. Silakan login kembali untuk menyimpan perubahan dan melanjutkan pengeditan template Anda.
                    </p>

                    <!-- Buttons -->
                    <div class="flex items-center gap-3">
                        <button @click="showSessionExpiredModal = false" class="flex-1 py-3 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                            Batal
                        </button>
                        <button @click="reloadSession" class="flex-1 py-3 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-200 transition-all">
                            Login Ulang
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- ═══════════ TOAST NOTIFIKASI: BERHASIL SIMPAN ═══════════ -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-4 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 -translate-y-4 scale-95"
        >
            <div v-if="showSuccessToast" class="fixed top-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 px-6 py-3.5 bg-slate-900 text-white rounded-2xl shadow-2xl border border-slate-700/60 font-semibold text-sm">
                <div class="w-6 h-6 rounded-full bg-emerald-500 text-slate-900 flex items-center justify-center shrink-0 font-bold">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span>Perubahan berhasil disimpan!</span>
                <button @click="showSuccessToast = false" class="ml-3 text-slate-400 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </Transition>
    </Teleport>
</div>
</template>
