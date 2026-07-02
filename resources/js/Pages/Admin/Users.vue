<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast.js';

const props = defineProps({
    users: { type: Object, required: true },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const { success: toastSuccess, error: toastError } = useToast();
const currentUserId = computed(() => usePage().props.auth?.user?.id);

// ═══ State ═══
const search = ref(props.filters?.search || '');
const roleFilter = ref(props.filters?.role || 'all');
const statusFilter = ref(props.filters?.status || 'all');

const editingUser = ref(null);
const resetPasswordUser = ref(null);
const deleteModal = ref({ open: false, user: null });
const templatesModal = ref({ open: false, user: null, templates: [] });
const deleting = ref(false);
const loadingTemplates = ref(false);

let searchDebounceTimer = null;

// ═══ Computed ═══
const userList = computed(() => props.users?.data || []);
const pagination = computed(() => ({
    current: props.users?.current_page || 1,
    last: props.users?.last_page || 1,
    total: props.users?.total || 0,
    from: props.users?.from || 0,
    to: props.users?.to || 0,
}));

// Role helper: admin (is_admin), creator (ada paid_orders), user (lainnya)
function roleOf(u) {
    if (u.is_admin) return 'admin';
    if ((u.paid_orders_count || 0) > 0) return 'creator';
    return 'user';
}

// Formatters
function formatDateTime(iso) {
    if (!iso) return '-';
    return new Date(iso).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });
}
function formatDate(iso) {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString('id-ID', { dateStyle: 'medium' });
}
function initial(name) {
    return (name || '?').charAt(0).toUpperCase();
}

// ═══ Filter submit ═══
function applyFilters() {
    router.reload({
        only: ['users'],
        data: {
            search: search.value || undefined,
            role: roleFilter.value !== 'all' ? roleFilter.value : undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        },
        preserveState: true,
    });
}

watch(search, () => {
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(applyFilters, 300);
});

function goToPage(page) {
    router.reload({
        only: ['users'],
        data: {
            search: search.value || undefined,
            role: roleFilter.value !== 'all' ? roleFilter.value : undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
            page,
        },
        preserveState: true,
    });
}

// ═══ Edit modal ═══
const editForm = useForm({ name: '', email: '' });
function openEdit(u) {
    editingUser.value = u;
    editForm.name = u.name;
    editForm.email = u.email;
    editForm.clearErrors();
}
function closeEdit() {
    editingUser.value = null;
    editForm.reset();
    editForm.clearErrors();
}
function submitEdit() {
    editForm.patch(route('admin.users.update', { user: editingUser.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            closeEdit();
            toastSuccess('Berhasil', `User '${editForm.name}' berhasil diperbarui.`);
        },
        onError: () => {
            // Errors tampil via form.errors
        },
    });
}

// ═══ Reset password modal ═══
const resetForm = useForm({ new_password: '', new_password_confirmation: '' });
function openResetPassword(u) {
    resetPasswordUser.value = u;
    resetForm.reset();
    resetForm.clearErrors();
}
function closeResetPassword() {
    resetPasswordUser.value = null;
    resetForm.reset();
    resetForm.clearErrors();
}
function submitResetPassword() {
    resetForm.post(route('admin.users.reset-password', { user: resetPasswordUser.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            closeResetPassword();
            toastSuccess('Berhasil', `Password user '${resetPasswordUser.value?.name}' berhasil direset.`);
        },
    });
}

// ═══ Toggle active ═══
function toggleActive(u) {
    router.patch(route('admin.users.toggle-active', { user: u.id }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toastSuccess('Berhasil', u.is_disabled
                ? `Akun '${u.name}' berhasil diaktifkan kembali.`
                : `Akun '${u.name}' berhasil dinonaktifkan.`);
        },
        onError: (errs) => {
            const msg = errs?.error || 'Gagal mengubah status akun.';
            toastError('Gagal', msg);
        },
    });
}

