<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
});

const search = ref('');

// Sample data
const sampleUsers = ref([
    { id: 1, name: 'Admin', email: 'admin@markettemplate.id', isAdmin: true, templates: 0, joined: '2026-06-01' },
    { id: 2, name: 'Ahmad Fauzi', email: 'ahmad.fauzi@gmail.com', isAdmin: false, templates: 3, joined: '2026-06-02' },
    { id: 3, name: 'Dian Permata', email: 'dian.permata@gmail.com', isAdmin: false, templates: 0, joined: '2026-06-03' },
    { id: 4, name: 'Rudi Hartono', email: 'rudi.hartono@gmail.com', isAdmin: false, templates: 2, joined: '2026-06-03' },
    { id: 5, name: 'Siti Nurhaliza', email: 'siti.nh@gmail.com', isAdmin: false, templates: 0, joined: '2026-06-04' },
    { id: 6, name: 'Budi Santoso', email: 'budi.santoso@gmail.com', isAdmin: false, templates: 0, joined: '2026-06-05' },
    { id: 7, name: 'Studio Mikro', email: 'studio@mikro.id', isAdmin: false, templates: 24, joined: '2026-05-15' },
    { id: 8, name: 'Nanda Pixel', email: 'nanda@pixel.id', isAdmin: false, templates: 18, joined: '2026-05-20' },
]);

const allUsers = computed(() => props.users?.length ? props.users : sampleUsers.value);

const filteredUsers = computed(() => {
    if (!search.value) return allUsers.value;
    const q = search.value.toLowerCase();
    return allUsers.value.filter(u =>
        u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
    );
});

const totalUsers = computed(() => allUsers.value.length);
const totalAdmins = computed(() => allUsers.value.filter(u => u.isAdmin).length);
const totalCreators = computed(() => allUsers.value.filter(u => u.templates > 0).length);
</script>

<template>
    <Head title="User — Admin" />
    <AdminLayout>
        <template #title>Manajemen User</template>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500 mb-1">Total User</p>
                <p class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ totalUsers }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500 mb-1">Admin</p>
                <p class="text-2xl font-extrabold text-indigo-600 tracking-tight">{{ totalAdmins }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500 mb-1">Creator</p>
                <p class="text-2xl font-extrabold text-emerald-600 tracking-tight">{{ totalCreators }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm mb-6">
            <div class="relative">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input v-model="search" type="search" placeholder="Cari nama atau email..."
                    class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50/50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">User</th>
                            <th class="text-left px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider hidden sm:table-cell">Email</th>
                            <th class="text-left px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Role</th>
                            <th class="text-left px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider hidden md:table-cell">Template</th>
                            <th class="text-left px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider hidden lg:table-cell">Bergabung</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in filteredUsers" :key="u.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br flex items-center justify-center text-white text-xs font-bold shrink-0"
                                        :class="u.isAdmin ? 'from-indigo-500 to-violet-500' : 'from-slate-400 to-slate-500'">
                                        {{ u.name.charAt(0) }}
                                    </div>
                                    <span class="font-semibold text-slate-900">{{ u.name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600 hidden sm:table-cell">{{ u.email }}</td>
                            <td class="px-5 py-3.5">
                                <span v-if="u.isAdmin" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold bg-indigo-100 text-indigo-700 rounded-full">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    Admin
                                </span>
                                <span v-else-if="u.templates > 0" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold bg-emerald-100 text-emerald-700 rounded-full">Creator</span>
                                <span v-else class="inline-flex items-center px-2.5 py-1 text-xs font-medium bg-slate-100 text-slate-600 rounded-full">User</span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-700 font-semibold hidden md:table-cell">{{ u.templates }}</td>
                            <td class="px-5 py-3.5 text-slate-500 text-xs hidden lg:table-cell">{{ u.joined }}</td>
                        </tr>
                        <tr v-if="filteredUsers.length === 0">
                            <td colspan="5" class="px-5 py-12 text-center text-slate-400 text-sm">User tidak ditemukan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
