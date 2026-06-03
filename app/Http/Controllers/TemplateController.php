<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HotspotConfig;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;

class TemplateController extends Controller
{
    public function edit(Request $request)
    {
        $config = HotspotConfig::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['business_name' => 'WIFI HOTSPOT']
        );
        return view('template.edit', compact('config'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'running_text' => 'nullable|string',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048'
        ]);

        $config = HotspotConfig::where('user_id', $request->user()->id)->firstOrFail();
        $config->business_name = $request->business_name;
        $config->running_text = $request->running_text;

        if ($request->hasFile('logo')) {
            if ($config->logo_path) {
                Storage::delete('public/' . $config->logo_path);
            }
            $path = $request->file('logo')->store('logos', 'public');
            $config->logo_path = $path;
        }

        $config->save();

        return redirect()->route('template.edit')->with('success', 'Konfigurasi berhasil disimpan!');
    }

    public function download(Request $request)
    {
        $config = HotspotConfig::where('user_id', $request->user()->id)->firstOrFail();

        $masterPath = storage_path('app/master_template');
        if (!File::exists($masterPath)) {
            return back()->with('error', 'Master template belum tersedia di server.');
        }

        $tempPath = storage_path('app/temp_custom_' . $request->user()->id);
        
        if (File::exists($tempPath)) {
            File::deleteDirectory($tempPath);
        }
        File::copyDirectory($masterPath, $tempPath);

        $files = File::allFiles($tempPath);
        foreach ($files as $file) {
            if ($file->getExtension() == 'html' || $file->getExtension() == 'txt') {
                $content = File::get($file->getPathname());
                $content = str_replace('{{BUSINESS_NAME}}', $config->business_name, $content);
                $content = str_replace('{{RUNNING_TEXT}}', $config->running_text ?? 'Selamat Datang di Hotspot Kami', $content);
                File::put($file->getPathname(), $content);
            }
        }

        if ($config->logo_path) {
            $userLogoPath = storage_path('app/public/' . $config->logo_path);
            if (File::exists($userLogoPath)) {
                // Asumsi file asli bernama logo.png di dalam folder root template
                File::copy($userLogoPath, $tempPath . '/logo.png');
            }
        }

        $zipPath = storage_path('app/Template_Hotspot_' . preg_replace('/[^A-Za-z0-9\-]/', '', $config->business_name) . '.zip');
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $files = File::allFiles($tempPath);
            foreach ($files as $file) {
                $relativePath = str_replace($tempPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $zip->addFile($file->getPathname(), $relativePath);
            }
            $zip->close();
        } else {
            return back()->with('error', 'Gagal membuat file ZIP.');
        }

        File::deleteDirectory($tempPath);

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
