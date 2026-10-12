<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

// Presets untuk Ikon
const iconPresets = [
    { label: 'Grid / Minimalis', path: 'M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z' },
    { label: 'Petir / Modern', path: 'M13 10V3L4 14h7v7l9-11h-7z' },
    { label: 'Gamepad / Gaming', path: 'M11 17a1 1 0 102 0v-1a1 1 0 10-2 0v1zM5 17a1 1 0 102 0v-1a1 1 0 10-2 0v1zm5-15a7 7 0 00-7 7c0 2 .5 3.5 1.5 5L3 17h14l-1.5-3c1-1.5 1.5-3 1.5-5a7 7 0 00-7-7z' },
    { label: 'Gedung / Hotel', path: 'M3 21h18M3 7v14M21 7v14M6 21V11h12v10M9 7V3h6v4' },
    { label: 'Kopi / Cafe', path: 'M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z' },
    { label: 'Topi / Sekolah', path: 'M12 14l9-5-9-5-9 5 9 5z' },
    { label: 'Tiket / Voucher', path: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z' },
    { label: 'Wifi / ISP', path: 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M2 8.82a15 15 0 0120 0M5 12.859a10 10 0 0114 0M8.5 16.429a5 5 0 017 0' },
    { label: 'Jam / Classic', path: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Bintang / Pro', path: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' },
];

// Presets untuk Warna Gradien
const colorPresets = [
    { label: 'Indigo', class: 'from-indigo-500 to-violet-500' },
    { label: 'Slate', class: 'from-slate-400 to-slate-500' },
    { label: 'Pink / Fuchsia', class: 'from-fuchsia-500 to-pink-500' },
    { label: 'Amber / Orange', class: 'from-amber-400 to-orange-500' },
    { label: 'Rose', class: 'from-rose-400 to-pink-500' },
    { label: 'Cyan / Blue', class: 'from-cyan-400 to-blue-500' },
    { label: 'Emerald / Teal', class: 'from-emerald-400 to-teal-500' },
    { label: 'Blue / Navy', class: 'from-blue-500 to-indigo-600' },
    { label: 'Purple / Pink', class: 'from-purple-500 to-pink-500' },
];

// Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingCategory = ref(null);

// Delete Modal State
const isDeleteModalOpen = ref(false);
const categoryToDelete = ref(null);

// Forms
const form = useForm({
    name: '',
    slug: '',
    icon: iconPresets[0].path,
    color: colorPresets[0].class,
});

const deleteForm = useForm({});

// Auto-generate slug while typing name in create mode
function handleNameInput() {
    if (!isEditing.value) {
        form.slug = form.name
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
}

function openCreateModal() {
    isEditing.value = false;
    editingCategory.value = null;
    form.reset();
    form.clearErrors();
    form.icon = iconPresets[0].path;
    form.color = colorPresets[0].class;
    isModalOpen.value = true;
}

function openEditModal(cat) {
    isEditing.value = true;
    editingCategory.value = cat;
    form.clearErrors();
    form.name = cat.name;
    form.slug = cat.slug;
    form.icon = cat.icon || iconPresets[0].path;
    form.color = cat.color || colorPresets[0].class;
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (isEditing.value && editingCategory.value) {
        form.put(route('admin.categories.update', editingCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.categories.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

function confirmDelete(cat) {
    categoryToDelete.value = cat;
    isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
    isDeleteModalOpen.value = false;
    categoryToDelete.value = null;
}

function executeDelete() {
    if (!categoryToDelete.value) return;
    deleteForm.delete(route('admin.categories.destroy', categoryToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
}
</script>

<template>
    <Head title="Manajemen Kategori — Admin" />
    <AdminLayout>
        <!-- ═══ TOPBAR TITLE & ACTIONS ═══ -->
        <template #title>Manajemen Kategori</template>

        <template #actions>
            <button
                @click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Kategori
            </button>
        </template>

        <!-- ═══ HEADER DESCRIPTION & STATS ═══ -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm text-slate-500">Kelola kategori untuk mengelompokkan template login hotspot Anda.</p>
            </div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-200/60 text-slate-700 text-xs font-semibold self-start sm:self-auto">
                <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                Total {{ categories.length }} Kategori
            </div>
        </div>

        <!-- ═══ CATEGORY GRID ═══ -->
        <div v-if="categories.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            <div
                v-for="cat in categories"
                :key="cat.id"
                class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all group flex flex-col justify-between"
            >
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center text-white shadow-md"
                            :class="cat.color"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="cat.icon"/>
                            </svg>
                        </div>
                        <button
                            @click="confirmDelete(cat)"
                            class="w-8 h-8 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 flex items-center justify-center transition-colors focus:outline-none"
                            title="Hapus Kategori"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                            </svg>
                        </button>
                    </div>
                    <h3 class="font-bold text-slate-900 text-base mb-1">{{ cat.name }}</h3>
                    <p class="text-xs text-slate-400 font-mono mb-3">/{{ cat.slug }}</p>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-100 mt-2">
                    <span class="text-xs text-slate-500 font-medium">
                        {{ cat.templates_count || 0 }} template
                    </span>
                    <button
                        @click="openEditModal(cat)"
                        type="button"
                        class="text-xs font-semibold text-indigo-600 group-hover:text-indigo-700 hover:underline inline-flex items-center gap-1 focus:outline-none"
                    >
                        Edit →
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800 mb-1">Belum Ada Kategori</h3>
            <p class="text-sm text-slate-500 mb-4 max-w-sm mx-auto">Tambahkan kategori pertama untuk mulai mengelompokkan template hotspot Anda.</p>
            <button
                @click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-sm transition-all"
            >
                + Tambah Kategori
            </button>
        </div>

        <!-- ═══ MODAL TAMBAH / EDIT KATEGORI ═══ -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

            <div class="relative bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl z-10 border border-slate-200">
                <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">
                            {{ isEditing ? 'Edit Kategori' : 'Tambah Kategori Baru' }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ isEditing ? 'Perbarui informasi dan tampilan kategori' : 'Kategori baru akan langsung tersedia pada form template' }}
                        </p>
                    </div>
                    <button
                        @click="closeModal"
                        class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Preview Card -->
                    <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center gap-3.5 mb-2">
                        <div
                            class="w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center text-white shadow-sm shrink-0"
                            :class="form.color"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="form.icon"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Preview Tampilan</p>
                            <h4 class="text-sm font-bold text-slate-800 truncate">{{ form.name || 'Nama Kategori' }}</h4>
                            <p class="text-xs text-slate-500 font-mono truncate">/{{ form.slug || 'slug-kategori' }}</p>
                        </div>
                    </div>

                    <!-- Nama Kategori -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Kategori <span class="text-rose-500">*</span></label>
                        <input
                            v-model="form.name"
                            @input="handleNameInput"
                            type="text"
                            placeholder="Contoh: Cafe & Resto"
                            required
                            class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-500">{{ form.errors.name }}</p>
                    </div>

                    <!-- Slug -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Slug URL <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs text-slate-400 font-mono">/</span>
                            <input
                                v-model="form.slug"
                                type="text"
                                placeholder="cafe-resto"
                                required
                                class="w-full pl-7 pr-3.5 py-2.5 text-sm border border-slate-200 rounded-xl font-mono text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all"
                            />
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Digunakan sebagai pengenal sistem dan filter URL.</p>
                        <p v-if="form.errors.slug" class="mt-1 text-xs text-rose-500">{{ form.errors.slug }}</p>
                    </div>

                    <!-- Pilihan Warna Gradien -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Warna Gradien</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                v-for="c in colorPresets"
                                :key="c.class"
                                type="button"
                                @click="form.color = c.class"
                                class="flex items-center gap-2 p-2 rounded-xl border text-left transition-all"
                                :class="form.color === c.class ? 'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20' : 'border-slate-200 hover:border-slate-300'"
                            >
                                <span class="w-5 h-5 rounded-lg bg-gradient-to-br shrink-0 shadow-sm" :class="c.class"></span>
                                <span class="text-xs font-medium text-slate-700 truncate">{{ c.label }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Pilihan Ikon Preset -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ikon Kategori</label>
                        <div class="grid grid-cols-5 gap-2 max-h-36 overflow-y-auto p-1 border border-slate-100 rounded-xl">
                            <button
                                v-for="(ico, idx) in iconPresets"
                                :key="idx"
                                type="button"
                                @click="form.icon = ico.path"
                                :title="ico.label"
                                class="h-11 rounded-xl flex items-center justify-center border transition-all"
                                :class="form.icon === ico.path ? 'border-indigo-600 bg-indigo-50 text-indigo-600 ring-2 ring-indigo-500/20' : 'border-slate-200 text-slate-500 hover:bg-slate-50'"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="ico.path"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-all"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
                        >
                            <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            {{ isEditing ? 'Simpan Perubahan' : 'Tambah Kategori' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ═══ MODAL KONFIRMASI HAPUS ═══ -->
        <div v-if="isDeleteModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeDeleteModal"></div>

            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl z-10 border border-slate-200">
                <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                    </svg>
                </div>

                <div class="text-center mb-5">
                    <h3 class="text-base font-bold text-slate-900 mb-1">Hapus Kategori</h3>
                    <p class="text-sm text-slate-500">
                        Apakah Anda yakin ingin menghapus kategori <span class="font-semibold text-slate-800">"{{ categoryToDelete?.name }}"</span>?
                    </p>

                    <!-- Warning jika masih ada template -->
                    <div
                        v-if="categoryToDelete?.templates_count > 0"
                        class="mt-3.5 p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs text-left flex items-start gap-2.5"
                    >
                        <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <span class="font-semibold">Perhatian:</span> Terdapat <b>{{ categoryToDelete.templates_count }}</b> template yang masih terhubung dengan kategori ini. Ubah kategori template tersebut terlebih dahulu sebelum menghapus kategori ini.
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        @click="closeDeleteModal"
                        class="px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-all"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDelete"
                        :disabled="categoryToDelete?.templates_count > 0 || deleteForm.processing"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed inline-flex items-center gap-2"
                    >
                        <svg v-if="deleteForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        Hapus Kategori
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
