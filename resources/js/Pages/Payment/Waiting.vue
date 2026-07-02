<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    orderId: { type: String, required: true },
    amount: { type: Number, default: 0 },
    paymentMethod: { type: String, default: '' },
    expiredAt: { type: String, default: null },
    tripayReference: { type: String, default: null },
    tripayCheckoutUrl: { type: String, default: null },
    tripayPayCode: { type: String, default: null },
    tripayQrString: { type: String, default: null },
    debugEnabled: { type: Boolean, default: false },
    template: { type: Object, default: () => ({ id: null, name: 'Template' }) },
});

// ═══ State ═══
const state = ref('waiting'); // 'waiting' | 'paid' | 'expired' | 'failed'
const copyState = ref('idle'); // 'idle' | 'copied'
const secondsLeft = ref(null);
const polling = ref(true);
const simulating = ref(null); // 'PAID' | 'FAILED' | null

// ═══ Helpers ═══
function formatRupiah(n) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n);
}

function formatMethod(code) {
    if (!code) return '';
    // Singkatan seperti "BCAVA" → "BCA Virtual Account" (kasar, cukup jelas)
    const map = {
        MANDIRIVA: 'Mandiri Virtual Account',
        BCAVA: 'BCA Virtual Account',
        ALFAMART: 'Alfamart',
        INDOMARET: 'Indomaret',
        ALFAMIDI: 'Alfamidi',
        QRIS2: 'QRIS',
        DANA: 'DANA',
    };
    return map[code] || code;
}

