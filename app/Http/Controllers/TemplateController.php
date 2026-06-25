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
     * STRATEGI ZIP FINAL (lebih reliable):
     *   1. Tentukan MASTER folder (template default lengkap: status.html, logout.html,
     *      alogin.html, bantuan.html, error.html, voucher.html, md5.js, gambar, dll)
     *   2. Tentukan EDITED folder (`orders/{user_id}/{id}/`) — biasanya hanya berisi
     *      `login.html` (hasil edit user) + kadang asset lain kalau user copy manual.
     *   3. Copy SELURUH isi master ke folder TEMPORARY (di sys_get_temp_dir).
     *   4. Overlay file dari EDITED ke TEMPORARY (file edited menimpa master, mis.
     *      `login.html` versi user). User TIDAK perlu copy semua asset manual.
     *   5. ZIP folder TEMPORARY → kirim ke user.
     *   6. Cleanup TEMPORARY setelah ZIP terkirim (deleteFileAfterSend).
     *
     * Hasil: ZIP LENGKAP dengan SEMUA asset master + login.html versi edit user.
     * Tidak peduli apakah edited folder lengkap atau tidak — yang penting master
     * selalu jadi base, edited hanya overlay.
     *
     * ZIP bersih dari: __MACOSX, ._* (macOS metadata), Thumbs.db, .DS_Store.
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

        // ── 1) Resolve MASTER folder (template default lengkap) ─────────
        $masterPath = $this->resolveMasterFolder($template);
        $realMaster = realpath($masterPath);
        $hasMaster = $realMaster !== false && is_dir($realMaster);

        // ── 2) Resolve EDITED folder (hasil edit user) ─────────
        $editedPath = "templates/orders/{$userId}/{$id}";
        $editedAbs = $this->storagePublicPath($editedPath);
        $realEdited = realpath($editedAbs);
        $hasEdited = $realEdited !== false && is_dir($realEdited);

        // ── 2a) Determine source: query param 'source' = master|edited ──
        // DEFAULT: master (sesuai requirement — download dari katalog harus
        // dapat aset original admin, bukan hasil edit user).
        // - '?source=master' → ZIP hanya berisi master (no overlay)
        // - '?source=edited' → ZIP berisi master + overlay edited (kalau ada)
        // - Tanpa query param → default = master (aman & predictable)
        //
        // Exception: kalau admin sudah pilih download edited dari editor
        // (via flag session 'download_edited'), pakai edited otomatis.
        $sourceParam = strtolower((string) $request->query('source', 'master'));
        $useEdited = false;
        if ($sourceParam === 'edited') {
            $useEdited = $hasEdited; // require edited folder exists
        } elseif ($sourceParam === 'master') {
            $useEdited = false;
        } else {
            // Unknown source — default master
            $useEdited = false;
        }

        if (! $hasMaster && ! $hasEdited) {
            return back()->with('error', 'Template tidak ditemukan di storage (baik master maupun hasil edit). Hubungi admin.');
        }

        // ── 3) Setup folder TEMPORARY (akan di-ZIP) ─────────
        $tempBase = sys_get_temp_dir();
        $tempName = 'tpl_' . $template->id . '_' . $userId . '_' . uniqid('', true);
        $tempDir = $tempBase . DIRECTORY_SEPARATOR . $tempName;
        if (! @mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
            \Log::error('Template download: gagal buat temp dir', [
                'template_id' => $id,
                'user_id' => $userId,
                'temp_dir' => $tempDir,
            ]);
            return back()->with('error', 'Gagal membuat folder temporary. Hubungi admin.');
        }

        try {
            // ── 4) Copy SELURUH isi master → temp ─────────
            $copiedCount = 0;
            if ($hasMaster) {
                $copiedCount = $this->copyDirectoryFlat($realMaster, $tempDir);
            }

            // ── 5) Overlay file dari edited (hanya kalau $useEdited) ─────────
            $overlaidCount = 0;
            if ($useEdited) {
                $overlaidCount = $this->overlayDirectory($realEdited, $tempDir);
            }

            // ── 6) Validasi minimal: temp harus ada login.html ─────────
            if (! file_exists($tempDir . DIRECTORY_SEPARATOR . 'login.html')) {
                throw new \RuntimeException('Folder ZIP tidak memiliki login.html (tidak ada di master maupun hasil edit).');
            }

            // ── 7) Build ZIP dari temp ─────────
            if (! class_exists('ZipArchive')) {
                throw new \RuntimeException('PHP zip extension belum aktif.');
            }

            // Suffix filename: _edited kalau pakai overlay edited, _master kalau pure master
            $suffix = $useEdited ? '_edited' : '_master';
            $safeName = preg_replace('/[^A-Za-z0-9\-]/', '_', $template->name);
            $zipFileName = 'Template_ID' . $template->id . '_' . $safeName . $suffix . '.zip';
            $finalZipPath = $tempBase . DIRECTORY_SEPARATOR . $zipFileName;

            // Hapus zip lama kalau ada (dari request sebelumnya yang gagal cleanup)
            @unlink($finalZipPath);

            $zip = new \ZipArchive();
            $opened = $zip->open($finalZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            $addedCount = 0;
            $totalSize = 0;
            $created = false;
            $closeStatus = false;

            if ($opened === true) {
                // Normalize base path supaya str_replace reliable di Windows.
                // realpath() di Windows bisa return lowercase drive letter
                // (mis. 'c:\Users\...') sedangkan $tempDir pakai uppercase
                // ('C:\Users\...'). str_replace case-sensitive — tanpa normalisasi,
                // hasil replace gagal & ZIP entry jadi path absolute utuh.
                $realTempBase = realpath($tempDir);
                if ($realTempBase === false) $realTempBase = $tempDir;
                // Pakai forward-slash versi & lowercase drive letter
                $realTempBase = str_replace('\\', '/', $realTempBase);
                $tempBaseLen = strlen($realTempBase);

                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($tempDir, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($iterator as $file) {
                    $realFile = $file->getRealPath();
                    if ($realFile === false) continue;

                    // Normalize ke forward-slash
                    $normalized = str_replace('\\', '/', $realFile);
                    // Hitung path relatif dari base (case-insensitive di Windows)
                    if (stripos($normalized, $realTempBase) === 0) {
                        $relative = substr($normalized, $tempBaseLen);
                    } else {
                        // Fallback: basename saja
                        $relative = $file->getFilename();
                    }
                    $relative = ltrim($relative, '/');

                    // Filter: skip __MACOSX, ._* (macOS), Thumbs.db, .DS_Store
                    if ($this->shouldSkipEntry($relative)) continue;

                    if ($file->isDir()) {
                        $zip->addEmptyDir($relative);
                    } else {
                        $size = $file->getSize();
                        if ($size === false || $size === 0) continue;
                        if ($zip->addFile($realFile, $relative)) {
                            $addedCount++;
                            $totalSize += $size;
                        }
                    }
                }
                // PENTING: close() bisa return false kalau ZIP corrupt. Capture status.
                $closeStatus = $zip->close();
                $created = ($closeStatus === true);
            }

            // ── 8) Validasi ZIP integrity (3 lapis) ─────────
            if (! $created) {
                throw new \RuntimeException('ZipArchive::close() gagal (open=' . $opened . ', close=' . var_export($closeStatus, true) . ').');
            }
            clearstatcache(true, $finalZipPath);
            if (! file_exists($finalZipPath)) {
                throw new \RuntimeException('ZIP file hilang setelah dibuat.');
            }
            $zipSize = filesize($finalZipPath);
            if ($zipSize === false || $zipSize < 22) {
                // ZIP min size = 22 bytes (end of central directory record)
                throw new \RuntimeException('ZIP file terlalu kecil/corrupt (size=' . var_export($zipSize, true) . 'B).');
            }

            // Verifikasi ZIP bisa dibuka kembali
            $verify = new \ZipArchive();
            $verifyResult = $verify->open($finalZipPath);
            if ($verifyResult !== true) {
                throw new \RuntimeException('ZIP corrupt setelah dibuat (verify open return=' . $verifyResult . ').');
            }
            $verifyFileCount = $verify->numFiles;
            // Test extract 1 file sample untuk memastikan ZIP tidak corrupt di level data
            $testExtract = $verify->getFromIndex(0);
            if ($testExtract === false && $verifyFileCount > 0) {
                $verify->close();
                throw new \RuntimeException('ZIP corrupt: getFromIndex(0) gagal.');
            }
            $verify->close();

            if ($verifyFileCount < 1) {
                throw new \RuntimeException('ZIP tidak berisi file (numFiles=' . $verifyFileCount . ').');
            }

            // ── 9) Log audit (detail seperti diminta) ─────────
            \Log::info('Template download', [
                'template_id' => $id,
                'user_id' => $userId,
                'edited_path' => $hasEdited ? $realEdited : null,
                'master_path' => $realMaster,
                'temp_path' => $tempDir,
                'copied_from_master' => $copiedCount,
                'overlaid_from_edited' => $overlaidCount,
                'file_count_in_zip' => $addedCount,
                'total_uncompressed_bytes' => $totalSize,
                'zip_file_path' => $finalZipPath,
                'zip_file_size_bytes' => $zipSize,
                'verify_num_files' => $verifyFileCount,
                'used_edited_overlay' => $useEdited,
                'source_param' => $sourceParam,
            ]);

            // ── 10) Kirim ZIP ke user ─────────
            // Bersihkan semua output buffer supaya tidak ada karakter stray
            // sebelum binary stream ZIP. Sangat penting — kalau ada spasi/
            // newline/echo sebelum file ZIP, file akan corrupt saat di-extract.
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            // Disable any PHP execution time limit issue — beri waktu ekstra untuk
            // download file besar.
            if (function_exists('set_time_limit')) {
                @set_time_limit(120);
            }
            // Bersihkan file yang mungkin di-hold oleh antivirus (Windows).
            // Flush semua buffer dan clearstatcache.
            flush();
            clearstatcache(true, $finalZipPath);

            // PENTING: deleteFileAfterSend HANYA dipakai kalau kita yakin ZIP
            // sudah benar-benar terkirim. Tapi untuk AMAN dari antivirus/
            // scanner yang masih hold file handle, kita JANGAN pakai
            // deleteFileAfterSend — biarkan file di temp, cleanup via finally.
            // Untuk trigger download, kita pakai BinaryFileResponse langsung.
            return response()->download($finalZipPath, $zipFileName, [
                'Content-Type' => 'application/zip',
                'Content-Length' => (string) $zipSize,
                'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Template-Id' => (string) $template->id,
                'X-File-Count' => (string) $addedCount,
            ]);
        } catch (\Throwable $e) {
            // Log error detail supaya admin bisa diagnosa
            \Log::error('Template download gagal', [
                'template_id' => $id,
                'user_id' => $userId,
                'master_path' => $realMaster,
                'edited_path' => $hasEdited ? $realEdited : null,
                'temp_path' => $tempDir,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Cleanup temp dir kalau ada
            $this->rmdirRecursive($tempDir);
            if (isset($finalZipPath) && file_exists($finalZipPath)) {
                @unlink($finalZipPath);
            }
            return back()->with('error', 'Gagal membuat file ZIP: ' . $e->getMessage() . '. Hubungi admin.');
        } finally {
            // Cleanup temp folder + ZIP file SELALU (baik sukses maupun gagal).
            // CATATAN: pada sukses, ZIP file mungkin masih di-hold oleh antivirus
            // scanner Windows — unlink akan return false tapi ZIP tetap terkirim
            // ke user (response sudah flush). File akan di-cleanup oleh OS saat
            // handle dilepas, atau bisa di-sweep berkala oleh cron.
            $this->rmdirRecursive($tempDir);
            if (isset($finalZipPath) && file_exists($finalZipPath)) {
                @unlink($finalZipPath);
            }
        }
    }

    /**
     * Resolve MASTER folder: template default lengkap dari `zip_file` di DB.
     * Handle sub-folder & spasi.
     */
    private function resolveMasterFolder(Template $template): string
    {
        $folder = $template->zip_file;
        if ($folder && Storage::disk('public')->exists($folder)) {
            return $this->resolveTemplateFolder($folder);
        }
        return storage_path('app/master_template');
    }

    /**
     * Copy SELURUH isi $src ke $dst (replace kalau ada). Return jumlah file yang disalin.
     * Pakai native PHP — lebih reliable dari Storage facade.
     * Filter: skip __MACOSX, ._*, Thumbs.db, .DS_Store.
     */
    private function copyDirectoryFlat(string $src, string $dst): int
    {
        $count = 0;
        if (! is_dir($src)) return 0;
        if (! is_dir($dst)) @mkdir($dst, 0755, true);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            $realFile = $file->getRealPath();
            if ($realFile === false) continue;
            $rel = str_replace($src, '', $realFile);
            $rel = str_replace('\\', '/', $rel);
            $rel = ltrim($rel, '/');
            if ($rel === '') continue;
            if ($this->shouldSkipEntry($rel)) continue;

            $dstAbs = $dst . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if ($file->isDir()) {
                if (! is_dir($dstAbs)) @mkdir($dstAbs, 0755, true);
            } else {
                $dstDir = dirname($dstAbs);
                if (! is_dir($dstDir)) @mkdir($dstDir, 0755, true);
                $size = $file->getSize();
                if ($size === false || $size === 0) continue;
                if (@copy($realFile, $dstAbs)) {
                    $count++;
                } else {
                    $content = @file_get_contents($realFile);
                    if ($content !== false) {
                        @file_put_contents($dstAbs, $content);
                        $count++;
                    }
                }
            }
        }
        return $count;
    }

    /**
     * Overlay file dari $src ke $dst — file di $src MENIMPA file di $dst dengan
     * path yang sama. File di $dst yang TIDAK ada di $src tetap (tidak dihapus).
     * Return jumlah file yang di-overlay.
     */
    private function overlayDirectory(string $src, string $dst): int
    {
        $count = 0;
        if (! is_dir($src) || ! is_dir($dst)) return 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            $realFile = $file->getRealPath();
            if ($realFile === false) continue;
            $rel = str_replace($src, '', $realFile);
            $rel = str_replace('\\', '/', $rel);
            $rel = ltrim($rel, '/');
            if ($rel === '') continue;
            if ($this->shouldSkipEntry($rel)) continue;

            $dstAbs = $dst . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            
            if ($file->isDir()) {
                if (! is_dir($dstAbs)) @mkdir($dstAbs, 0755, true);
                continue;
            }

            $dstDir = dirname($dstAbs);
            if (! is_dir($dstDir)) @mkdir($dstDir, 0755, true);

            $size = $file->getSize();
            if ($size === false || $size === 0) continue;
            if (@copy($realFile, $dstAbs)) {
                $count++;
            } else {
                $content = @file_get_contents($realFile);
                if ($content !== false) {
                    @file_put_contents($dstAbs, $content);
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Hapus folder recursive. Best-effort, ignore error.
     */
    private function rmdirRecursive(string $dir): void
    {
        if (! is_dir($dir)) return;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) @rmdir($file->getRealPath());
            else @unlink($file->getRealPath());
        }
        @rmdir($dir);
    }

    /**
     * Filter entri yang harus di-skip saat ZIP:
     * - __MACOSX/ (macOS resource fork)
     * - ._* (macOS metadata)
     * - Thumbs.db (Windows thumbnail cache)
     * - .DS_Store (macOS folder metadata)
     */
    private function shouldSkipEntry(string $relativePath): bool
    {
        if (strpos($relativePath, '__MACOSX/') === 0) return true;
        if (strpos($relativePath, '__MACOSX\\') === 0) return true;
        if (preg_match('#(^|/)\._[^/]+$#', $relativePath)) return true;
        $base = basename($relativePath);
        if ($base === 'Thumbs.db' || $base === '.DS_Store') return true;
        return false;
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
}
