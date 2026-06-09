<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast.js';
import { formatUploadErrors } from '@/Utils/formatErrors.js';

const props = defineProps({ template: Object });

const form = useForm({
    _method: 'PUT',
    name: props.template.name,
    category: props.template.category,
    short_desc: props.template.short_desc || '',
    long_desc: props.template.long_desc || '',
    price: props.template.price,
    discount_price: props.template.discount_price,
    badge: props.template.badge || '',
    features: props.template.features || [''],
    status: props.template.status,
    allow_edit_before_checkout: props.template.allow_edit_before_checkout,
    preview_gradients: props.template.preview_gradients || [],
    preview_image: null,
    template_files: [],
    relative_paths: [],
});

// ── Folder upload ────────────────────
const folderCount = ref(0);
const totalSize = ref('');
function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' KB';
    return (bytes/(1024*1024)).toFixed(1) + ' MB';
}
function onFolderChange(e) {
    const files = Array.from(e.target.files);
    if (!files.length) return;
    form.template_files = [];
    form.relative_paths = [];
    let total = 0;
    files.forEach(f => {
        const path = f.webkitRelativePath || f.name;
        form.template_files.push(f);
        form.relative_paths.push(path);
        total += f.size;
    });
    folderCount.value = files.length;
    totalSize.value = formatSize(total);
}

const featureInput = ref('');
function addFeature() {
    if (featureInput.value.trim()) {
        form.features.push(featureInput.value.trim());
        featureInput.value = '';
    }
}
function removeFeature(i) { form.features.splice(i, 1); }

function coerceToStringArray(arr) {
    if (!Array.isArray(arr)) return [];
    return arr
        .map((v) => {
            if (v === null || v === undefined) return null;
            if (typeof v === 'string') return v.trim();
            if (typeof v === 'number' || typeof v === 'boolean') return String(v);
            if (typeof v === 'object') {
                return (v.label ?? v.name ?? v.value ?? v.text ?? '').toString().trim();
            }
            return String(v).trim();
        })
        .filter((v) => v !== null && v !== undefined && v !== '');
}

function submit() {
    form.transform((data) => {
        const fd = new FormData();
        for (const [key, value] of Object.entries(data)) {
            if (value === null || value === undefined) continue;
            if (key === 'template_files' || key === 'relative_paths') continue;
            if (Array.isArray(value)) {
                const sanitized = coerceToStringArray(value);
                sanitized.forEach((v) => fd.append(`${key}[]`, v));
            } else if (value instanceof File || value instanceof Blob) {
                fd.append(key, value);
            } else if (typeof value === 'boolean') {
                fd.append(key, value ? '1' : '0');
            } else {
                fd.append(key, value);
            }
        }
        (data.template_files || []).forEach((file, i) => {
            fd.append(`template_files[${i}]`, file);
        });
        (data.relative_paths || []).forEach((p, i) => {
            fd.append(`relative_paths[${i}]`, p);
        });
        return fd;
    }).post('/admin/templates/' + props.template.id, {
        onSuccess: () => {
            useToast().success(
                'Update Berhasil',
                'Template berhasil diperbarui.'
            );
        },
        onError: (errors) => {
            useToast().error('Update Gagal', formatUploadErrors(errors));
        },
    });
}

function formatPrice(p) { return 'Rp ' + p.toLocaleString('id-ID'); }
</script>

<template>
    <Head title="Edit Template — Admin" />

    <AdminLayout>
        <template #title>Edit: {{ template.name }}</template>

        <div class="max-w-3xl">
            <div class="flex justify-end mb-4">
                <button @click="submit" :disabled="form.processing" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 disabled:opacity-50 transition-colors">Update Template</button>
            </div>
            <form @submit.prevent="submit" class="space-y-6">

                <!-- Basic info -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <h2 class="font-semibold text-slate-900">Informasi Dasar</h2>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Nama Template *</label>
                        <input v-model="form.name" type="text" required class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Kategori *</label>
                            <select v-model="form.category" required class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                                <option value="modern">Modern</option>
                                <option value="classic">Classic</option>
                                <option value="minimalis">Minimalis</option>
                                <option value="pro">Professional</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Badge</label>
                            <select v-model="form.badge" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                                <option value="">Tanpa Badge</option>
                                <option value="Best Seller">Best Seller</option>
                                <option value="Baru">Baru</option>
                                <option value="Populer">Populer</option>
                                <option value="Sale">Sale</option>
                                <option value="Trending">Trending</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Harga (Rp) *</label>
                            <input v-model="form.price" type="number" min="0" required class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Harga Diskon (Rp)</label>
                            <input v-model="form.discount_price" type="number" min="0" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Status *</label>
                        <select v-model="form.status" required class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                    <div v-if="template.sold_count > 0" class="text-xs text-slate-400 flex items-center gap-4">
                        <span>Terjual: <strong class="text-slate-600">{{ template.sold_count }} kali</strong></span>
                        <span>Rating: <strong class="text-slate-600">{{ template.rating }}</strong></span>
                    </div>
                </div>

                <!-- Description -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <h2 class="font-semibold text-slate-900">Deskripsi</h2>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Deskripsi Singkat</label>
                        <textarea v-model="form.short_desc" rows="2" maxlength="500" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Deskripsi Lengkap</label>
                        <textarea v-model="form.long_desc" rows="4" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none resize-none"></textarea>
                    </div>
                </div>

                <!-- Features -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <h2 class="font-semibold text-slate-900">Fitur / Tags</h2>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <span v-for="(f, i) in form.features" :key="i" class="inline-flex items-center gap-1 text-xs bg-indigo-50 text-indigo-700 px-2.5 py-1 rounded-lg font-medium">
                            {{ f }}
                            <button @click="removeFeature(i)" class="ml-1 hover:text-red-500">&times;</button>
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <input v-model="featureInput" @keyup.enter="addFeature" type="text" placeholder="Tambah fitur..." class="flex-1 px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                        <button type="button" @click="addFeature" class="px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-xl hover:bg-indigo-100 transition-colors">Tambah</button>
                    </div>
                </div>

                <!-- Upload (optional replace) -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <h2 class="font-semibold text-slate-900">Upload File (opsional — kosongkan jika tidak ingin ganti)</h2>
                    <div v-if="template.preview_image" class="text-xs text-slate-400 mb-2">Preview saat ini: {{ template.preview_image }}</div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Ganti Preview Image (PNG/JPG, max 2MB)</label>
                        <input type="file" accept="image/png,image/jpeg" @input="form.preview_image = $event.target.files[0]" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                    <div v-if="template.zip_file" class="text-xs text-slate-400 mb-2">Folder saat ini: {{ template.zip_file }}</div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Ganti Folder Template (pilih folder hotspot)</label>
                        <p class="text-xs text-slate-400 mb-2">Kosongkan jika tidak ingin mengganti folder template.</p>
                        <input type="file" webkitdirectory directory multiple @change="onFolderChange" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <div v-if="folderCount > 0" class="mt-3 text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-lg flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="font-medium">{{ folderCount }} file dipilih ({{ totalSize }})</span>
                        </div>
                    </div>
                </div>

                <!-- Options -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <h2 class="font-semibold text-slate-900">Pengaturan</h2>
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <input v-model="form.allow_edit_before_checkout" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-slate-700">Izinkan pembeli mengedit template sebelum checkout</span>
                    </label>
                </div>

                <div class="flex justify-end gap-3">
                    <Link href="/admin/templates" class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">Batal</Link>
                    <button type="submit" :disabled="form.processing" class="px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 disabled:opacity-50 shadow-sm transition-colors">Update Template</button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
