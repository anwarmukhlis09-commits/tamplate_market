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
            // Escape LIKE wildcard (%) & (_) supaya input user tidak匹配 semua row
            $q = addcslashes($request->search, '%_\\');
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
        //    Validasi extension sudah di 'mimes' rule (whitelist). Tetap sanitize
        //    relativePath: tolak .., leading slash, dan karakter aneh supaya
        //    user tidak bisa menulis ke luar folder template.
        $basePath = "templates/{$template->id}/original";
        $files = $request->file('template_files') ?? [];
        $relativePaths = $request->input('relative_paths', []);
        foreach ($files as $i => $file) {
            $relativePath = $relativePaths[$i] ?? $file->getClientOriginalName();
            // Sanitasi: tolak path traversal & absolut
            if (strpos($relativePath, '..') !== false
                || strpos($relativePath, '\\') !== false
                || strpos($relativePath, '/') === 0
                || strpos($relativePath, "\0") !== false) {
                continue; // skip file berbahaya, lanjut ke file berikutnya
            }
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

            // Simpan file upload — dengan validasi extension + sanitasi path
            // (lihat store() untuk detail; update() pakai pola yang sama)
            $files = $request->file('template_files');
            $relativePaths = $request->input('relative_paths', []);
            foreach ($files as $i => $file) {
                $relativePath = $relativePaths[$i] ?? $file->getClientOriginalName();
                if (strpos($relativePath, '..') !== false
                    || strpos($relativePath, '\\') !== false
                    || strpos($relativePath, '/') === 0
                    || strpos($relativePath, "\0") !== false) {
                    continue;
                }
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
        //
        // SECURITY: Validasi ketat bahwa folder yang akan dihapus BERAWAL dengan
        // "templates/" — JANGAN pernah deleteDirectory('/') atau path di luar
        // namespace template. realpath() check memblokir symlink/case tricks.
        $storageRoot = realpath(Storage::disk('public')->path(''));

        if ($template->zip_file) {
            // zip_file = "templates/{id}/original" → dirname = "templates/{id}"
            $idFolder = dirname($template->zip_file);
            $this->safeDeleteTemplateDir($idFolder, $storageRoot);
        }

        // Defensive: kalau ada struktur lama pakai slug-based folder, bersihkan juga.
        // Slug hanya boleh [a-z0-9-] — whitelist karakter sebelum dipakai di path.
        if ($template->slug && preg_match('/^[a-z0-9-]+$/', $template->slug)) {
            $slugFolder = 'templates/' . $template->slug;
            $this->safeDeleteTemplateDir($slugFolder, $storageRoot);
        }

        // Hapus thumbnail eksplisit (preview_image) kalau masih ada — redundant
        // dengan deleteDirectory di atas tapi aman kalau path-nya berbeda.
        // Validasi: path harus di dalam storage root & berawal "templates/".
        if ($template->preview_image) {
            $this->safeDeleteFile($template->preview_image, $storageRoot);
        }

        // Hapus cache/preview orphan di templates/previews (path ini dipakai oleh
        // `update()` saat upload manual preview image, lihat baris 184)
        if ($template->preview_image) {
            $previewInPreviews = 'templates/previews/' . basename($template->preview_image);
            $this->safeDeleteFile($previewInPreviews, $storageRoot);
        }

        $template->delete();
        return back()->with('success', 'Template berhasil dihapus!');
    }

    /**
     * Hapus directory template SETELAH verifikasi path absolut hasil realpath
     * berada di dalam storage root. Mencegah deleteDirectory('/') atau escape
     * keluar dari disk 'public' via path manipulation.
     */
    private function safeDeleteTemplateDir(string $relativePath, string $storageRoot): void
    {
        // Tolak null byte, traversal, path absolut, atau path di luar "templates/"
        if (strpos($relativePath, "\0") !== false
            || strpos($relativePath, '..') !== false
            || strpos($relativePath, '\\') !== false
            || strpos($relativePath, '/') === 0
            || strpos($relativePath, 'templates/') !== 0) {
            return;
        }

        $absPath = Storage::disk('public')->path($relativePath);
        $realPath = realpath($absPath);
        if ($realPath === false || $storageRoot === false) {
            return;
        }
        if (strpos($realPath, $storageRoot) !== 0) {
            return;
        }
        if (is_dir($realPath)) {
            Storage::disk('public')->deleteDirectory($relativePath);
        }
    }

    /**
     * Hapus single file SETELAH verifikasi path aman (di dalam storage root,
     * berawalan "templates/", tidak ada traversal).
     */
    private function safeDeleteFile(string $relativePath, string $storageRoot): void
    {
        if (strpos($relativePath, "\0") !== false
            || strpos($relativePath, '..') !== false
            || strpos($relativePath, '\\') !== false
            || strpos($relativePath, '/') === 0
            || strpos($relativePath, 'templates/') !== 0) {
            return;
        }
        $absPath = Storage::disk('public')->path($relativePath);
        $realPath = realpath($absPath);
        if ($realPath === false || $storageRoot === false) {
            return;
        }
        if (strpos($realPath, $storageRoot) !== 0) {
            return;
        }
        if (is_file($realPath)) {
            Storage::disk('public')->delete($relativePath);
        }
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
    //
    // SECURITY: template_files divalidasi extension (whitelist) supaya admin
    // tidak bisa upload file executable (.php, .phtml, .jsp, .cgi, dll) ke
    // folder template yang di-serve sebagai static asset via route preview.
    // Whitelist: file yang relevan untuk template MikroTik hotspot + asset web standar.
    private function templateRules(bool $requireFiles = true): array
    {
        $allowedExt = 'html,htm,css,js,svg,png,jpg,jpeg,gif,webp,ico,'
                   . 'woff,woff2,ttf,otf,eot,json,txt,md,xml';

        return [
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:modern,classic,minimalis,pro',
            'price' => 'required|integer|min:0',
            'status' => 'required|in:draft,published',
            'allow_edit_before_checkout' => 'boolean',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'template_files' => ($requireFiles ? 'required|' : '') . 'array|min:1',
            'template_files.*' => "file|max:10240|mimes:{$allowedExt}",
            'relative_paths' => 'nullable|array',
            'relative_paths.*' => ['string', 'regex:#^(?!.*\.\.)(?!/)[A-Za-z0-9._\-/]+$#'],
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
            'template_files.*.mimes' => 'Tipe file tidak diizinkan. Hanya file HTML/CSS/JS/gambar/font/teks yang boleh diupload.',

            'relative_paths.*.regex' => 'Path file mengandung karakter tidak valid (.. atau path absolut).',
        ];
    }
}