// ═══ Delete modal ═══
function openDelete(u) {
    deleteModal.value = { open: true, user: u };
}
function closeDelete() {
    deleteModal.value = { open: false, user: null };
}
function confirmDelete() {
    if (!deleteModal.value.user) return;
    deleting.value = true;
    const u = deleteModal.value.user;
    router.delete(route('admin.users.destroy', { user: u.id }), {
        preserveScroll: true,
        onSuccess: () => {
            closeDelete();
            toastSuccess('Berhasil', `Akun '${u.name}' berhasil dihapus.`);
        },
        onError: (errs) => {
            toastError('Gagal', errs?.error || 'Gagal menghapus user.');
        },
        onFinish: () => {
            deleting.value = false;
        },
    });
}

// ═══ View templates modal ═══
async function openTemplates(u) {
    templatesModal.value = { open: true, user: u, templates: [] };
    loadingTemplates.value = true;
    try {
        // Pakai getPaidTemplateIdsAttribute — bisa juga endpoint dedicated,
        // tapi untuk MVP hitung dari orders user yang completed.
        // Asumsi backend menyediakan field 'paid_templates' array of {id,name,slug}
        // kalau tidak, fallback ke count saja.
        if (u.paid_templates && Array.isArray(u.paid_templates) && u.paid_templates.length > 0) {
            templatesModal.value.templates = u.paid_templates;
        } else {
            // Fallback: tampilkan info "user belum pernah order"
            templatesModal.value.templates = [];
        }
    } finally {
        loadingTemplates.value = false;
    }
}
function closeTemplates() {
    templatesModal.value = { open: false, user: null, templates: [] };
}

// ═══ ESC key handler ═══
function onKeydown(e) {
    if (e.key !== 'Escape') return;
    if (editingUser.value) closeEdit();
    else if (resetPasswordUser.value) closeResetPassword();
    else if (deleteModal.value.open) closeDelete();
    else if (templatesModal.value.open) closeTemplates();
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
});
</script>

