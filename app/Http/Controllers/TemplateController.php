<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    /**
     * Tampilkan editor untuk template {id}.
     * Route: GET /template/{id}/editor
     */
    public function edit(Request $request, int $id)
    {
        $template = Template::findOrFail($id);
        return \Inertia\Inertia::render('EditTemplate', [
            'template' => $template,
        ]);
    }

    /**
     * Update konfigurasi template {id} (placeholder).
     * Route: POST /template/{id}/editor
     */
    public function update(Request $request, int $id)
    {
        $template = Template::findOrFail($id);
        $request->validate([
            'business_name' => 'required|string|max:255',
        ]);
        // Placeholder: integrasikan dengan tabel konfigurasi user nanti
        return redirect()->back()->with('success', 'Template ' . $template->name . ' berhasil diupdate.');
    }

    /**
     * Download template {id} sebagai ZIP.
     * Route: GET /template/{id}/download
     *
     * Guard: user HARUS sudah membayar untuk template ini (session paid_templates).
     * Kalau belum bayar → redirect ke /checkout/{id} untuk checkout + payment.
     *
     * Prioritas sumber file:
     *   1. Salinan edit user di `orders/{user_id}/{id}/` (kalau ada & lengkap)
     *   2. Master template di `zip_file` (fallback)
     *
     * Hasil ZIP: login.html, status.html, logout.html, style.css, dan assets
     * siap upload ke MikroTik. ZIP bersih dari __MACOSX & file ._* macOS.
     */
    public function download(Request $request, int $id)
    {
        $template = Template::findOrFail($id);
        $user = $request->user();

        // Guard payment: user harus sudah bayar untuk download.
        // Skip kalau user adalah admin (bypass) atau template gratis (price=0).
        $isFree = (int) $template->price === 0;
        $isAdmin = $user && method_exists($user, 'isAdmin') && $user->isAdmin();
        $paidTemplates = (array) $request->session()->get('paid_templates', []);
        $hasPaid = in_array($id, $paidTemplates, true);

        if (! $isFree && ! $isAdmin && ! $hasPaid) {
            $request->session()->put('url.intended', "/template/{$id}/download");

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'error' => 'payment_required',
                    'message' => 'Selesaikan pembayaran untuk download template.',
                    'redirect' => route('checkout.show', ['id' => $id]),
                ], 402);
            }

            return redirect()
                ->route('checkout.show', ['id' => $id])
                ->with('info', 'Selesaikan pembayaran untuk download template.');
        }

        $userId = $user?->id ?? 'guest';

        // 1) Prioritas: salinan edit user (kalau ada & berisi asset penting)
        $editedPath = "templates/orders/{$userId}/{$id}";
        $editedLoginFile = "{$editedPath}/login.html";
        $useEdited = Storage::disk('public')->exists($editedLoginFile);

        if ($useEdited) {
            // Safety net: kalau edited folder ada tapi belum berisi asset
            // (mis. draft lama dari versi sebelum recursive copy), copy dari
            // master dulu supaya ZIP lengkap.
            $hasAsset = $this->editedHasAsset($editedPath);
            if (! $hasAsset) {
                $this->copyAssetsFromMaster($template, $editedPath);
            }
            // VALIDASI POST-COPY: kalau setelah copy edited folder masih
            // tidak punya asset (mis. copy gagal), fallback ke master agar
            // ZIP tetap lengkap. Lebih baik kirim file master asli daripada
            // ZIP 29KB yang hanya berisi login.html.
            $editedAbs = $this->storagePublicPath($editedPath);
            $hasAssetAfter = $this->editedHasAsset($editedPath);
            if ($hasAssetAfter) {
                $srcPath = $editedAbs;
            } else {
                $folder = $template->zip_file;
                if ($folder && Storage::disk('public')->exists($folder)) {
                    $srcPath = $this->resolveTemplateFolder($folder);
                } else {
                    $srcPath = storage_path('app/master_template');
                }
                // Override suffix — bukan edited version
                $useEdited = false;
            }
        } else {
            // 2) Fallback: master template
            $folder = $template->zip_file;
            if ($folder && Storage::disk('public')->exists($folder)) {
                $srcPath = $this->resolveTemplateFolder($folder);
            } else {
                $srcPath = storage_path('app/master_template');
            }
        }

        // Validasi srcPath exist (cek pakai realpath supaya jalan di Windows
        // dengan path ber-spasi seperti "loginpage 1/")
        $realSrc = realpath($srcPath);
        if (! $realSrc || ! is_dir($realSrc)) {
            return back()->with('error', 'File template tidak ditemukan di storage.');
        }

        // Validasi minimal: harus ada login.html
        if (! file_exists($realSrc . DIRECTORY_SEPARATOR . 'login.html')) {
            return back()->with('error', 'Template tidak punya login.html. Upload folder template yang valid.');
        }

        // Cek zip extension aktif
        if (! class_exists('ZipArchive')) {
            return back()->with('error', 'PHP zip extension belum aktif. Hubungi admin.');
        }

        // Build archive
        $suffix = $useEdited ? '_edited' : '';
        $safeName = preg_replace('/[^A-Za-z0-9\-]/', '_', $template->name);
        $zipFileName = 'Template_ID' . $template->id . '_' . $safeName . $suffix . '.zip';
        // Pakai sys_get_temp_dir() agar unique & auto-cleanup. Fallback ke storage.
        $zipPath = tempnam(sys_get_temp_dir(), 'tpl_zip_');
        if ($zipPath === false) {
            $zipPath = storage_path('app/' . $zipFileName);
        }

        // Pakai realpath sebagai base (handle path dengan spasi)
        $zip = new \ZipArchive();
        // Replace nama file temp → nama .zip yang proper
        $finalZipPath = preg_replace('/\.[^.]+$/', '', $zipPath) . '.zip';

        $created = false;
        if ($zip->open($finalZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $addedCount = 0;
            $totalSize = 0;
            // Pakai realpath agar path dengan spasi (mis. "loginpage 1") aman
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($realSrc, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                $realFile = $file->getRealPath();
                if ($realFile === false) continue;

                // Skip __MACOSX & file metadata macOS (._*) — bukan bagian template
                $relative = str_replace($realSrc, '', $realFile);
                $relative = str_replace('\\', '/', $relative);
                $relative = ltrim($relative, '/');
                if (strpos($relative, '__MACOSX/') === 0) continue;
                if (preg_match('#/\._[^/]+$#', $relative)) continue;
                if (basename($relative) === 'Thumbs.db' || basename($relative) === '.DS_Store') continue;

                if ($file->isDir()) {
                    $zip->addEmptyDir($relative);
                } else {
                    $size = $file->getSize();
                    if ($size === false || $size === 0) continue; // skip 0-byte file
                    if ($zip->addFile($realFile, $relative)) {
                        $addedCount++;
                        $totalSize += $size;
                    }
                }
            }
            $zip->close();
            $created = true;
        }

        if (! $created || ! file_exists($finalZipPath) || filesize($finalZipPath) === 0) {
            @unlink($finalZipPath);
            return back()->with('error', 'Gagal membuat file ZIP. Coba lagi atau hubungi admin.');
        }

        // Log untuk audit
        \Log::info('Template download', [
            'template_id' => $id,
            'user_id' => $userId,
            'use_edited' => $useEdited,
            'src_path' => $realSrc,
            'zip_size' => filesize($finalZipPath),
            'file_count' => $addedCount,
        ]);

        return response()
            ->download($finalZipPath, $zipFileName)
            ->deleteFileAfterSend(true);
    }

    /**
     * Convert relative path di disk 'public' jadi absolute path di filesystem.
     * Pakai ini daripada Storage::disk('public')->path() karena method itu
     * tidak ada di Filesystem contract (PHPStan warning). Implementasi ini
     * equivalent: public disk root = storage_path('app/public').
     */
    private function storagePublicPath(string $relativePath): string
    {
        // Normalize: hilangkan leading slash kalau ada
        $relativePath = ltrim($relativePath, '/');
        return storage_path("app/public/{$relativePath}");
    }

    /**
     * Cek apakah folder edited sudah berisi asset penting (style.css, images/, img/, assets/).
     * Dipakai untuk safety net copy dari master kalau edited folder tidak lengkap.
     *
     * FIX: cek 'img' (singular) juga — beberapa template MikroTik pakai
     * folder 'img/' (bukan 'images/' plural). Tanpa cek ini, editedHasAsset
     * bisa return false padahal folder SUDAH berisi img/, menyebabkan
     * copy berulang atau fallback ke master.
     */
    private function editedHasAsset(string $editedPath): bool
    {
        $absPath = $this->storagePublicPath($editedPath);
        if (! is_dir($absPath)) return false;
        if (file_exists($absPath . DIRECTORY_SEPARATOR . 'style.css')) return true;
        if (is_dir($absPath . DIRECTORY_SEPARATOR . 'images')) return true;
        if (is_dir($absPath . DIRECTORY_SEPARATOR . 'img')) return true; // singular
        if (is_dir($absPath . DIRECTORY_SEPARATOR . 'assets')) return true;
        if (is_dir($absPath . DIRECTORY_SEPARATOR . 'css')) return true;
        return false;
    }

    /**
     * Resolve folder berisi login.html di master. Bisa di root atau sub-folder.
     * Return absolute path ke folder yang berisi login.html.
     *
     * TUJUAN: ZIP entries tanpa prefix sub-folder. Kalau master = "templates/4/original"
     * dan login.html ada di "templates/4/original/mikrotik-standard-template/login.html",
     * maka folder ini return path ke "mikrotik-standard-template/" (bukan "original/").
     * Hasilnya ZIP entries: "login.html", "style.css", "images/logo.svg", dll —
     * siap di-upload ke MikroTik tanpa extract+pindahkan file.
     *
     * Path dengan spasi (mis. "loginpage 1") aman karena pakai realpath().
     */
    private function resolveTemplateFolder(string $masterPath): string
    {
        $absMaster = $this->storagePublicPath($masterPath);
        $realMaster = realpath($absMaster);
        if ($realMaster === false) {
            return $absMaster;
        }

        // Cek login.html di root master
        if (file_exists($realMaster . DIRECTORY_SEPARATOR . 'login.html')) {
            return $realMaster;
        }

        // Scan rekursif untuk cari folder berisi login.html
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($realMaster, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === 'login.html') {
                return dirname($file->getRealPath());
            }
        }

        // Fallback ke master root
        return $realMaster;
    }

    /**
     * Copy asset (style.css, images/, assets/, status.html, logout.html)
     * dari folder master ke folder draft edited. Dipakai sebagai safety net
     * kalau draft edited ada tapi CUMA login.html (mis. draft lama sebelum
     * Patch 1). Idempotent — kalau file sudah ada di edited, skip.
     *
     * TUJUAN: pastikan ZIP yang di-download berisi SEMUA asset template,
     * bukan hanya login.html hasil edit.
     *
     * Pakai native PHP copy + mkdir (bukan Storage facade) karena:
     *   - Lebih reliable di Windows (Storage::put() kadang silent fail)
     *   - mkdir(true, ...) rekursif untuk sub-folder
     *   - copy() lebih cepat dari file_get_contents + put
     */
    private function copyAssetsFromMaster(Template $template, string $editedPath): void
    {
        $folder = $template->zip_file;
        if (! $folder || ! Storage::disk('public')->exists($folder)) {
            return;
        }

        // Resolve folder master berisi login.html (handle sub-folder & spasi)
        $masterLoginDir = $this->resolveTemplateFolder($folder);
        if (! is_dir($masterLoginDir)) return;

        // Absolute path ke edited folder (target copy)
        $editedAbs = $this->storagePublicPath($editedPath);

        // Copy semua file master KECUALI login.html (sudah ada & sudah di-edit)
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($masterLoginDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            if (! $file->isFile()) continue;
            $realFile = $file->getRealPath();
            if ($realFile === false) continue;
            if (basename($realFile) === 'login.html') continue;
            // Skip __MACOSX & ._file macOS metadata
            if (strpos($realFile, '__MACOSX') !== false) continue;
            if (strpos(basename($realFile), '._') === 0) continue;

            $rel = str_replace($masterLoginDir, '', $realFile);
            $rel = str_replace('\\', '/', $rel);
            $rel = ltrim($rel, '/');
            if ($rel === '') continue;

            $dstAbs = $editedAbs . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);

            // Skip kalau file sudah ada (idempotent — kalau user edit style.css,
            // kita tidak overwrite)
            if (file_exists($dstAbs)) continue;

            // Buat parent directory secara rekursif (kalau belum ada)
            $dstDir = dirname($dstAbs);
            if (! is_dir($dstDir)) {
                @mkdir($dstDir, 0755, true);
            }

            // Copy file langsung — lebih reliable dari Storage::put
            if (! @copy($realFile, $dstAbs)) {
                // Fallback ke file_get_contents + file_put_contents
                $content = @file_get_contents($realFile);
                if ($content !== false) {
                    @file_put_contents($dstAbs, $content);
                }
            }
        }
    }
}
