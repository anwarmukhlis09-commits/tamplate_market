<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast.js';

const toast = useToast();

const settings = ref({
    siteName: 'MarketTemplate',
    siteTagline: 'Marketplace Template Hotspot MikroTik #1 di Indonesia',
    contactEmail: 'support@markettemplate.id',
    contactWhatsapp: '0812-3456-7890',
    currency: 'IDR',
    taxRate: 11,
    maintenanceMode: false,
    allowRegistration: true,
    freeTrialDays: 7,
    defaultTemplatePrice: 49000,
});

const integrations = ref({
    midtransServerKey: 'Mid-server-***************',
    midtransClientKey: 'Mid-client-***************',
    midtransProduction: false,
    smtpHost: 'smtp.mailtrap.io',
    smtpPort: 587,
    smtpUser: 'admin@markettemplate.id',
});

function saveSettings() {
    toast.success('Pengaturan Disimpan', 'Perubahan telah berhasil disimpan.');
}
function testConnection(type) {
    toast.info('Testing ' + type, 'Memeriksa koneksi ke server ' + type + '...');
    setTimeout(() => {
        toast.success('Koneksi Berhasil', 'Server ' + type + ' terhubung dengan baik.');
    }, 1500);
}
</script>

<template>
    <Head title="Pengaturan — Admin" />
    <AdminLayout>
        <template #title>Pengaturan Sistem</template>

        <div class="space-y-5">
            <!-- General settings -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-900">Umum</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Konfigurasi dasar situs</p>
                </div>
                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Situs</label>
                            <input v-model="settings.siteName" type="text" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Currency</label>
                            <select v-model="settings.currency" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                                <option>IDR</option>
                                <option>USD</option>
                                <option>MYR</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tagline</label>
                        <input v-model="settings.siteTagline" type="text" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Support</label>
                            <input v-model="settings.contactEmail" type="email" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">WhatsApp</label>
                            <input v-model="settings.contactWhatsapp" type="text" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-900">Harga & Pajak</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Pengaturan harga default dan pajak</p>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Harga Default Template (Rp)</label>
                        <input v-model.number="settings.defaultTemplatePrice" type="number" min="0" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pajak (%)</label>
                        <input v-model.number="settings.taxRate" type="number" min="0" max="100" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                    </div>
                </div>
            </div>

            <!-- Access -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-900">Akses & Pendaftaran</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Pengaturan akses publik dan mode maintenance</p>
                </div>
                <div class="p-5 space-y-4">
                    <label class="flex items-center justify-between cursor-pointer p-3 rounded-xl hover:bg-slate-50 transition-colors">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Izinkan Pendaftaran User Baru</p>
                            <p class="text-xs text-slate-500">User baru bisa membuat akun di halaman register</p>
                        </div>
                        <input v-model="settings.allowRegistration" type="checkbox" class="w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                    </label>
                    <label class="flex items-center justify-between cursor-pointer p-3 rounded-xl hover:bg-rose-50 transition-colors">
                        <div>
                            <p class="text-sm font-semibold text-rose-700">Mode Maintenance</p>
                            <p class="text-xs text-rose-500">Situs tidak bisa diakses user biasa saat aktif</p>
                        </div>
                        <input v-model="settings.maintenanceMode" type="checkbox" class="w-5 h-5 rounded border-slate-300 text-rose-600 focus:ring-rose-500 cursor-pointer">
                    </label>
                </div>
            </div>

            <!-- Integrations -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-900">Integrasi Payment & Email</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Midtrans payment gateway & SMTP email</p>
                </div>
                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Midtrans Server Key</label>
                            <input v-model="integrations.midtransServerKey" type="text" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Midtrans Client Key</label>
                            <input v-model="integrations.midtransClientKey" type="text" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none font-mono">
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input v-model="integrations.midtransProduction" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        <span class="text-slate-700">Mode Produksi (uncheck untuk sandbox)</span>
                    </label>
                    <div class="flex gap-2">
                        <button @click="testConnection('Midtrans')" class="px-3 py-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">Test Koneksi Midtrans</button>
                        <button @click="testConnection('SMTP')" class="px-3 py-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">Test Koneksi SMTP</button>
                    </div>
                </div>
            </div>

            <!-- Save -->
            <div class="flex justify-end gap-3 pt-2">
                <button class="px-5 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">Batal</button>
                <button @click="saveSettings" class="px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-sm transition-colors">Simpan Pengaturan</button>
            </div>
        </div>
    </AdminLayout>
</template>
