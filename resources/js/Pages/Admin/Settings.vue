<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
</script>
<template>
    <Head title="Pengaturan — Admin" />
    <AdminLayout>
        <template #title>Pengaturan</template>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Pengaturan Sistem</h3>
            <p class="text-sm text-slate-400">Konfigurasi situs di halaman ini.</p>
        </div>
    </AdminLayout>
</template>
