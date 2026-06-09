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
     */
    public function download(Request $request, int $id)
    {
        $template = Template::findOrFail($id);

        // Resolve path folder template (dari zip_file)
        $folder = $template->zip_file;
        $srcPath = $folder && Storage::disk('public')->exists($folder)
            ? Storage::disk('public')->path($folder)
            : storage_path('app/master_template');

        if (!File::exists($srcPath)) {
            return back()->with('error', 'File template tidak ditemukan.');
        }

        $zipFileName = 'Template_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $template->name) . '.zip';

        // Stream ZIP pakai ZipArchive (extension zip harus enabled)
        if (!class_exists('ZipArchive')) {
            return back()->with('error', 'PHP zip extension belum aktif. Hubungi admin.');
        }

        $zipPath = storage_path('app/' . $zipFileName);
        if (file_exists($zipPath)) {
            @unlink($zipPath);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $files = File::allFiles($srcPath);
            foreach ($files as $file) {
                $relative = str_replace($srcPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $zip->addFile($file->getPathname(), $relative);
            }
            $zip->close();
            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Gagal membuat file ZIP.');
    }
}
