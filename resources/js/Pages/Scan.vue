<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const cameraPermission = ref('prompt'); // 'prompt' | 'granted' | 'denied' | 'error'
const scannerError = ref(null);
const scannedCode = ref(null);
const isScanning = ref(false);
let html5QrCode = null;

function getQueryParam(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

const returnUrl = ref(getQueryParam('return_url') || null);

onMounted(() => {
    // Load script locally from our server assets to avoid CDN block on hotspot walled-garden
    const script = document.createElement('script');
    script.src = '/js/html5-qrcode.min.js';
    script.async = true;
    script.onload = () => {
        initializeScanner();
    };
    script.onerror = () => {
        scannerError.value = 'Gagal memuat library pemindai QR Code lokal. Hubungi administrator.';
    };
    document.head.appendChild(script);
});

onUnmounted(() => {
    if (html5QrCode && html5QrCode.isScanning) {
        html5QrCode.stop().catch(err => console.error(err));
    }
});

function initializeScanner() {
    if (typeof Html5Qrcode === 'undefined') {
        scannerError.value = 'Library QR Code tidak terdefinisi.';
        return;
    }

    try {
        html5QrCode = new Html5Qrcode("qr-reader");
        isScanning.value = true;
        
        const config = { 
            fps: 15, 
            qrbox: (width, height) => {
                const size = Math.min(width, height) * 0.7;
                return { width: size, height: size };
            }
        };

        html5QrCode.start(
            { facingMode: "environment" }, 
            config, 
            (decodedText) => {
                // Success callback
                scannedCode.value = decodedText;
                isScanning.value = false;
                
                // Play notification sound
                try {
                    const audio = new Audio('data:audio/wav;base64,UklGRigAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQQAAAAAAA==');
                    audio.play();
                } catch(e) {}

                // Stop scanning and redirect
                html5QrCode.stop().then(() => {
                    if (returnUrl.value) {
                        const baseUrl = returnUrl.value;
                        const separator = baseUrl.includes('?') ? '&' : '?';
                        window.location.href = baseUrl + separator + 'voucher=' + encodeURIComponent(decodedText);
                    } else {
                        alert('Scan Sukses!\nKode Voucher: ' + decodedText);
                    }
                }).catch(err => {
                    console.error('Gagal menghentikan scanner:', err);
                    // Fallback redirect
                    if (returnUrl.value) {
                        const baseUrl = returnUrl.value;
                        const separator = baseUrl.includes('?') ? '&' : '?';
                        window.location.href = baseUrl + separator + 'voucher=' + encodeURIComponent(decodedText);
                    }
                });
            },
            (errorMessage) => {
                // Verbose scanning logs - skip
            }
        ).then(() => {
            cameraPermission.value = 'granted';
        }).catch((err) => {
            console.error('Gagal memulai scanner:', err);
            cameraPermission.value = 'denied';
            scannerError.value = 'Akses kamera ditolak atau perangkat tidak mendukung kamera.';
            isScanning.value = false;
        });

    } catch (e) {
        console.error(e);
        scannerError.value = 'Terjadi kesalahan sistem saat memuat kamera: ' + e.message;
        isScanning.value = false;
    }
}

function handleManualCancel() {
    if (returnUrl.value) {
        window.location.href = returnUrl.value;
    } else {
        window.location.href = '/';
    }
}
</script>

<style scoped>
.laser-line {
    animation: scanner-laser 2s infinite linear;
}

@keyframes scanner-laser {
    0% { top: 10%; }
    50% { top: 90%; }
    100% { top: 10%; }
}
</style>

<template>
    <Head title="Scan QR Code Voucher — Hotspot Portal" />

    <div class="min-h-screen bg-slate-950 flex flex-col items-center justify-center p-4 relative overflow-hidden font-sans text-white">
        <!-- Background glows -->
        <div class="absolute w-[500px] h-[500px] rounded-full bg-indigo-500/10 blur-[120px] -top-40 -left-40 pointer-events-none"></div>
        <div class="absolute w-[500px] h-[500px] rounded-full bg-purple-500/10 blur-[120px] -bottom-40 -right-40 pointer-events-none"></div>

        <div class="w-full max-w-md bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-3xl p-6 shadow-2xl z-10 flex flex-col">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center mx-auto mb-3 shadow-lg shadow-indigo-500/20">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                </div>
                <h1 class="text-xl font-black tracking-tight text-white">QR VOUCHER SCANNER</h1>
                <p class="text-slate-400 text-xs mt-1">Arahkan kamera ke kode QR voucher hotspot Anda</p>
            </div>

            <!-- Scanner Box -->
            <div class="relative w-full aspect-square bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-inner flex items-center justify-center">
                <!-- Video Element target for Html5Qrcode -->
                <div id="qr-reader" class="w-full h-full object-cover"></div>

                <!-- Overlay scanner bounds -->
                <div v-if="isScanning" class="absolute inset-0 border-2 border-indigo-500/30 rounded-2xl pointer-events-none">
                    <!-- Scanner Corner Markers -->
                    <div class="absolute top-4 left-4 w-6 h-6 border-t-4 border-l-4 border-indigo-500 rounded-tl-md"></div>
                    <div class="absolute top-4 right-4 w-6 h-6 border-t-4 border-r-4 border-indigo-500 rounded-tr-md"></div>
                    <div class="absolute bottom-4 left-4 w-6 h-6 border-b-4 border-l-4 border-indigo-500 rounded-bl-md"></div>
                    <div class="absolute bottom-4 right-4 w-6 h-6 border-b-4 border-r-4 border-indigo-500 rounded-br-md"></div>

                    <!-- Scanning Laser line effect -->
                    <div class="laser-line absolute left-4 right-4 h-0.5 bg-indigo-500 shadow-[0_0_10px_#6366f1]"></div>
                </div>

                <!-- Camera states / Loading / Error overlays -->
                <div v-if="cameraPermission === 'prompt' && !scannerError" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950 text-slate-400 p-6 text-center gap-3">
                    <div class="w-8 h-8 rounded-full border-4 border-slate-800 border-t-indigo-500 animate-spin"></div>
                    <span class="text-sm font-medium">Memulai kamera...</span>
                </div>

                <div v-if="cameraPermission === 'denied' || scannerError" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/90 text-center p-6 gap-4">
                    <div class="w-12 h-12 rounded-full bg-rose-950/50 text-rose-500 flex items-center justify-center border border-rose-800/40">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-bold text-sm text-slate-200">Gagal Mengakses Kamera</h3>
                        <p class="text-xs text-slate-400 max-w-xs leading-relaxed">{{ scannerError || 'Pastikan izin kamera diaktifkan dan situs diakses menggunakan koneksi HTTPS.' }}</p>
                    </div>
                </div>

                <div v-if="scannedCode" class="absolute inset-0 flex flex-col items-center justify-center bg-indigo-950/90 text-center p-6 gap-3">
                    <div class="w-12 h-12 rounded-full bg-emerald-500 text-white flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-100">Scan Berhasil!</h3>
                        <p class="text-xs text-indigo-300 mt-1 font-mono break-all bg-indigo-900/50 px-3 py-1.5 rounded-lg border border-indigo-800/40">{{ scannedCode }}</p>
                    </div>
                </div>
            </div>

            <!-- Footer / Control Buttons -->
            <div class="mt-6 flex flex-col gap-3">
                <button type="button" @click="handleManualCancel" class="w-full py-3 px-4 rounded-xl text-sm font-semibold border border-slate-800 bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white transition-all shadow-sm">
                    Kembali Ke Halaman Login
                </button>
            </div>
        </div>
        
        <p class="text-[10px] text-slate-600 mt-6 select-none">Powered by Template Hotspot secure SSL redirection.</p>
    </div>
</template>
