<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

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
     *   1. Salinan edit user di `orders/{user_id}/{id}/` (kalau ada)
     *   2. Master template di `zip_file` (fallback)
     *
     * Hasil ZIP: login.html, status.html, logout.html, style.css, dan assets
     * siap upload ke MikroTik.
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
            // Simpan intended URL supaya setelah payment kembali ke editor/download
            $request->session()->put('url.intended', "/template/{$id}/download");

            // XHR (dari editor) → return 402 JSON supaya frontend bisa navigate SPA
            // Browser normal → 302 redirect ke halaman checkout
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

        // 1) Prioritas: salinan edit user
        $editedPath = "templates/orders/{$userId}/{$id}";
        $editedLoginFile = "{$editedPath}/login.html";
        if (Storage::disk('public')->exists($editedLoginFile)) {
            // Safety net: kalau edited folder ada tapi CUMA login.html
            // (mis. dari versi lama sebelum Patch 1), copy asset dari master
            // supaya ZIP berisi full template, bukan 1 file saja.
            $editedStyleFile = "{$editedPath}/style.css";
            if (! Storage::disk('public')->exists($editedStyleFile)) {
                $this->copyAssetsFromMaster($template, $editedPath);
            }
            $srcPath = Storage::disk('public')->path($editedPath);
        } else {
            // 2) Fallback: master template
            // PENTING: pakai folder berisi login.html sebagai srcPath, BUKAN root
            // zip_file. Kalau master zip_file = "templates/4/original" tapi login.html
            // ada di sub-folder "mikrotik-standard-template/", maka srcPath harus
            // sub-folder itu. Kalau pakai root, ZIP entries akan punya prefix
            // "mikrotik-standard-template/" yang bukan struktur yg user inginkan.
            $folder = $template->zip_file;
            if ($folder && Storage::disk('public')->exists($folder)) {
                $srcPath = $this->resolveTemplateFolder($folder);
            } else {
                $srcPath = storage_path('app/master_template');
            }
        }

        if (!File::exists($srcPath)) {
            return back()->with('error', 'File template tidak ditemukan.');
        }

        // Cek zip extension aktif
        if (!class_exists('ZipArchive')) {
            return back()->with('error', 'PHP zip extension belum aktif. Hubungi admin.');
        }

        // Build archive — tambahkan suffix "_edited" kalau dari salinan user
        // File tetap format ZIP standard (semua OS bisa extract via WinRAR/7-Zip/Explorer),
        // suffix ".rar" untuk konsistensi dengan permintaan user. Include template ID
        // di filename supaya jelas "berdasarkan id template yang sedang diedit".
        $suffix = Storage::disk('public')->exists($editedLoginFile) ? '_edited' : '';
        $safeName = preg_replace('/[^A-Za-z0-9\-]/', '_', $template->name);
        $zipFileName = 'Template_ID' . $template->id . '_' . $safeName . $suffix . '.rar';
        $zipPath = storage_path('app/' . $zipFileName);
        if (file_exists($zipPath)) {
            @unlink($zipPath);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $files = File::allFiles($srcPath);
            foreach ($files as $file) {
                $relative = str_replace($srcPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                // Normalize path separator untuk cross-platform ZIP
                $relative = str_replace('\\', '/', $relative);
                $zip->addFile($file->getPathname(), $relative);
            }
            $zip->close();
            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Gagal membuat file ZIP.');
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
     */
    private function resolveTemplateFolder(string $masterPath): string
    {
        // Cek di root dulu
        $rootLogin = $masterPath . '/login.html';
        if (Storage::disk('public')->exists($rootLogin)) {
            return Storage::disk('public')->path($masterPath);
        }

        // Scan rekursif untuk cari folder berisi login.html
        foreach (Storage::disk('public')->allFiles($masterPath) as $candidate) {
            if (basename($candidate) === 'login.html') {
                return Storage::disk('public')->path(dirname($candidate));
            }
        }

        // Fallback ke root kalau login.html tidak ditemukan
        return Storage::disk('public')->path($masterPath);
    }

    /**
     * Copy asset (style.css, images/, assets/, status.html, logout.html)
     * dari folder master ke folder draft edited. Dipakai sebagai safety net
     * kalau draft edited ada tapi CUMA login.html (mis. draft lama sebelum
     * Patch 1). Idempotent — kalau file sudah ada di edited, skip.
     *
     * TUJUAN: pastikan ZIP yang di-download berisi SEMUA asset template,
     * bukan hanya login.html hasil edit.
     */
    private function copyAssetsFromMaster(Template $template, string $editedPath): void
    {
        $folder = $template->zip_file;
        if (! $folder || ! Storage::disk('public')->exists($folder)) {
            return;
        }

        // Cari folder berisi login.html di master (root atau sub-folder)
        $masterLoginFile = $folder . '/login.html';
        if (! Storage::disk('public')->exists($masterLoginFile)) {
            foreach (Storage::disk('public')->allFiles($folder) as $candidate) {
                if (basename($candidate) === 'login.html') {
                    $masterLoginFile = $candidate;
                    break;
                }
            }
        }
        $assetSrcDir = dirname($masterLoginFile);

        // Copy semua file master KECUALI login.html (sudah ada & sudah di-edit)
        foreach (Storage::disk('public')->allFiles($assetSrcDir) as $srcFile) {
            if (basename($srcFile) === 'login.html') continue;
            $rel = substr($srcFile, strlen($assetSrcDir) + 1);
            $dstFile = $editedPath . '/' . $rel;
            // Skip kalau file sudah ada (idempotent)
            if (Storage::disk('public')->exists($dstFile)) continue;
            Storage::disk('public')->makeDirectory(dirname($dstFile));
            Storage::disk('public')->put($dstFile, Storage::disk('public')->get($srcFile));
        }
    }
}
