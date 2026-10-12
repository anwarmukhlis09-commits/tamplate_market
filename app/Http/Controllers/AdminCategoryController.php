<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminCategoryController extends Controller
{
    /**
     * Tampilkan halaman manajemen kategori admin.
     */
    public function index(): Response
    {
        $categories = Category::query()
            ->withCount('templates')
            ->orderBy('id', 'asc')
            ->get();

        return Inertia::render('Admin/Categories', [
            'categories' => $categories,
        ]);
    }

    /**
     * Simpan kategori baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:categories,slug'],
            'icon' => ['nullable', 'string'],
            'color' => ['required', 'string', 'max:100'],
        ]);

        $slug = !empty($validated['slug']) 
            ? Str::slug($validated['slug']) 
            : Str::slug($validated['name']);

        // Pastikan slug unik jika auto-generated
        $originalSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        // Default icon jika kosong
        $icon = !empty($validated['icon']) 
            ? $validated['icon'] 
            : 'M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z';

        Category::create([
            'name' => trim($validated['name']),
            'slug' => $slug,
            'icon' => $icon,
            'color' => $validated['color'],
        ]);

        return redirect()->back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    /**
     * Perbarui data kategori yang sudah ada.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', Rule::unique('categories', 'slug')->ignore($category->id)],
            'icon' => ['nullable', 'string'],
            'color' => ['required', 'string', 'max:100'],
        ]);

        $newSlug = Str::slug($validated['slug']);
        $oldSlug = $category->slug;

        // Jika slug berubah, perbarui juga kolom category pada template terkait agar relasi tidak putus
        if ($oldSlug !== $newSlug) {
            Template::where('category', $oldSlug)->update(['category' => $newSlug]);
        }

        $category->update([
            'name' => trim($validated['name']),
            'slug' => $newSlug,
            'icon' => $validated['icon'] ?: $category->icon,
            'color' => $validated['color'],
        ]);

        return redirect()->back()->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Hapus kategori dari database.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $templateCount = Template::where('category', $category->slug)->count();

        if ($templateCount > 0) {
            return redirect()->back()->with('error', "Kategori '{$category->name}' tidak dapat dihapus karena masih digunakan oleh {$templateCount} template.");
        }

        $category->delete();

        return redirect()->back()->with('success', "Kategori '{$category->name}' berhasil dihapus.");
    }
}
