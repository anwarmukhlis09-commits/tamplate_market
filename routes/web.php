<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => app()->version(),
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/katalog', function () {
    return Inertia::render('Catalog');
});

Route::get('/template/{id}', function ($id) {
    return Inertia::render('TemplateDetail', ['id' => $id, 'canLogin' => Route::has('login')]);
});

Route::get('/template/{id}/edit', function ($id) {
    return Inertia::render('EditTemplate', [
        'id' => $id,
        'canLogin' => Route::has('login'),
    ]);
})->name('template.editor');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Template Generator Routes
    Route::get('/template', [TemplateController::class, 'edit'])->name('template.edit');
    Route::post('/template', [TemplateController::class, 'update'])->name('template.update');
    Route::get('/template/download', [TemplateController::class, 'download'])->name('template.download');
});

require __DIR__.'/auth.php';
