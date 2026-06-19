<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

defineProps({
    canResetPassword: { type: Boolean, default: false },
    status: { type: String, default: null },
});

// ── Capture URL yang sedang dibuka SEBELUM redirect ke /login ──────
// Tujuan: setelah login sukses, kembali ke page itu, BUKAN ke /dashboard.
// Sumber prioritas (aman, internal-only):
//   1) Query param `?redirect=/path` (kalau ada link eksplisit)
//   2) `?intended=/path` (Laravel intended URL dari middleware Authenticate)
//   3) `window.location.pathname` saat ini = path SEBELUM user sampai di /login
//      (Inertia + Laravel simpan previous URL via referrer/back navigation)
//
// Validasi: hanya path internal (dimulai dengan `/`, BUKAN `//` yg = external)
const safeRedirect = computed(() => {
    const params = new URLSearchParams(window.location.search);
    const candidate = params.get('redirect') || params.get('intended') || '/';
    // Cegah open redirect: hanya path internal absolut
    if (typeof candidate !== 'string' || !candidate.startsWith('/') || candidate.startsWith('//')) {
        return '/';
    }
    return candidate;
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
    redirect: safeRedirect.value, // <-- dikirim ke controller
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
<Head title="Login — MarketTemplate" />

<div class="min-h-screen bg-gradient-to-br from-[#0F172A] via-[#312E81] to-[#6D28D9] flex items-center justify-center p-6">

    <div class="w-full max-w-6xl bg-white/95 backdrop-blur-xl rounded-[32px] shadow-2xl p-5 grid grid-cols-1 md:grid-cols-2 gap-5">

        <!-- ═══ LEFT: FORM ═══ -->
        <div class="flex flex-col justify-center px-8 py-12 md:px-20">
            <div class="text-center mb-10">
                <div class="mx-auto mb-4 w-14 h-14 rounded-2xl bg-gradient-to-br from-[#5B4CF0] to-[#8B5CF6] flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-indigo-500/30">
                    MT
                </div>
                <h1 class="text-3xl font-bold text-slate-900 mb-3">Selamat Datang Kembali!</h1>
                <p class="text-sm text-slate-500">Masuk untuk mengelola template hotspot Anda</p>
            </div>

            <div v-if="status" class="mb-4 text-sm font-medium text-emerald-600 bg-emerald-50 border border-emerald-200 rounded-xl p-3.5">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Masukkan email Anda"
                        class="w-full rounded-xl border border-slate-200 bg-white px-5 py-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                    />
                    <p v-if="form.errors.email" class="mt-2 text-sm text-rose-600">{{ form.errors.email }}</p>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                    <div class="relative">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan password Anda"
                            class="w-full rounded-xl border border-slate-200 bg-white pl-5 pr-12 py-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                            class="absolute inset-y-0 right-0 z-10 flex items-center justify-center w-12 text-slate-400 hover:text-indigo-600 transition-colors rounded-r-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                        >
                            <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="mt-2 text-sm text-rose-600">{{ form.errors.password }}</p>
                </div>

                <!-- Remember + Forgot -->
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input v-model="form.remember" type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                        <span class="text-slate-600">Ingat saya</span>
                    </label>
                    <Link v-if="canResetPassword" :href="route('password.request')" class="font-medium text-indigo-600 hover:text-indigo-800 cursor-pointer">Lupa password?</Link>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-indigo-600 py-4 text-white font-semibold shadow-lg shadow-indigo-500/30 hover:bg-indigo-700 transition disabled:opacity-60 disabled:cursor-not-allowed inline-flex items-center justify-center gap-2"
                >
                    <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    {{ form.processing ? 'Memproses…' : 'Masuk' }}
                </button>
            </form>

            <p class="mt-8 text-center text-sm text-slate-500">
                Belum punya akun?
                <Link :href="route('register')" class="inline-block font-semibold text-indigo-600 hover:text-indigo-800 underline underline-offset-2 cursor-pointer select-none py-1">Daftar sekarang</Link>
            </p>
        </div>

        <!-- ═══ RIGHT: PROMO PANEL ═══ -->
        <div class="hidden md:block relative rounded-[26px] overflow-hidden min-h-[600px] bg-cover bg-center" style="background-image: url('/images/register-illustration.jpg');">
            <div class="absolute inset-0 bg-gradient-to-b from-black/5 via-transparent to-black/40"></div>
            <div class="absolute bottom-10 left-10 right-10 text-white">
                <p class="text-3xl font-bold leading-tight">Template Login Hotspot Profesional</p>
                <p class="text-sm opacity-90 mt-3">Siap pakai untuk MikroTik, Hotel, Cafe, ISP dan Voucher WiFi.</p>
            </div>
        </div>

    </div>
</div>
</template>
