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

        $data['slug'] = Str::slug($data['name']);
        $data['allow_edit_before_checkout'] = $request->boolean('allow_edit_before_checkout');

        // ── Create template dulu untuk dapat ID unik (auto-increment) ──
        $data['zip_file'] = null; // akan di-set setelah dapat ID
        $template = Template::create($data);

        // ── Store folder structure pakai ID template (unique per-template) ──
        // Path: storage/app/public/templates/{id}/original/{file}
        $basePath = "templates/{$template->id}/original";
        $hasLoginHtml = false;

        $files = $request->file('template_files');
        $paths = $request->input('relative_paths', []);

        foreach ($files as $i => $file) {
            $relativePath = $paths[$i] ?? $file->getClientOriginalName();
            $targetPath = $basePath . '/' . $relativePath;

            // Ensure subdirectory exists
            $dir = dirname($targetPath);
            if (!Storage::disk('public')->exists($dir)) {
                Storage::disk('public')->makeDirectory($dir);
            }

            Storage::disk('public')->putFileAs(dirname($targetPath), $file, basename($relativePath));

            if (strtolower(basename($relativePath)) === 'login.html') {
                $hasLoginHtml = true;
            }
        }

        if (!$hasLoginHtml) {
            // Clean up uploaded files
            Storage::disk('public')->deleteDirectory($basePath);
            return back()->withErrors([
                'template_files' => "Folder template harus berisi file login.html. File ini wajib ada agar halaman login hotspot MikroTik dapat ditampilkan ke pengguna. Silakan pilih folder lain yang berisi login.html, atau tambahkan file login.html ke folder Anda lalu upload ulang.",
            ])->withInput();
        }

        $data['zip_file'] = $basePath; // Folder path ID-based (e.g. templates/19/original)

        // Auto-generate thumbnail dari login.html template (no manual upload needed)
        $fullPath = Storage::disk('public')->path($basePath);
        $thumbnailRel = TemplateThumbnailGenerator::generate(
            $template->id,
            $data['name'],
            $fullPath
        );
        if ($thumbnailRel) {
            $data['preview_image'] = $thumbnailRel;
        }

        // (No longer support manual preview_image upload — auto-generated only)

        Template::create($data);

        return redirect()->route('admin.templates.index')->with('success', 'Template berhasil ditambahkan! ' . count($files) . ' file diupload, thumbnail auto-generated.');
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

        // Handle folder re-upload
        if ($request->hasFile('template_files')) {
            $slug = $data['slug'];
            $basePath = "templates/{$slug}";

            // Clear old folder
            if ($template->zip_file && Storage::disk('public')->exists($template->zip_file)) {
                Storage::disk('public')->deleteDirectory($template->zip_file);
            }

            $files = $request->file('template_files');
            $paths = $request->input('relative_paths', []);

            $hasLoginHtml = false;
            foreach ($files as $i => $file) {
                $relativePath = $paths[$i] ?? $file->getClientOriginalName();
                $targetPath = $basePath . '/' . $relativePath;
                $dir = dirname($targetPath);
                if (!Storage::disk('public')->exists($dir)) {
                    Storage::disk('public')->makeDirectory($dir);
                }
                Storage::disk('public')->putFileAs(dirname($targetPath), $file, basename($relativePath));

                if (strtolower(basename($relativePath)) === 'login.html') {
                    $hasLoginHtml = true;
                }
            }

            if (!$hasLoginHtml) {
                Storage::disk('public')->deleteDirectory($basePath);
                return back()->withErrors([
                    'template_files' => "Folder template harus berisi file login.html. File ini wajib ada agar halaman login hotspot MikroTik dapat ditampilkan ke pengguna. Silakan pilih folder lain yang berisi login.html, atau tambahkan file login.html ke folder Anda lalu upload ulang.",
                ])->withInput();
            }

            $data['zip_file'] = $basePath;
        }

        // Handle preview image
        if ($request->hasFile('preview_image')) {
            if ($template->preview_image) {
                Storage::disk('public')->delete($template->preview_image);
            }
            $data['preview_image'] = $request->file('preview_image')->store('templates/previews', 'public');
        }

        $template->update($data);

        return redirect()->route('admin.templates.index')->with('success', 'Template berhasil diupdate!');
    }

    // ── Delete ────────────────────────
    public function destroy(Template $template)
    {
        // Hapus folder template (original files) + thumbnail sebelum delete DB record
        // supaya tidak ada orphaned files di storage
        if ($template->zip_file) {
            $folderPath = dirname($template->zip_file); // templates/{id}
            if (Storage::disk('public')->exists($folderPath)) {
                Storage::disk('public')->deleteDirectory($folderPath);
            }
        }

        $template->delete();
        return back()->with('success', 'Template berhasil dihapus (folder + thumbnail ikut terhapus)!');
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
    private function templateRules(bool $requireFiles = true): array
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:modern,classic,minimalis,pro',
            'short_desc' => 'nullable|string|max:500',
            'long_desc' => 'nullable|string',
            'price' => 'required|integer|min:0',
            'discount_price' => 'nullable|integer|min:0',
            'badge' => 'nullable|string|in:Best Seller,Baru,Populer,Sale,Trending',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'status' => 'required|in:draft,published',
            'allow_edit_before_checkout' => 'boolean',
            'preview_gradients' => 'nullable|array',
            'preview_image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'template_files' => ($requireFiles ? 'required|' : '') . 'array|min:1',
            'template_files.*' => 'file|max:10240',
            'relative_paths' => 'nullable|array',
            'relative_paths.*' => 'string',
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

            'short_desc.max'       => 'Deskripsi singkat maksimal 500 karakter.',

            'price.required'       => 'Harga wajib diisi.',
            'price.integer'        => 'Harga harus berupa angka bulat (tanpa titik/koma).',
            'price.min'            => 'Harga tidak boleh bernilai negatif.',

            'discount_price.integer' => 'Harga diskon harus berupa angka bulat.',
            'discount_price.min'     => 'Harga diskon tidak boleh negatif.',

            'badge.in'             => 'Badge tidak valid. Pilih: Best Seller, Baru, Populer, Sale, atau Trending.',

            'status.required'      => 'Status publikasi wajib dipilih.',
            'status.in'            => 'Status tidak valid. Pilih: Draft atau Published.',

            'preview_image.image'  => 'File preview harus berupa gambar (bukan dokumen lain).',
            'preview_image.mimes'  => 'Format gambar harus PNG, JPG, atau JPEG.',
            'preview_image.max'    => 'Ukuran gambar preview terlalu besar. Maksimal 2 MB.',

            'template_files.required' => 'Folder template hotspot wajib diupload.',
            'template_files.array'    => 'Format upload folder tidak valid. Silakan pilih folder ulang.',
            'template_files.min'      => 'Folder template tidak boleh kosong. Pilih folder yang berisi file template.',

            'template_files.*.file'  => 'Salah satu file dalam folder tidak dapat dibaca. Pastikan file tidak rusak.',
            'template_files.*.max'   => 'Ukuran salah satu file terlalu besar. Maksimal 10 MB per file. Silakan kompres folder Anda dan coba lagi.',
        ];
    }
}
