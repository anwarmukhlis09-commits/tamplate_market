<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast.js';
import { formatUploadErrors } from '@/Utils/formatErrors.js';

const props = defineProps({ templates: Object, filters: Object });
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
function applyFilter() {
    const p = new URLSearchParams();
    if (search.value) p.set('search', search.value);
    if (statusFilter.value) p.set('status', statusFilter.value);
    window.location.href = '/admin/templates?' + p.toString();
}
function formatPrice(p) { return 'Rp ' + p.toLocaleString('id-ID'); }
function confirmDelete(id) {
    if (!confirm('Yakin hapus template ini?')) return;
    useForm({}).delete('/admin/templates/' + id, {
        onError: (errors) => {
            useToast().error('Hapus Gagal', formatUploadErrors(errors));
        },
    });
}
</script>

<template>
    <Head title="Kelola Template — Admin" />
    <AdminLayout>
        <template #title>Kelola Template</template>

        <div class="flex flex-col sm:flex-row gap-3 mb-6">
            <input v-model="search" @keyup.enter="applyFilter" type="text" placeholder="Cari template..." class="flex-1 px-3.5 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
            <select v-model="statusFilter" @change="applyFilter" class="px-3.5 py-2 text-sm border border-slate-200 rounded-xl">
                <option value="">Semua Status</option><option value="published">Published</option><option value="draft">Draft</option>
            </select>
            <button @click="applyFilter" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700">Filter</button>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 text-xs uppercase">Template</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 text-xs uppercase hidden sm:table-cell">Kategori</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 text-xs uppercase">Harga</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 text-xs uppercase">Status</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 text-xs uppercase hidden lg:table-cell">Terjual</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-500 text-xs uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="tpl in templates.data" :key="tpl.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3"><div class="font-semibold text-slate-900">{{ tpl.name }}</div><div class="text-xs text-slate-400 truncate max-w-[200px]">{{ tpl.short_desc }}</div></td>
                            <td class="px-4 py-3 hidden sm:table-cell"><span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md capitalize">{{ tpl.category }}</span></td>
                            <td class="px-4 py-3"><span class="font-semibold text-slate-900">{{ formatPrice(tpl.price) }}</span></td>
                            <td class="px-4 py-3"><span class="text-xs font-semibold px-2 py-0.5 rounded-full" :class="tpl.status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">{{ tpl.status === 'published' ? 'Published' : 'Draft' }}</span></td>
                            <td class="px-4 py-3 hidden lg:table-cell text-slate-600">{{ tpl.sold_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <Link :href="'/admin/templates/' + tpl.id + '/edit'" class="px-2.5 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">Edit</Link>
                                    <Link :href="'/admin/templates/' + tpl.id + '/toggle'" method="patch" as="button" class="px-2.5 py-1.5 text-xs font-medium rounded-lg transition-colors" :class="tpl.status === 'published' ? 'text-amber-600 bg-amber-50 hover:bg-amber-100' : 'text-emerald-600 bg-emerald-50 hover:bg-emerald-100'">{{ tpl.status === 'published' ? 'Unpublish' : 'Publish' }}</Link>
                                    <button @click="confirmDelete(tpl.id)" class="px-2.5 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="templates.data.length === 0"><td colspan="6" class="px-4 py-16 text-center text-sm text-slate-400">Belum ada template. <Link href="/admin/templates/create" class="text-indigo-600 font-medium">Tambah →</Link></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="templates.last_page > 1" class="flex items-center justify-center gap-1.5 mt-6">
            <Link v-for="p in templates.links" :key="p.label" :href="p.url || '#'" v-html="p.label" class="w-8 h-8 text-sm font-medium rounded-lg flex items-center justify-center transition-colors" :class="p.active ? 'bg-indigo-600 text-white' : p.url ? 'text-slate-600 hover:bg-white border border-slate-200' : 'text-slate-300 cursor-default'" />
        </div>
    </AdminLayout>
</template>
