<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Support\TemplateThumbnailGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminTemplateController extends Controller
{
    // ── Dashboard ─────────────────────
    public function dashboard()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'templates' => Template::count(),
                'published' => Template::where('status', 'published')->count(),
                'draft' => Template::where('status', 'draft')->count(),
                'total_sold' => Template::sum('sold_count'),
                'total_revenue' => Template::sum('sold_count') * 50000, // placeholder
                'user_count' => \App\Models\User::count(),
            ],
        ]);
    }

    // ── Index ─────────────────────────
    public function index(Request $request)
    {
        $query = Template::query();

        if ($request->search) {
            $q = $request->search;
            $query->where(function ($sql) use ($q) {
                $sql->where('name', 'like', "%{$q}%")
                   ->orWhere('category', 'like', "%{$q}%");
            });
        }

        if ($request->status && in_array($request->status, ['draft', 'published'])) {
            $query->where('status', $request->status);
        }

        $templates = $query->orderBy('updated_at', 'desc')->paginate(12);

        return Inertia::render('Admin/Templates/Index', [
            'templates' => $templates,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    // ── Create ────────────────────────
    public function create()
    {
        return Inertia::render('Admin/Templates/Create');
    }

    // ── Store ─────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate($this->templateRules(), $this->templateMessages());

        $data['allow_edit_before_checkout'] = $request->boolean('allow_edit_before_checkout');
        $data['slug'] = Str::slug($data['name']);

        // 1) Create DB record dulu untuk dapat ID unik
        $data['zip_file'] = null;
        $data['preview_image'] = null;
        $template = Template::create($data);

        // 2) Simpan SEMUA file upload ke templates/{id}/original/ — apa adanya
        //    Tidak ada validasi, tidak ada cek login.html, tidak ada filter.
        //    Apa yang user upload = apa yang tersimpan.
        $basePath = "templates/{$template->id}/original";
        $files = $request->file('template_files') ?? [];
        $relativePaths = $request->input('relative_paths', []);
        foreach ($files as $i => $file) {
            $relativePath = $relativePaths[$i] ?? $file->getClientOriginalName();
            $targetDir = dirname($basePath . '/' . $relativePath);
            Storage::disk('public')->putFileAs(
                $targetDir,
                $file,
                basename($relativePath)
            );
        }

        // 3) Generate thumbnail kalau login.html ada di root — optional, no validation
        //    (Skip kalau user upload preview_image manual di langkah 4 — manual prioritas)
        $autoThumbnail = null;
        if (!$request->hasFile('preview_image') && Storage::disk('public')->exists($basePath . '/login.html')) {
            $autoThumbnail = TemplateThumbnailGenerator::generate(
                $template->id,
                $data['name'],
                Storage::disk('public')->path($basePath)
            );
        }

        // 4) Simpan preview_image manual kalau user upload — prioritas di atas auto-thumbnail
        if ($request->hasFile('preview_image')) {
            $manualPath = $request->file('preview_image')->store('templates/' . $template->id, 'public');
            $template->preview_image = $manualPath;
        } elseif ($autoThumbnail) {
            $template->preview_image = $autoThumbnail;
        }

        // 5) Update template dengan zip_file path + preview_image
        $template->zip_file = $basePath;
        $template->save();

        $fileCount = count($files);
        return redirect()->route('admin.templates.index')
            ->with('success', "Template '{$template->name}' (id={$template->id}) tersimpan. {$fileCount} file.");
    }

    // ── Edit ──────────────────────────
    public function edit(Template $template)
    {
        return Inertia::render('Admin/Templates/Edit', [
            'template' => $template,
        ]);
    }

    // ── Update ────────────────────────
    public function update(Request $request, Template $template)
    {
        $data = $request->validate($this->templateRules(false), $this->templateMessages());

        $data['slug'] = Str::slug($data['name']);
        $data['allow_edit_before_checkout'] = $request->boolean('allow_edit_before_checkout');

        // Handle folder re-upload — pakai ID-based path (sama dengan store())
        if ($request->hasFile('template_files')) {
            $basePath = "templates/{$template->id}/original";

            // Clear old folder
            if (Storage::disk('public')->exists($basePath)) {
                Storage::disk('public')->deleteDirectory($basePath);
            }

            // Simpan file upload — apa adanya, tanpa validasi
            $files = $request->file('template_files');
            $relativePaths = $request->input('relative_paths', []);
            foreach ($files as $i => $file) {
                $relativePath = $relativePaths[$i] ?? $file->getClientOriginalName();
                $targetDir = dirname($basePath . '/' . $relativePath);
                Storage::disk('public')->putFileAs(
                    $targetDir,
                    $file,
                    basename($relativePath)
                );
            }

            $data['zip_file'] = $basePath;

            // Re-generate auto-thumbnail kalau login.html ada & user TIDAK upload manual
            if (!$request->hasFile('preview_image') && Storage::disk('public')->exists($basePath . '/login.html')) {
                $thumbnailRel = TemplateThumbnailGenerator::generate(
                    $template->id,
                    $data['name'],
                    Storage::disk('public')->path($basePath)
                );
                if ($thumbnailRel) {
                    $data['preview_image'] = $thumbnailRel;
                }
            }
        }

        // Handle manual preview_image upload (works even without folder re-upload)
        if ($request->hasFile('preview_image')) {
            // Hapus preview lama kalau ada
            if ($template->preview_image && Storage::disk('public')->exists($template->preview_image)) {
                Storage::disk('public')->delete($template->preview_image);
            }
            $data['preview_image'] = $request->file('preview_image')->store('templates/' . $template->id, 'public');
        }

        $template->update($data);

        return redirect()->route('admin.templates.index')->with('success', 'Template berhasil diupdate!');
    }

    // ── Delete ────────────────────────
    public function destroy(Template $template)
    {
        // Hapus folder template (original files) + thumbnail sebelum delete DB record
        // supaya tidak ada orphaned files di storage. Hanya menghapus folder
        // milik template_id ini, bukan template lain.
        if ($template->zip_file) {
            // zip_file = "templates/{id}/original" → dirname = "templates/{id}"
            $idFolder = dirname($template->zip_file);
            if (Storage::disk('public')->exists($idFolder)) {
                Storage::disk('public')->deleteDirectory($idFolder);
            }
        }

        // Defensive: kalau ada struktur lama pakai slug-based folder, bersihkan juga
        if ($template->slug) {
            $slugFolder = 'templates/' . $template->slug;
            if (Storage::disk('public')->exists($slugFolder)) {
                Storage::disk('public')->deleteDirectory($slugFolder);
            }
        }

        // Hapus thumbnail eksplisit (preview_image) kalau masih ada — redundant
        // dengan deleteDirectory di atas tapi aman kalau path-nya berbeda
        if ($template->preview_image && Storage::disk('public')->exists($template->preview_image)) {
            Storage::disk('public')->delete($template->preview_image);
        }

        // Hapus cache/preview orphan di templates/previews (path ini dipakai oleh
        // `update()` saat upload manual preview image, lihat baris 184)
        if ($template->preview_image) {
            $previewInPreviews = 'templates/previews/' . basename($template->preview_image);
            if (Storage::disk('public')->exists($previewInPreviews)) {
                Storage::disk('public')->delete($previewInPreviews);
            }
        }

        $template->delete();
        return back()->with('success', 'Template berhasil dihapus!');
    }

    // ── Toggle publish ────────────────
    public function togglePublish(Template $template)
    {
        $template->update([
            'status' => $template->status === 'published' ? 'draft' : 'published',
        ]);
        return back()->with('success', 'Status template diubah!');
    }

    // ── Shared validation rules ─────────
    // Minimal validation: fokus pada field yang wajib untuk "upload = tampil".
    // Field tambahan (short_desc, badge, dll) divalidasi terpisah jika dikirim.
    private function templateRules(bool $requireFiles = true): array
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:modern,classic,minimalis,pro',
            'price' => 'required|integer|min:0',
            'status' => 'required|in:draft,published',
            'allow_edit_before_checkout' => 'boolean',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'template_files' => ($requireFiles ? 'required|' : '') . 'array|min:1',
            'template_files.*' => 'file|max:10240',
            'relative_paths' => 'nullable|array',
            'relative_paths.*' => 'string',
            'preview_image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ];
    }

    // ── Indonesian error messages ─────────
    private function templateMessages(): array
    {
        return [
            'name.required'        => 'Nama template wajib diisi.',
            'name.string'          => 'Nama template harus berupa teks.',
            'name.max'             => 'Nama template maksimal 255 karakter.',

            'category.required'    => 'Kategori wajib dipilih.',
            'category.in'          => 'Kategori tidak valid. Pilih: Modern, Classic, Minimalis, atau Professional.',

            'price.required'       => 'Harga wajib diisi.',
            'price.integer'        => 'Harga harus berupa angka bulat (tanpa titik/koma).',
            'price.min'            => 'Harga tidak boleh bernilai negatif.',

            'status.required'      => 'Status publikasi wajib dipilih.',
            'status.in'            => 'Status tidak valid. Pilih: Draft atau Published.',

            'template_files.required' => 'Folder template hotspot wajib diupload.',
            'template_files.array'    => 'Format upload folder tidak valid. Silakan pilih folder ulang.',
            'template_files.min'      => 'Folder template tidak boleh kosong. Pilih folder yang berisi file template.',

            'template_files.*.file'  => 'Salah satu file dalam folder tidak dapat dibaca. Pastikan file tidak rusak.',
            'template_files.*.max'   => 'Ukuran salah satu file terlalu besar. Maksimal 10 MB per file. Silakan kompres folder Anda dan coba lagi.',
        ];
    }
}