const countdown = computed(() => {
    if (secondsLeft.value === null) return '--:--:--';
    if (secondsLeft.value <= 0) return '00:00:00';
    const h = Math.floor(secondsLeft.value / 3600);
    const m = Math.floor((secondsLeft.value % 3600) / 60);
    const s = secondsLeft.value % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

const isUrgent = computed(() => secondsLeft.value !== null && secondsLeft.value < 600); // < 10 menit

// ═══ Countdown timer ═══
let countdownTimer = null;
function updateCountdown() {
    if (!props.expiredAt) {
        secondsLeft.value = null;
        return;
    }
    const expiry = new Date(props.expiredAt).getTime();
    const now = Date.now();
    secondsLeft.value = Math.floor((expiry - now) / 1000);
    if (secondsLeft.value <= 0) {
        countdownTimer && clearInterval(countdownTimer);
        countdownTimer = null;
        if (state.value === 'waiting') {
            state.value = 'expired';
            // Auto-redirect ke failed page setelah 2 detik
            setTimeout(() => {
                router.visit(route('payment.failed', { order: props.orderId }));
            }, 2000);
        }
    }
}

// ═══ Polling status ═══
let pollTimer = null;
async function pollStatus() {
    if (!polling.value) return;
    try {
        const r = await fetch(route('payment.status', { order: props.orderId }), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!r.ok) return;
        const j = await r.json();
        if (j.status === 'completed') {
            state.value = 'paid';
            polling.value = false;
            clearInterval(pollTimer);
            // Beri user lihat animasi "Pembayaran diterima" sebentar lalu redirect
            setTimeout(() => {
                router.visit(route('payment.success', { order: props.orderId }));
            }, 1500);
        } else if (['expired', 'failed'].includes(j.status) || j.is_expired) {
            state.value = j.status;
            polling.value = false;
            clearInterval(pollTimer);
            setTimeout(() => {
                router.visit(route('payment.failed', { order: props.orderId }));
            }, 1000);
        }
    } catch (e) {
        // Silent — next tick akan retry
    }
}

// ═══ Copy to clipboard ═══
async function copyValue(text) {
    try {
        await navigator.clipboard.writeText(text);
        copyState.value = 'copied';
        setTimeout(() => { copyState.value = 'idle'; }, 2000);
    } catch (e) {
        // Fallback untuk browser lama
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (_) {}
        document.body.removeChild(ta);
        copyState.value = 'copied';
        setTimeout(() => { copyState.value = 'idle'; }, 2000);
    }
}

// ═══ Simulasi callback (debug only) ═══
function simulate(status) {
    simulating.value = status;
    router.post(
        route('payment.simulate-callback', { order: props.orderId }),
        { status },
        {
            preserveScroll: true,
            onFinish: () => {
                simulating.value = null;
                // Trigger poll manual supaya UI update cepat
                setTimeout(pollStatus, 300);
            },
        }
    );
}

// ═══ Lifecycle ═══
onMounted(() => {
    updateCountdown();
    countdownTimer = setInterval(updateCountdown, 1000);
    pollStatus();
    pollTimer = setInterval(pollStatus, 3000);
});

onUnmounted(() => {
    countdownTimer && clearInterval(countdownTimer);
    pollTimer && clearInterval(pollTimer);
});
</script>

<template>
<Head :title="`Menunggu Pembayaran — ${orderId}`" />

<div class="min-h-screen bg-gradient-to-br from-[#0F172A] via-[#312E81] to-[#6D28D9] flex items-center justify-center p-6" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 text-center">

        <!-- ═══ Status icon dengan ping animation ═══ -->
        <div class="relative w-20 h-20 mx-auto mb-5">
            <div v-if="state === 'waiting'" class="absolute inset-0 bg-indigo-200 rounded-full animate-ping opacity-50"></div>
            <div v-else-if="state === 'paid'" class="absolute inset-0 bg-emerald-200 rounded-full animate-ping opacity-50"></div>
            <div class="relative w-full h-full rounded-full flex items-center justify-center shadow-xl transition-colors" :class="{
                'bg-gradient-to-br from-indigo-400 to-violet-500 shadow-indigo-200': state === 'waiting',
                'bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-emerald-200': state === 'paid',
                'bg-gradient-to-br from-rose-400 to-rose-600 shadow-rose-200': state === 'expired' || state === 'failed',
            }">
                <svg v-if="state === 'waiting'" class="w-10 h-10 text-white animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                <svg v-else-if="state === 'paid'" class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                <svg v-else class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <!-- ═══ Title (dynamic) ═══ -->
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-1">
            <span v-if="state === 'waiting'">Menunggu Pembayaran</span>
            <span v-else-if="state === 'paid'">Pembayaran Diterima!</span>
            <span v-else-if="state === 'expired'">Waktu Habis</span>
            <span v-else>Pembayaran Gagal</span>
        </h1>
        <p class="text-sm text-slate-500 mb-6">
            <span v-if="state === 'waiting'">Selesaikan pembayaran Anda di Tripay</span>
            <span v-else-if="state === 'paid'">Mengarahkan ke halaman unduh...</span>
            <span v-else-if="state === 'expired'">Order Anda telah kadaluarsa</span>
            <span v-else>Mengarahkan ke halaman gagal...</span>
        </p>

        <!-- ═══ Info card ═══ -->
        <div class="bg-slate-50 rounded-2xl p-4 mb-4 text-left space-y-2 text-sm">
            <div class="flex justify-between gap-3">
                <span class="text-slate-500 shrink-0">Order ID</span>
                <span class="font-mono text-xs font-semibold text-slate-800 truncate">{{ orderId }}</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="text-slate-500 shrink-0">Template</span>
                <span class="font-semibold text-slate-800 truncate">{{ template?.name }}</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="text-slate-500 shrink-0">Metode</span>
                <span class="font-semibold text-slate-800 truncate">{{ formatMethod(paymentMethod) }}</span>
            </div>
            <div class="flex justify-between gap-3 pt-2 border-t border-slate-200">
                <span class="text-slate-500 shrink-0">Total</span>
                <span class="font-extrabold text-indigo-600">{{ formatRupiah(amount) }}</span>
            </div>
            <div v-if="state === 'waiting' && secondsLeft !== null" class="flex justify-between gap-3 pt-2 border-t border-slate-200">
                <span class="text-slate-500 shrink-0">Sisa waktu</span>
                <span class="font-mono font-bold tabular-nums" :class="isUrgent ? 'text-rose-600' : 'text-slate-800'">
                    {{ countdown }}
                </span>
            </div>
        </div>

        <!-- ═══ VA number / QR info box ═══ -->
        <div v-if="tripayPayCode && state === 'waiting'" class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 mb-4 text-left">
            <p class="text-xs font-bold text-indigo-700 uppercase tracking-wider mb-2">Nomor Virtual Account</p>
            <div class="flex items-center gap-2">
                <input
                    readonly
                    :value="tripayPayCode"
                    class="flex-1 px-3 py-2.5 bg-white border border-indigo-200 rounded-lg font-mono text-base font-bold text-slate-800 focus:outline-none"
                />
                <button @click="copyValue(tripayPayCode)" class="px-3 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-colors flex items-center gap-1.5">
                    <svg v-if="copyState === 'copied'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    {{ copyState === 'copied' ? 'Tersalin' : 'Salin' }}
                </button>
            </div>
            <p class="text-xs text-indigo-600 mt-2">Bayar melalui {{ formatMethod(paymentMethod) }} sebelum waktu habis.</p>
        </div>

        <!-- ═══ QRIS QR code box ═══ -->
        <div v-if="tripayQrString && !tripayPayCode && state === 'waiting'" class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 mb-4 text-left">
            <p class="text-xs font-bold text-indigo-700 uppercase tracking-wider mb-2">QRIS Code</p>
            <div class="flex items-start gap-2">
                <textarea readonly :value="tripayQrString" rows="3" class="flex-1 px-3 py-2 bg-white border border-indigo-200 rounded-lg font-mono text-xs text-slate-800 focus:outline-none resize-none"></textarea>
                <button @click="copyValue(tripayQrString)" class="px-3 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-colors flex items-center gap-1.5">
                    {{ copyState === 'copied' ? 'Tersalin' : 'Salin' }}
                </button>
            </div>
        </div>

        <!-- ═══ Action buttons ═══ -->
        <div class="space-y-2.5">
            <!-- Open Tripay -->
            <a v-if="tripayCheckoutUrl && state === 'waiting'" :href="tripayCheckoutUrl" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 w-full py-3.5 text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 rounded-xl shadow-lg shadow-indigo-200 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Buka Halaman Tripay
            </a>

            <!-- Debug: simulasi callback -->
            <div v-if="debugEnabled && state === 'waiting'" class="bg-amber-50 border border-amber-200 rounded-xl p-3 mt-2">
                <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-2">🛠️ Debug Mode (APP_DEBUG=true)</p>
                <div class="grid grid-cols-2 gap-2">
                    <button @click="simulate('PAID')" :disabled="simulating" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition-colors disabled:opacity-50">
                        {{ simulating === 'PAID' ? 'Memproses...' : 'Simulate PAID' }}
                    </button>
                    <button @click="simulate('FAILED')" :disabled="simulating" class="px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg transition-colors disabled:opacity-50">
                        {{ simulating === 'FAILED' ? 'Memproses...' : 'Simulate FAILED' }}
                    </button>
                </div>
            </div>

            <!-- Back to template -->
            <Link v-if="template?.id" :href="`/template/${template.id}`" class="flex items-center justify-center gap-1.5 w-full py-2.5 text-xs font-medium text-slate-500 hover:text-slate-700 transition-colors mt-2">
                ← Kembali ke detail template
            </Link>
        </div>
    </div>
</div>
</template>