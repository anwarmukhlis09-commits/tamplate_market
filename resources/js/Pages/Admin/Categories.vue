<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const sampleCategories = ref([
    { id: 1, name: 'Minimalis', slug: 'minimalis', icon: 'M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z', color: 'from-slate-400 to-slate-500', count: 8 },
    { id: 2, name: 'Modern', slug: 'modern', icon: 'M13 10V3L4 14h7v7l9-11h-7z', color: 'from-indigo-500 to-violet-500', count: 12 },
    { id: 3, name: 'Gaming', slug: 'gaming', icon: 'M11 17a1 1 0 102 0v-1a1 1 0 10-2 0v1zM5 17a1 1 0 102 0v-1a1 1 0 10-2 0v1zm5-15a7 7 0 00-7 7c0 2 .5 3.5 1.5 5L3 17h14l-1.5-3c1-1.5 1.5-3 1.5-5a7 7 0 00-7-7z', color: 'from-fuchsia-500 to-pink-500', count: 4 },
    { id: 4, name: 'Hotel', slug: 'hotel', icon: 'M3 21h18M3 7v14M21 7v14M6 21V11h12v10M9 7V3h6v4', color: 'from-amber-400 to-orange-500', count: 6 },
    { id: 5, name: 'Cafe & Restaurant', slug: 'cafe', icon: 'M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z', color: 'from-rose-400 to-pink-500', count: 5 },
    { id: 6, name: 'Sekolah', slug: 'sekolah', icon: 'M12 14l9-5-9-5-9 5 9 5z', color: 'from-cyan-400 to-blue-500', count: 3 },
    { id: 7, name: 'Voucher', slug: 'voucher', icon: 'M2 9V7a2 2 0 012-2h16a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4z', color: 'from-emerald-400 to-teal-500', count: 7 },
    { id: 8, name: 'ISP', slug: 'isp', icon: 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M2 8.82a15 15 0 0120 0M5 12.859a10 10 0 0114 0M8.5 16.429a5 5 0 017 0', color: 'from-blue-500 to-indigo-600', count: 2 },
]);

const allCategories = computed(() => props.categories?.length ? props.categories : sampleCategories.value);

const newCategory = ref({ name: '', slug: '', color: 'from-slate-400 to-slate-500' });
const showAddForm = ref(false);

function addCategory() {
    if (!newCategory.value.name.trim()) return;
    const slug = newCategory.value.slug || newCategory.value.name.toLowerCase().replace(/\s+/g, '-');
    sampleCategories.value.push({
        id: Date.now(),
        name: newCategory.value.name,
        slug,
        icon: 'M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z',
        color: newCategory.value.color,
        count: 0,
    });
    newCategory.value = { name: '', slug: '', color: 'from-slate-400 to-slate-500' };
    showAddForm.value = false;
}
</script>

<template>
    <Head title="Kategori — Admin" />
    <AdminLayout>
        <template #title>Manajemen Kategori</template>

        <!-- Add button -->
        <div class="flex justify-end mb-5">
            <button @click="showAddForm = !showAddForm" class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah Kategori
            </button>
        </div>

        <!-- Add form -->
        <div v-if="showAddForm" class="bg-white rounded-2xl border border-indigo-200 p-5 shadow-sm mb-6">
            <h3 class="text-sm font-bold text-slate-900 mb-3">Kategori Baru</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                <input v-model="newCategory.name" type="text" placeholder="Nama kategori" class="px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                <input v-model="newCategory.slug" type="text" placeholder="Slug (opsional)" class="px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                <select v-model="newCategory.color" class="px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                    <option value="from-slate-400 to-slate-500">Slate</option>
                    <option value="from-indigo-500 to-violet-500">Indigo</option>
                    <option value="from-fuchsia-500 to-pink-500">Pink</option>
                    <option value="from-amber-400 to-orange-500">Amber</option>
                    <option value="from-rose-400 to-pink-500">Rose</option>
                    <option value="from-cyan-400 to-blue-500">Cyan</option>
                    <option value="from-emerald-400 to-teal-500">Emerald</option>
                    <option value="from-blue-500 to-indigo-600">Blue</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button @click="addCategory" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">Simpan</button>
                <button @click="showAddForm = false" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Batal</button>
            </div>
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div v-for="cat in allCategories" :key="cat.id" class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md hover:border-indigo-200 transition-all group">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center text-white" :class="cat.color">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="cat.icon"/></svg>
                    </div>
                    <button class="w-8 h-8 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 flex items-center justify-center transition-colors" title="Hapus">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                    </button>
                </div>
                <h3 class="font-bold text-slate-900 mb-1">{{ cat.name }}</h3>
                <p class="text-xs text-slate-400 font-mono mb-3">/{{ cat.slug }}</p>
                <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                    <span class="text-xs text-slate-500">{{ cat.count }} template</span>
                    <span class="text-xs font-semibold text-indigo-600 group-hover:text-indigo-700">Edit →</span>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