<template>
    <Head title="User — Admin" />
    <AdminLayout>
        <template #title>Manajemen User</template>

        <!-- Stats (5 cards) -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500 mb-1">Total User</p>
                <p class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ stats.total_users || 0 }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500 mb-1">Aktif</p>
                <p class="text-2xl font-extrabold text-emerald-600 tracking-tight">{{ stats.active_users || 0 }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500 mb-1">Dinonaktifkan</p>
                <p class="text-2xl font-extrabold text-rose-600 tracking-tight">{{ stats.disabled_users || 0 }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500 mb-1">Admin Aktif</p>
                <p class="text-2xl font-extrabold text-indigo-600 tracking-tight">{{ stats.admins_count || 0 }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-medium text-slate-500 mb-1">Creator</p>
                <p class="text-2xl font-extrabold text-amber-600 tracking-tight">{{ stats.creators_count || 0 }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm mb-6">
            <div class="flex flex-col md:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari nama atau email..."
                        class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none"
                    >
                </div>
                <select
                    v-model="roleFilter"
                    @change="applyFilters"
                    class="text-sm border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none cursor-pointer"
                >
                    <option value="all">Semua Role</option>
                    <option value="admin">Admin</option>
                    <option value="creator">Creator</option>
                    <option value="user">User</option>
                </select>
                <select
                    v-model="statusFilter"
                    @change="applyFilters"
                    class="text-sm border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none cursor-pointer"
                >
                    <option value="all">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="disabled">Dinonaktifkan</option>
                </select>
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
                            <th class="text-left px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Status</th>
                            <th class="text-left px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider hidden md:table-cell">Order</th>
                            <th class="text-left px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider hidden lg:table-cell">Bergabung</th>
                            <th class="text-right px-5 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in userList" :key="u.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br flex items-center justify-center text-white text-xs font-bold shrink-0"
                                        :class="u.is_admin ? 'from-indigo-500 to-violet-500' : 'from-slate-400 to-slate-500'">
                                        {{ initial(u.name) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ u.name }}</p>
                                        <p v-if="u.id === currentUserId" class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider">Anda</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600 hidden sm:table-cell">{{ u.email }}</td>
                            <td class="px-5 py-3.5">
                                <span v-if="u.is_admin" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold bg-indigo-100 text-indigo-700 rounded-full">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    Admin
                                </span>
                                <span v-else-if="(u.paid_orders_count || 0) > 0" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold bg-emerald-100 text-emerald-700 rounded-full">Creator</span>
                                <span v-else class="inline-flex items-center px-2.5 py-1 text-xs font-medium bg-slate-100 text-slate-600 rounded-full">User</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span v-if="u.is_disabled" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 rounded-full">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/></svg>
                                    Dinonaktifkan
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Aktif
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-700 font-semibold hidden md:table-cell">
                                <span class="text-emerald-600">{{ u.paid_orders_count || 0 }}</span>
                                <span class="text-xs text-slate-400">/ {{ u.orders_count || 0 }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-500 text-xs hidden lg:table-cell">{{ formatDateTime(u.created_at) }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- Lihat Template (selalu tampil) -->
                                    <button
                                        @click="openTemplates(u)"
                                        class="p-1.5 hover:bg-slate-100 rounded-lg transition-colors text-slate-500 hover:text-indigo-600"
                                        title="Lihat template dimiliki"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                    </button>
                                    <!-- Tombol lainnya HANYA untuk user lain (bukan diri sendiri) -->
                                    <template v-if="u.id !== currentUserId">
                                        <button @click="openEdit(u)" class="p-1.5 hover:bg-slate-100 rounded-lg transition-colors text-slate-500 hover:text-indigo-600" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button @click="openResetPassword(u)" class="p-1.5 hover:bg-slate-100 rounded-lg transition-colors text-slate-500 hover:text-amber-600" title="Reset password">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                        </button>
                                        <button @click="toggleActive(u)" class="p-1.5 hover:bg-slate-100 rounded-lg transition-colors" :class="u.is_disabled ? 'text-emerald-500 hover:text-emerald-700' : 'text-slate-500 hover:text-rose-600'" :title="u.is_disabled ? 'Aktifkan kembali' : 'Nonaktifkan'">
                                            <svg v-if="u.is_disabled" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        </button>
                                        <button @click="openDelete(u)" class="p-1.5 hover:bg-rose-50 rounded-lg transition-colors text-slate-500 hover:text-rose-600" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="userList.length === 0">
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400 text-sm">User tidak ditemukan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.last > 1" class="flex flex-col sm:flex-row items-center justify-between gap-3 px-5 py-4 border-t border-slate-200 bg-slate-50/50">
                <p class="text-xs text-slate-500">
                    Menampilkan <span class="font-semibold text-slate-700">{{ pagination.from }}</span> –
                    <span class="font-semibold text-slate-700">{{ pagination.to }}</span> dari
                    <span class="font-semibold text-slate-700">{{ pagination.total }}</span> user
                </p>
                <div class="flex items-center gap-1.5">
                    <button @click="goToPage(pagination.current - 1)" :disabled="pagination.current <= 1" class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">← Prev</button>
                    <span class="px-3 py-1.5 text-xs font-semibold text-slate-700">{{ pagination.current }} / {{ pagination.last }}</span>
                    <button @click="goToPage(pagination.current + 1)" :disabled="pagination.current >= pagination.last" class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">Next →</button>
                </div>
            </div>
        </div>

        <!-- ═══ Modal Edit ═══ -->
        <Teleport to="body">
            <div v-if="editingUser" @click.self="closeEdit" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h2 class="text-lg font-extrabold text-slate-900">Edit User</h2>
                        <button @click="closeEdit" class="p-1.5 hover:bg-slate-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitEdit" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama</label>
                            <input v-model="editForm.name" type="text" required class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" :class="editForm.errors.name ? 'border-rose-400' : ''">
                            <p v-if="editForm.errors.name" class="text-xs text-rose-500 mt-1">{{ editForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email</label>
                            <input v-model="editForm.email" type="email" required class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" :class="editForm.errors.email ? 'border-rose-400' : ''">
                            <p v-if="editForm.errors.email" class="text-xs text-rose-500 mt-1">{{ editForm.errors.email }}</p>
                        </div>
                        <div v-if="editForm.errors.error" class="text-sm text-rose-600 bg-rose-50 px-3 py-2 rounded-lg">{{ editForm.errors.error }}</div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="closeEdit" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">Batal</button>
                            <button type="submit" :disabled="editForm.processing" class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                                <span v-if="editForm.processing">Menyimpan...</span>
                                <span v-else>Simpan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ═══ Modal Reset Password ═══ -->
            <div v-if="resetPasswordUser" @click.self="closeResetPassword" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-extrabold text-slate-900">Reset Password</h2>
                            <p class="text-xs text-slate-500">{{ resetPasswordUser.name }} ({{ resetPasswordUser.email }})</p>
                        </div>
                        <button @click="closeResetPassword" class="p-1.5 hover:bg-slate-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitResetPassword" class="p-6 space-y-4">
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-700">
                            <p class="font-bold mb-1">⚠️ Perhatian</p>
                            <p>User harus login ulang dengan password baru. Existing "remember me" cookies akan invalid.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Password Baru (min. 8 karakter)</label>
                            <input v-model="resetForm.new_password" type="password" required minlength="8" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" :class="resetForm.errors.new_password ? 'border-rose-400' : ''">
                            <p v-if="resetForm.errors.new_password" class="text-xs text-rose-500 mt-1">{{ resetForm.errors.new_password }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Konfirmasi Password</label>
                            <input v-model="resetForm.new_password_confirmation" type="password" required minlength="8" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                        </div>
                        <div v-if="resetForm.errors.error" class="text-sm text-rose-600 bg-rose-50 px-3 py-2 rounded-lg">{{ resetForm.errors.error }}</div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="closeResetPassword" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">Batal</button>
                            <button type="submit" :disabled="resetForm.processing" class="px-4 py-2 text-sm font-bold text-white bg-amber-600 rounded-lg hover:bg-amber-700 disabled:opacity-50">
                                <span v-if="resetForm.processing">Mereset...</span>
                                <span v-else>Reset Password</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ═══ Modal Delete Confirmation ═══ -->
            <div v-if="deleteModal.open" @click.self="closeDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
                    <div class="p-6 text-center">
                        <div class="w-14 h-14 mx-auto rounded-full bg-rose-100 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <h2 class="text-lg font-extrabold text-slate-900 mb-1">Hapus User?</h2>
                        <p class="text-sm text-slate-600 mb-3">User <strong>{{ deleteModal.user?.name }}</strong> akan dihapus permanen.</p>
                        <div v-if="(deleteModal.user?.orders_count || 0) > 0" class="bg-rose-50 border border-rose-200 rounded-lg p-3 text-xs text-rose-700 mb-4 text-left">
                            <p class="font-bold mb-1">⚠️ Order terkait akan ikut terhapus</p>
                            <p>User ini punya <strong>{{ deleteModal.user.orders_count }} order</strong>. Semua order (termasuk yang sudah lunas) akan ikut terhapus dan tidak bisa di-recover.</p>
                        </div>
                        <div class="flex justify-center gap-2 mt-4">
                            <button @click="closeDelete" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">Batal</button>
                            <button @click="confirmDelete" :disabled="deleting" class="px-4 py-2 text-sm font-bold text-white bg-rose-600 rounded-lg hover:bg-rose-700 disabled:opacity-50">
                                <span v-if="deleting">Menghapus...</span>
                                <span v-else>Ya, Hapus</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ Modal Lihat Template ═══ -->
            <div v-if="templatesModal.open" @click.self="closeTemplates" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                        <div>
                            <h2 class="text-lg font-extrabold text-slate-900">Template Dimiliki</h2>
                            <p class="text-xs text-slate-500">{{ templatesModal.user?.name }} — <span class="font-semibold text-emerald-600">{{ templatesModal.user?.paid_orders_count || 0 }} template</span></p>
                        </div>
                        <button @click="closeTemplates" class="p-1.5 hover:bg-slate-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-6">
                        <div v-if="loadingTemplates" class="text-center text-sm text-slate-500 py-6">Memuat...</div>
                        <div v-else-if="(templatesModal.user?.paid_orders_count || 0) === 0" class="text-center text-sm text-slate-500 py-6">
                            <p>User ini belum membeli template apapun.</p>
                        </div>
                        <div v-else class="text-center text-sm text-slate-600 py-6">
                            <p>User ini memiliki <strong>{{ templatesModal.user.paid_orders_count }} template</strong> yang dibeli.</p>
                            <p class="text-xs text-slate-400 mt-1">(Lihat di halaman <Link href="/admin/transactions" class="text-indigo-600 hover:underline">/admin/transactions</Link> untuk detail per order)</p>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>