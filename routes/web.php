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
        'templates' => \App\Models\Template::where('status', 'published')
            ->orderBy('sold_count', 'desc')
            ->take(6)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'category' => $t->category,
                'price' => $t->price,
                'discountPrice' => $t->discount_price,
                'badge' => $t->badge,
                'features' => $t->features ?? [],
                'shortDesc' => $t->short_desc,
                'image' => $t->preview_gradients[0] ?? 'bg-gradient-to-br from-indigo-500 to-purple-500',
                'imageUrl' => $t->preview_image ? asset('storage/' . $t->preview_image) : null,
                'rating' => (float) $t->rating,
                'sold' => $t->sold_count,
            ]),
    ]);
});

Route::get('/katalog', function () {
    $templates = \App\Models\Template::where('status', 'published')
        ->orderBy('sold_count', 'desc')
        ->get()
        ->map(fn($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'slug' => $t->slug,
            'category' => $t->category,
            'price' => $t->price,
            'discountPrice' => $t->discount_price,
            'badge' => $t->badge,
            'features' => $t->features ?? [],
            'shortDesc' => $t->short_desc,
            'longDesc' => $t->long_desc,
            'image' => $t->preview_gradients[0] ?? 'bg-gradient-to-br from-indigo-500 to-purple-500',
            'imageUrl' => $t->preview_image ? asset('storage/' . $t->preview_image) : null,
            'rating' => (float) $t->rating,
            'sold' => $t->sold_count,
        ])
        // ID-based: hanya data dari DB, no local cache, no fallback
        ->values()
        ->all();

    // Render Inertia + set Cache-Control via response
    $inertiaResponse = \Inertia\Inertia::render('Catalog', [
        'templates' => $templates,
        'fetchedAt' => now()->timestamp,
    ]);

    // Convert ke HTTP response dan tambahkan Cache-Control
    $httpResponse = $inertiaResponse->toResponse(request());
    $httpResponse->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
    $httpResponse->headers->set('Pragma', 'no-cache');
    $httpResponse->headers->set('Expires', '0');
    return $httpResponse;
});

// Download template sebagai ZIP (scope ke {id}) — HARUS dideklarasikan
// SEBELUM /template/{id} supaya route match spesifik duluan.
Route::get('/template/{id}/download', [\App\Http\Controllers\TemplateController::class, 'download'])
    ->middleware('auth')
    ->name('template.download');

Route::get('/template/{id}', function ($id) {
    $t = \App\Models\Template::findOrFail($id);

    // Template serupa: 3 published templates dari category sama (exclude current)
    $related = \App\Models\Template::where('category', $t->category)
        ->where('id', '!=', $t->id)
        ->where('status', 'published')
        ->take(3)
        ->get();
    // Fallback: kalau tidak ada di category sama, ambil 3 published random
    if ($related->isEmpty()) {
        $related = \App\Models\Template::where('id', '!=', $t->id)
            ->where('status', 'published')
            ->inRandomOrder()
            ->take(3)
            ->get();
    }
    $relatedMapped = $related->map(fn($rt) => [
        'id' => $rt->id,
        'name' => $rt->name,
        'category' => $rt->category,
        'price' => $rt->price,
        'imageUrl' => $rt->preview_image ? asset('storage/' . $rt->preview_image) : null,
    ])->all();

    return Inertia::render('TemplateDetail', [
        'template' => [
            'id' => $t->id,
            'name' => $t->name,
            'slug' => $t->slug,
            'category' => $t->category,
            'shortDesc' => $t->short_desc,
            'longDesc' => $t->long_desc,
            'price' => $t->price,
            'discountPrice' => $t->discount_price,
            'badge' => $t->badge,
            'features' => $t->features,
            'previewImageUrl' => $t->preview_image ? asset('storage/' . $t->preview_image) : null,
            'previews' => $t->preview_gradients ?? ['bg-gradient-to-br from-indigo-500 to-purple-500'],
            'whatsIncluded' => ['File HTML/CSS/JS lengkap', 'Panduan instalasi', 'File MikroTik hotspot', 'Free update 1 tahun'],
            'rating' => (float) $t->rating,
            'sold' => $t->sold_count,
            'updatedAt' => $t->updated_at->format('Y-m-d'),
            'allowEdit' => $t->allow_edit_before_checkout,
        ],
        'relatedTemplates' => $relatedMapped,
        'canLogin' => Route::has('login'),
    ]);
});

// ── Fullscreen Preview (no iframe chrome, ESC to exit) ─────
Route::get('/template/{id}/fullscreen', function ($id) {
    $t = \App\Models\Template::findOrFail($id);
    return Inertia::render('Template/Fullscreen', [
        'template' => [
            'id' => $t->id,
            'name' => $t->name,
            'slug' => $t->slug,
        ],
    ]);
})->name('template.fullscreen');

// ── Direct Preview (buka file HTML asli, no iframe, no Inertia) ─────────
// Contoh: /preview/game-zone/login.html
//         /preview/coffee-shop/status.html
Route::get('/preview/{slug}/{file?}', function ($slug, $file = 'login.html') {
    $t = \App\Models\Template::where('slug', $slug)->firstOrFail();

    // Whitelist file yang boleh diakses (security: cegah path traversal)
    $allowed = ['login.html', 'status.html', 'logout.html', 'error.html',
                'alogin.html', 'rlogin.html', 'redirect.html', 'radvert.html'];
    if (!in_array($file, $allowed)) {
        abort(404, 'File not found');
    }

    // Cari file: 1) folder template, 2) public/storage (default MikroTik), 3) master template
    $filePath = null;
    if ($t->zip_file && \Storage::disk('public')->exists($t->zip_file . '/' . $file)) {
        $filePath = \Storage::disk('public')->path($t->zip_file . '/' . $file);
    } elseif (file_exists(public_path('storage/' . $t->zip_file . '/' . $file))) {
        $filePath = public_path('storage/' . $t->zip_file . '/' . $file);
    } elseif (file_exists(storage_path('app/master_template/' . $file))) {
        $filePath = storage_path('app/master_template/' . $file);
    }

    if (!$filePath) abort(404, 'Page not found: ' . $file);

    $html = file_get_contents($filePath);

    // Inject demo data + form-interceptor + relative-asset base
    $baseTag = '<base href="' . asset('storage/' . $t->zip_file) . '/">';
    $html = str_replace('<head>', "<head>\n" . $baseTag, $html);

    $demo = [
        '$(username)' => 'demo',
        '$(ip)' => '192.168.88.10',
        '$(mac)' => 'AA:BB:CC:DD:EE:FF',
        '$(uptime)' => '00:15:23',
        '$(bytes-in-nice)' => '12 MB',
        '$(bytes-out-nice)' => '30 MB',
        '$(session-time-left)' => '00:44:37',
        '$(link-login-only)' => url('/preview/' . $slug . '/alogin.html'),
        '$(link-login)' => url('/preview/' . $slug . '/login.html'),
        '$(link-logout)' => url('/preview/' . $slug . '/logout.html'),
        '$(link-status)' => url('/preview/' . $slug . '/status.html'),
        '$(link-redirect)' => url('/preview/' . $slug . '/status.html'),
        '$(link-redirect-esc)' => url('/preview/' . $slug . '/status.html'),
        '$(link-orig)' => 'http://192.168.88.1/',
        '$(location-id)' => 'demo-location',
        '$(location-name)' => 'Demo Hotspot',
        '$(error)' => 'Simulasi: username atau password salah',
        '$(hostname)' => '192.168.88.1',
        '$(popup)' => 'true',
        '$(if session-time-left)' => '', '$(endif)' => '',
        '$(if advert-pending' => '', '$(if login-by-mac' => '',
        '$(if http-status' => '', '$(if http-header' => '',
        '$(refresh-timeout-secs)' => '30',
    ];
    $custom = [
        '{{BUSINESS_NAME}}' => $t->name,
        '{{RUNNING_TEXT}}' => 'Selamat datang di ' . $t->name . '! Demo preview.',
        '{{PRIMARY_COLOR}}' => '#4F46E5',
        '{{PRIMARY_COLOR_RGB}}' => '79, 70, 229',
        '{{BG_GRADIENT}}' => 'linear-gradient(135deg, #4F46E5, #7C3AED)',
        '{{BG_COLOR1}}' => '#4F46E5',
        '{{BG_COLOR2}}' => '#7C3AED',
        '{{LOGIN_BTN_TEXT}}' => 'Login Hotspot',
        '{{FOOTER_TEXT}}' => 'Powered by MarketTemplate',
        '{{WHATSAPP}}' => '0812-3456-7890',
        '{{LOGO_URL}}' => $t->preview_image ? asset('storage/' . $t->preview_image) : 'logo.png',
        '{{SHOW_VOUCHER}}' => 'block',
        '{{VOUCHER_1_NAME}}' => '1 Jam', '{{VOUCHER_1_PRICE}}' => 'Rp 1K', '{{VOUCHER_1_DURATION}}' => '1 JAM',
        '{{VOUCHER_2_NAME}}' => '5 Jam', '{{VOUCHER_2_PRICE}}' => 'Rp 3K', '{{VOUCHER_2_DURATION}}' => '5 JAM',
        '{{VOUCHER_3_NAME}}' => '24 Jam', '{{VOUCHER_3_PRICE}}' => 'Rp 5K', '{{VOUCHER_3_DURATION}}' => '24 JAM',
    ];
    $replacements = array_merge($demo, $custom);
    $html = str_replace(array_keys($replacements), array_values($replacements), $html);

    // Rewrite href link "Logout" → langsung ke logout.html (absolute URL).
    // Pakai str_replace sederhana supaya reliable (no closure scope issue).
    // Pattern: <a ... href="$(link-logout)" ...>Logout</a>  →  <a ... href="/preview/{slug}/logout.html" ...>Logout</a>
    $logoutUrl = url('/preview/' . $t->slug . '/logout.html');
    $html = str_ireplace(
        '$(link-logout)',
        $logoutUrl,
        $html
    );

    // Add target="_top" ke SEMUA <a> link supaya escape iframe
    $html = preg_replace_callback('/<a\s+([^>]*?)>/i', function ($m) {
        $attrs = $m[1];
        if (preg_match('/\btarget\s*=/i', $attrs)) return $m[0];
        if (preg_match('/href\s*=\s*["\']#/', $attrs)) return $m[0];
        return '<a ' . $attrs . ' target="_top" rel="noopener">';
    }, $html);

    // Form & link interceptor: arahkan ke file yang sesuai
    $interceptor = <<<HTML
<script>
(function() {
    var basePath = window.location.pathname.replace(/[^\/]*$/, '');

    // Intercept semua form submit → redirect ke status.html
    document.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        window.location.href = basePath + 'status.html';
    }, true);

    // Helper: detect text dengan exact match
    function textEq(target, ...needles) {
        var t = (target.textContent || target.value || '').trim().toLowerCase();
        return needles.indexOf(t) !== -1;
    }

    // Capture phase: handle SEMUA navigation buttons
    document.addEventListener('click', function(e) {
        var target = e.target.closest('a, button, input[type="submit"], input[type="button"]');
        if (!target) return;

        // Logout / Log Off → logout.html
        if (textEq(target, 'logout', 'log out', 'logoff', 'log off')) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            window.location.href = basePath + 'logout.html';
            return;
        }

        // Login / Login Lagi → login.html (override href kalau perlu)
        if (textEq(target, 'login', 'login lagi', 'masuk', 'login kembali')) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            window.location.href = basePath + 'login.html';
            return;
        }
    }, true);
})();
</script>
HTML;
    $html = str_replace('</body>', $interceptor . "\n</body>", $html);

    // Set X-Frame-Options supaya tidak bisa di-embed (security)
    return response($html, 200)
        ->header('Content-Type', 'text/html; charset=utf-8')
        ->header('X-Frame-Options', 'SAMEORIGIN')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
})->where('file', '.*\.html$')->name('preview.direct');

// ── ID-based Preview Asset (CSS/JS/SVG/PNG/JPG) — non-HTML files ──────
// Contoh: /templates/4/preview/style.css
//         /templates/4/preview/images/logo.svg
//         /templates/4/preview/assets/script.js
//
// HTML (login.html, status.html, dll) di-handle route di bawah dengan logic
// <base> + demo MikroTik variables. Asset path relatif di HTML resolve ke
// URL yang sama (/templates/4/preview/...), jadi route ini WAJIB serve
// asset dari folder master template (bukan folder preview/ yang tidak ada).
//
// Keamanan: path dicek manual untuk cegah path traversal (../, /, dst).
// Hanya serve dari folder `templates/{id}/original/{subfolder}/*` dan
// `templates/orders/{user_id}/{id}/{subfolder}/*` (draft).
//
// PENTING: route ini harus didefinisikan SEBELUM route HTML agar tidak
// ter-match ke regex `.*\.html$` yang longgar.
Route::get('/templates/{id}/preview/{path}', function ($id, $path) {
    // SECURITY: tolak null byte, path absolut, dan karakter kontrol lebih dulu
    if (strpos($path, "\0") !== false || strpos($path, '..') !== false
        || strpos($path, '\\') !== false || strpos($path, '/') === 0) {
        abort(403, 'Invalid path');
    }
    // Hanya file dengan extension yang diizinkan (whitelist)
    $allowedExt = ['css', 'js', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'ico',
                   'woff', 'woff2', 'ttf', 'otf', 'eot', 'map', 'json', 'txt', 'md'];
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        abort(404, 'Asset type not allowed: ' . $ext);
    }

    $t = \App\Models\Template::findOrFail($id);

    // Resolve file: cek folder master DAN folder draft user (kalau ada).
    // 1) Master: templates/{id}/original/<subfolder>/{path}
    // 2) Draft user: templates/orders/{user_id}/{id}/<subfolder>/{path}
    //
    // Strategi: scan folder master templates/{id}/original/, cari file yang
    // path-nya diakhiri dengan $path. Ini handle semua struktur folder
    // (mikrotik-standard-template/style.css, images/logo.svg, dst).
    $candidates = [];
    $masterBase = "templates/{$t->id}/original";
    if (\Storage::disk('public')->exists($masterBase)) {
        foreach (\Storage::disk('public')->allFiles($masterBase) as $f) {
            // Match suffix: 'style.css' cocok dengan
            // 'templates/4/original/mikrotik-standard-template/style.css'
            // dan 'images/logo.svg' cocok dengan
            // 'templates/4/original/mikrotik-standard-template/images/logo.svg'
            if (substr($f, -strlen('/' . $path)) === '/' . $path) {
                $candidates[] = $f;
            }
        }
    }
    // Draft user (kalau editor sudah save)
    $draftBase = "templates/orders/" . (auth()->id() ?? 'guest') . "/{$t->id}";
    if (\Storage::disk('public')->exists($draftBase)) {
        foreach (\Storage::disk('public')->allFiles($draftBase) as $f) {
            if (substr($f, -strlen('/' . $path)) === '/' . $path) {
                $candidates[] = $f;
            }
        }
    }

    if (!$candidates) {
        abort(404, "Asset '{$path}' not found in template #{$t->id}");
    }

    // Pakai candidate pertama (master lebih diutamakan karena suffix-match
    // memastikan exact filename match)
    $resolved = $candidates[0];
    $absolutePath = \Storage::disk('public')->path($resolved);

    // SECURITY: realpath check — pastikan path absolut benar-benar ada di dalam
    // folder template yang diizinkan. Menangani symlink/case-insensitive Windows
    // tricks yang lolos dari filter string di atas.
    $realResolved = realpath($absolutePath);
    $realMaster = realpath(\Storage::disk('public')->path($masterBase));
    $realDraft = realpath(\Storage::disk('public')->path($draftBase)) ?: $realMaster;
    if (! $realResolved || ! $realMaster) {
        abort(404, 'Asset not found on disk.');
    }
    if (strpos($realResolved, $realMaster) !== 0 && strpos($realResolved, $realDraft) !== 0) {
        abort(403, 'Resolved path outside allowed template folder.');
    }

    // MIME type dari extension
    $mime = match ($ext) {
        'css'  => 'text/css; charset=utf-8',
        'js'   => 'application/javascript; charset=utf-8',
        'map'  => 'application/json; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'  => 'font/ttf',
        'otf'  => 'font/otf',
        'eot'  => 'application/vnd.ms-fontobject',
        'txt'  => 'text/plain; charset=utf-8',
        'md'   => 'text/markdown; charset=utf-8',
        default => 'application/octet-stream',
    };

    return response()->file($absolutePath, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=3600', // asset bisa di-cache 1 jam
    ]);
})->where('path', '^(?!.*\.html$).+')->name('templates.preview.asset');

// ── ID-based Preview (pakai {id} template, scope ke ID card itu) ───────
// Contoh: /templates/15/preview         (default login.html)
//         /templates/15/preview/status   (status.html)
//         /templates/15/preview/logout   (logout.html)
// Folder pattern: storage/app/public/templates/{id}/original/{file}
Route::get('/templates/{id}/preview/{file?}', function ($id, $file = 'login.html') {
    $t = \App\Models\Template::findOrFail($id);

    // Whitelist file (security: cegah path traversal)
    $allowed = ['login.html', 'status.html', 'logout.html', 'error.html',
                'alogin.html', 'rlogin.html', 'redirect.html', 'radvert.html'];
    if (!in_array($file, $allowed)) {
        abort(404, 'File not found');
    }

    // Resolve file dengan PRIORITY: edited draft user dulu, fallback ke master.
    // Penting untuk live preview di editor — user expect lihat hasil edit mereka,
    // bukan master original.
    //
    // 1) Draft edited (orders/{user_id}/{id}/login.html) — PRIORITY TERTINGGI
    //    kalau ada, pakai ini (hasil edit user)
    // 2) Master (templates/{id}/original/...) — fallback kalau draft kosong
    //
    // Untuk file login.html, cek DRAFT dulu. Untuk file lain (status.html dll),
    // cek DRAFT dulu, fallback ke master — biar preview konsisten dengan template
    // yang sudah diedit.
    //
    // SECURITY: scan rekursif semua file di basePath, pilih file dengan nama $file.
    // Kalau ada beberapa (root + subfolder), pilih yang path-nya PALING PANJANG
    // (= paling dalam = file user upload asli, bukan orphan di root).
    $userId = auth()->id() ?? 'guest';
    $draftPath = "templates/orders/{$userId}/{$id}";
    $masterPath = "templates/{$t->id}/original";

    $matches = [];
    // Priority 1: cek draft edited user dulu
    if (\Storage::disk('public')->exists($draftPath)) {
        foreach (\Storage::disk('public')->allFiles($draftPath) as $candidate) {
            if (basename($candidate) === $file) {
                $matches[] = ['path' => $candidate, 'priority' => 1];
            }
        }
    }
    // Priority 2: fallback ke master kalau draft tidak punya file ini
    if (empty($matches) && \Storage::disk('public')->exists($masterPath)) {
        foreach (\Storage::disk('public')->allFiles($masterPath) as $candidate) {
            if (basename($candidate) === $file) {
                $matches[] = ['path' => $candidate, 'priority' => 2];
            }
        }
    }
    if (!$matches) {
        abort(404, "File '{$file}' tidak ada di template #{$id} (master atau draft edited). Upload folder template yang berisi login.html.");
    }
    // Pilih berdasarkan priority dulu (draft > master), lalu path terpanjang
    // (= paling dalam di tree, kalau ada beberapa file dengan nama sama)
    usort($matches, function ($a, $b) {
        if ($a['priority'] !== $b['priority']) return $a['priority'] - $b['priority'];
        return strlen($b['path']) - strlen($a['path']);
    });
    $filePath = \Storage::disk('public')->path($matches[0]['path']);
    // FIX: <base> menunjuk ke ROUTE HANDLER /templates/{id}/preview/, BUKAN storage symlink.
    // Alasan: storage symlink bisa tidak ada di production; route handler konsisten
    // baca dari folder master, dan <base> akan resolve relative path (style.css,
    // images/logo.svg) ke route templates.preview.asset yang baru.
    $baseHref = url('/templates/' . $t->id . '/preview/');
    $html = file_get_contents($filePath);

    // Inject base tag untuk asset relatif
    $baseTag = '<base href="' . $baseHref . '/">';
    $html = str_replace('<head>', "<head>\n" . $baseTag, $html);

    // Mode editor: disable interaksi form di dalam iframe supaya user tidak
    // bisa salah ketik di input username/password MikroTik. Cegah focus stealing
    // ke iframe (kombinasi dengan `inert` + `pointer-events: none` di frontend).
    if (request('editor') === '1') {
        $disableCss = '<style id="editor-mode-disable">'
            . 'input, textarea, select, button, a, form, [tabindex] {'
            . '  pointer-events: none !important;'
            . '  user-select: none !important;'
            . '  -webkit-user-select: none !important;'
            . '}'
            . 'body { cursor: default !important; }'
            . '</style>';
        $html = str_replace('</head>', $disableCss . '</head>', $html);
    }

    // Demo MikroTik variables (replace placeholder)
    $demo = [
        '$(username)' => 'demo',
        '$(ip)' => '192.168.88.10',
        '$(mac)' => 'AA:BB:CC:DD:EE:FF',
        '$(uptime)' => '00:15:23',
        '$(bytes-in-nice)' => '12 MB',
        '$(bytes-out-nice)' => '30 MB',
        '$(session-time-left)' => '00:44:37',
        // Link ke preview lain pakai ID yang sama (dengan .html di akhir karena
        // route punya constraint where('file', '.*\.html$'))
        '$(link-login-only)' => url('/templates/' . $t->id . '/preview/alogin.html'),
        '$(link-login)' => url('/templates/' . $t->id . '/preview/login.html'),
        '$(link-logout)' => url('/templates/' . $t->id . '/preview/logout.html'),
        '$(link-status)' => url('/templates/' . $t->id . '/preview/status.html'),
        '$(link-redirect)' => url('/templates/' . $t->id . '/preview/status.html'),
        '$(link-redirect-esc)' => url('/templates/' . $t->id . '/preview/status.html'),
        '$(link-orig)' => 'http://192.168.88.1/',
        '$(location-id)' => 'demo-location',
        '$(location-name)' => 'Demo Hotspot',
        '$(error)' => 'Simulasi: username atau password salah',
        '$(hostname)' => '192.168.88.1',
        '$(popup)' => 'true',
        '$(if session-time-left)' => '', '$(endif)' => '',
        '$(if advert-pending' => '', '$(if login-by-mac' => '',
        '$(if http-status' => '', '$(if http-header' => '',
        '$(refresh-timeout-secs)' => '30',
    ];
    $custom = [
        '{{BUSINESS_NAME}}' => $t->name,
        '{{RUNNING_TEXT}}' => 'Selamat datang di ' . $t->name . '! Demo preview.',
        '{{PRIMARY_COLOR}}' => '#4F46E5',
        '{{PRIMARY_COLOR_RGB}}' => '79, 70, 229',
        '{{BG_GRADIENT}}' => 'linear-gradient(135deg, #4F46E5, #7C3AED)',
        '{{BG_COLOR1}}' => '#4F46E5',
        '{{BG_COLOR2}}' => '#7C3AED',
        '{{LOGIN_BTN_TEXT}}' => 'Login Hotspot',
        '{{FOOTER_TEXT}}' => 'Powered by MarketTemplate',
        '{{WHATSAPP}}' => '0812-3456-7890',
        '{{LOGO_URL}}' => $t->preview_image ? asset('storage/' . $t->preview_image) : 'logo.png',
        '{{SHOW_VOUCHER}}' => 'block',
        '{{VOUCHER_1_NAME}}' => '1 Jam', '{{VOUCHER_1_PRICE}}' => 'Rp 1K', '{{VOUCHER_1_DURATION}}' => '1 JAM',
        '{{VOUCHER_2_NAME}}' => '5 Jam', '{{VOUCHER_2_PRICE}}' => 'Rp 3K', '{{VOUCHER_2_DURATION}}' => '5 JAM',
        '{{VOUCHER_3_NAME}}' => '24 Jam', '{{VOUCHER_3_PRICE}}' => 'Rp 5K', '{{VOUCHER_3_DURATION}}' => '1 HARI',
    ];
    $replacements = array_merge($demo, $custom);
    $html = str_replace(array_keys($replacements), array_values($replacements), $html);

    // Add target="_top" ke semua <a> link (escape iframe)
    $html = preg_replace_callback('/<a\s+([^>]*?)>/i', function ($m) {
        $attrs = $m[1];
        if (preg_match('/\btarget\s*=/i', $attrs)) return $m[0];
        if (preg_match('/href\s*=\s*["\']#/', $attrs)) return $m[0];
        return '<a ' . $attrs . ' target="_top" rel="noopener">';
    }, $html);

    // Form interceptor: redirect submit ke status.html
    $interceptor = <<<JS
<script>
(function() {
    var basePath = '/templates/' + {$t->id} + '/preview/';
    document.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        window.top.location.href = basePath + 'status.html';
    }, true);
})();
</script>
JS;
    $html = str_replace('</body>', $interceptor . "\n</body>", $html);

    return response($html, 200)
        ->header('Content-Type', 'text/html; charset=utf-8')
        ->header('X-Frame-Options', 'SAMEORIGIN')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
})->where('file', '.*\.html$')->name('templates.preview');

// ── Interactive Preview (Inertia — tetap untuk compatibility) ─────────
Route::get('/template/{id}/preview', function ($id) {
    $t = \App\Models\Template::findOrFail($id);
    return Inertia::render('TemplatePreview', [
        'template' => ['id' => $t->id, 'name' => $t->name],
        'canLogin' => Route::has('login'),
    ]);
})->name('template.preview');

Route::get('/template/{id}/preview-frame', function ($id) {
    $t = \App\Models\Template::findOrFail($id);
    $page = request('page', 'login');

    // Map page names to files
    $pageFiles = [
        'login' => 'login.html',
        'status' => 'status.html',
        'logout' => 'logout.html',
        'error' => 'error.html',
        'alogin' => 'alogin.html',
    ];
    $file = $pageFiles[$page] ?? 'login.html';

    // Resolve HANYA dari folder template #{$id} (ID-based, no cross-template).
    // Scan rekursif semua file, pilih dengan nama $file. Kalau ada beberapa
    // (root + subfolder), pilih yang path-nya PALING PANJANG (= paling dalam
    // = file upload user asli, bukan orphan di root).
    $templateFolder = $t->zip_file;
    if (!$templateFolder) {
        return response("Template #{$id} belum punya file upload.", 404);
    }
    $allFiles = Storage::disk('public')->allFiles($templateFolder);
    $matches = [];
    foreach ($allFiles as $candidate) {
        if (basename($candidate) === $file) {
            $matches[] = $candidate;
        }
    }
    if (!$matches) {
        return response("File '{$file}' tidak ada di folder template #{$id} (root atau subfolder).", 404);
    }
    usort($matches, function ($a, $b) { return strlen($b) - strlen($a); });
    $filePath = Storage::disk('public')->path($matches[0]);
    $baseHref = asset('storage/' . dirname($matches[0]));
    $html = file_get_contents($filePath);

    // Mode editor: disable interaksi form di dalam iframe (anti focus stealing)
    if (request('editor') === '1') {
        $disableCss = '<style id="editor-mode-disable">'
            . 'input, textarea, select, button, a, form, [tabindex] {'
            . '  pointer-events: none !important;'
            . '  user-select: none !important;'
            . '  -webkit-user-select: none !important;'
            . '}'
            . 'body { cursor: default !important; }'
            . '</style>';
        $html = str_replace('</head>', $disableCss . '</head>', $html);
    }

    // Demo data for MikroTik variables
    $demo = [
        '$(username)' => 'demo',
        '$(ip)' => '192.168.88.10',
        '$(mac)' => 'AA:BB:CC:DD:EE:FF',
        '$(uptime)' => '00:15:23',
        '$(bytes-in-nice)' => '12 MB',
        '$(bytes-out-nice)' => '30 MB',
        '$(session-time-left)' => '00:44:37',
        '$(link-login-only)' => url('/templates/' . $t->id . '/preview/alogin.html'),
        '$(link-login)' => url('/templates/' . $t->id . '/preview/login.html'),
        '$(link-logout)' => url('/templates/' . $t->id . '/preview/logout.html'),
        '$(link-status)' => url('/templates/' . $t->id . '/preview/status.html'),
        '$(link-redirect)' => url('/templates/' . $t->id . '/preview/status.html'),
        '$(link-redirect-esc)' => url('/templates/' . $t->id . '/preview/status.html'),
        '$(link-orig)' => 'http://192.168.88.1/',
        '$(location-id)' => 'demo-location',
        '$(location-name)' => 'Demo Hotspot',
        '$(error)' => 'Simulasi: username atau password salah',
        '$(hostname)' => '192.168.88.1',
        '$(popup)' => 'true',
        '$(if session-time-left)' => '', '$(endif)' => '',
        '$(if advert-pending' => '', '$(if login-by-mac' => '',
        '$(if http-status' => '', '$(if http-header' => '',
        '$(refresh-timeout-secs)' => '30',
    ];

    // Our custom placeholders
    $custom = [
        '{{BUSINESS_NAME}}' => $t->name,
        '{{RUNNING_TEXT}}' => 'Selamat datang di ' . $t->name . '! Demo preview.',
        '{{PRIMARY_COLOR}}' => '#4F46E5',
        '{{PRIMARY_COLOR_RGB}}' => '79, 70, 229',
        '{{BG_GRADIENT}}' => 'linear-gradient(135deg, #4F46E5, #7C3AED)',
        '{{BG_COLOR1}}' => '#4F46E5',
        '{{BG_COLOR2}}' => '#7C3AED',
        '{{LOGIN_BTN_TEXT}}' => 'Login Hotspot',
        '{{FOOTER_TEXT}}' => 'Powered by MarketTemplate',
        '{{WHATSAPP}}' => '0812-3456-7890',
        '{{LOGO_URL}}' => $t->preview_image ? asset('storage/' . $t->preview_image) : 'logo.png',
        '{{SHOW_VOUCHER}}' => 'block',
        '{{VOUCHER_1_NAME}}' => '1 Jam', '{{VOUCHER_1_PRICE}}' => 'Rp 1K', '{{VOUCHER_1_DURATION}}' => '1 JAM',
        '{{VOUCHER_2_NAME}}' => '5 Jam', '{{VOUCHER_2_PRICE}}' => 'Rp 3K', '{{VOUCHER_2_DURATION}}' => '5 JAM',
        '{{VOUCHER_3_NAME}}' => '24 Jam', '{{VOUCHER_3_PRICE}}' => 'Rp 5K', '{{VOUCHER_3_DURATION}}' => '24 JAM',
    ];

    $replacements = array_merge($demo, $custom);
    $html = str_replace(array_keys($replacements), array_values($replacements), $html);

    // Inject base tag for relative assets
    $baseTag = '<base href="' . $baseHref . '/">';
    $html = str_replace('<head>', "<head>\n" . $baseTag, $html);

    // Make all <a> links break out of the sandboxed iframe so they
    // navigate the top-level window instead of being blocked by the
    // sandbox policy (which has no allow-top-navigation flag).
    $html = preg_replace_callback(
        '/<a\s+([^>]*?)>/i',
        function ($m) {
            $attrs = $m[1];
            // Skip if already has a target
            if (preg_match('/\btarget\s*=/i', $attrs)) return $m[0];
            // Skip anchors (e.g. <a href="#section">)
            if (preg_match('/href\s*=\s*["\']#/', $attrs)) return $m[0];
            return '<a ' . $attrs . ' target="_top" rel="noopener">';
        },
        $html
    );

    // Remove problematic MikroTik conditionals
    $html = preg_replace('/\$\(if[^)]*\)/', '', $html);

    // Inject demo JS if ?demo=1
    if (request('demo') === '1') {
        $statusUrl = route('template.preview-frame', ['id' => $id, 'page' => 'status', 'demo' => 1]);
        $loginUrl = route('template.preview-frame', ['id' => $id, 'page' => 'login', 'demo' => 1]);
        $demoJs = '<script>
            (function() {
                document.body.setAttribute("data-demo", "true");

                // Auto-fill username/voucher fields
                setTimeout(function() {
                    var inputs = document.querySelectorAll("input[name=\'username\'], input[name=\'voucher\'], input[type=\'text\']:not([readonly])");
                    var filled = false;
                    for (var i = 0; i < inputs.length; i++) {
                        if (!inputs[i].value || inputs[i].value === "") {
                            inputs[i].value = "DEMO123";
                            filled = true;
                        }
                    }
                    // Auto-fill password if empty
                    var pws = document.querySelectorAll("input[type=\'password\']:not([readonly])");
                    for (var j = 0; j < pws.length; j++) {
                        if (!pws[j].value || pws[j].value === "") {
                            pws[j].value = "demo";
                        }
                    }
                }, 200);

                // Helper: find submit button in form
                function findSubmitBtn(form) {
                    return form.querySelector("button[type=\'submit\'], input[type=\'submit\'], .btn, [class*=\'btn\'], button");
                }

                // Intercept all form submissions
                var forms = document.querySelectorAll("form");
                forms.forEach(function(form) {
                    form.addEventListener("submit", function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var btn = findSubmitBtn(form);
                        var origText = btn ? (btn.textContent || btn.value || "Login") : "Login";
                        if (btn) {
                            btn.textContent = btn.value = "Menghubungkan...";
                            btn.disabled = true;
                            btn.style.opacity = "0.7";
                            btn.style.cursor = "wait";
                        }

                        // Tell parent to show loading overlay
                        window.parent.postMessage({ action: "connecting" }, "*");

                        // After delay, navigate to status
                        setTimeout(function() {
                            window.parent.postMessage({ action: "success" }, "*");
                            setTimeout(function() {
                                window.location.href = "' . $statusUrl . '";
                            }, 1000);
                        }, 1500);
                    }, true);
                });

                // Intercept logout links/buttons
                document.addEventListener("click", function(e) {
                    var target = e.target;
                    // Check if clicked element or parent is a logout link
                    while (target && target !== document.body) {
                        var href = target.getAttribute("href") || "";
                        var action = target.getAttribute("action") || "";
                        var text = (target.textContent || "").toLowerCase();
                        if (href.indexOf("logout") > -1 || action.indexOf("logout") > -1 || text.indexOf("log") > -1) {
                            if (target.tagName === "A" || target.tagName === "BUTTON" || target.tagName === "FORM") {
                                e.preventDefault();
                                window.parent.postMessage({ action: "logout" }, "*");
                                setTimeout(function() {
                                    window.location.href = "' . $loginUrl . '";
                                }, 800);
                                return;
                            }
                        }
                        target = target.parentElement;
                    }
                }, true);

                // Intercept status page "Log Off" forms
                var btns = document.querySelectorAll("button, input[type=\'submit\'], .btn-danger");
                btns.forEach(function(btn) {
                    var text = (btn.textContent || btn.value || "").toLowerCase();
                    if (text.indexOf("log off") > -1 || text.indexOf("logout") > -1) {
                        btn.addEventListener("click", function(e) {
                            e.preventDefault();
                            window.parent.postMessage({ action: "logout" }, "*");
                            setTimeout(function() {
                                window.location.href = "' . $loginUrl . '";
                            }, 800);
                        });
                    }
                });
            })();
        </script>';
        $html = str_replace('</body>', $demoJs . "\n</body>", $html);
    }

    return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');
})->name('template.preview-frame');

Route::get('/template/{id}/edit', function ($id) {
    $t = \App\Models\Template::findOrFail($id);
    return Inertia::render('EditTemplate', [
        'template' => [
            'id' => $t->id,
            'name' => $t->name,
            'category' => $t->category,
        ],
        'canLogin' => Route::has('login'),
    ]);
})->name('template.editor');

// ── Editor: baca field editable dari login.html (data-edit attributes) ─────
Route::get('/template/{id}/editor/fields', function ($id) {
    $t = \App\Models\Template::findOrFail($id);
    $folder = $t->zip_file;
    if (!$folder) {
        return response()->json(['fields' => [], 'html' => '', 'has_data_edit' => false]);
    }

    // PERSISTENSI EDIT PER USER:
    // 1) Prioritas: draft edited user di `orders/{user_id}/{id}/login.html`
    //    (hasil edit terakhir — user lanjut edit dari kondisi terakhir, bukan master default)
    // 2) Fallback: master template (untuk user baru / template yang belum pernah di-edit)
    //
    // Source-of-truth: `data-edit` attributes di HTML (master sama edited punya
    // atribut yang sama, hanya inner text yang berubah). Kalau edited draft ada,
    // struktur field list identik dengan master — yang berubah hanya default value.
    $loginPath = null;
    $userId = auth()->id() ?? 'guest';
    $editedLoginFile = "templates/orders/{$userId}/{$id}/login.html";

    if (\Storage::disk('public')->exists($editedLoginFile)) {
        // User pernah edit — pakai draft
        $loginPath = $editedLoginFile;
    } else {
        // Belum pernah edit — load master untuk derive field schema
        $masterLoginFile = $folder . '/login.html';
        if (\Storage::disk('public')->exists($masterLoginFile)) {
            $loginPath = $masterLoginFile;
        } else {
            // Scan subfolder master
            foreach (\Storage::disk('public')->allFiles($folder) as $candidate) {
                if (basename($candidate) === 'login.html') {
                    $loginPath = $candidate;
                    break;
                }
            }
        }
    }

    if (!$loginPath) {
        return response()->json(['fields' => [], 'html' => '', 'has_data_edit' => false]);
    }

    $html = \Storage::disk('public')->get($loginPath);
    $fields = [];

    // Helper inline: extract attribute value dari tag string
    $extractAttr = function ($tag, $attr) {
        if (preg_match('/\b' . $attr . '="([^"]*)"/i', $tag, $m)) {
            return $m[1];
        }
        return '';
    };

    // Helper inline: extract label dari data-label attribute, fallback ke name
    $extractLabel = function ($tag, $name) {
        if (preg_match('/\bdata-label="([^"]*)"/i', $tag, $m) && $m[1] !== '') {
            return $m[1];
        }
        // Capitalize & prettify name: "brand_name" → "Brand Name"
        return ucwords(str_replace(['_', '-'], ' ', $name));
    };

    // Helper inline: extract inner text dari tag (non-greedy)
    $extractInner = function ($html, $name) use ($extractAttr) {
        $pattern = '/<(\w+)([^>]*\bdata-edit="' . preg_quote($name, '/') . '")[^>]*>(.*?)<\/\1>/is';
        if (preg_match($pattern, $html, $m)) {
            return trim($m[3]);
        }
        return '';
    };

    // Parse data-edit (text)
    if (preg_match_all('/<(\w+)([^>]*\bdata-edit="([^"]+)"[^>]*)>/i', $html, $matches, PREG_SET_ORDER)) {
        $seen = [];
        foreach ($matches as $m) {
            $name = $m[3];
            if (isset($seen[$name])) continue; // skip duplikat
            $seen[$name] = true;
            $tag = '<' . $m[1] . $m[2] . '>';
            $fields[] = [
                'name' => $name,
                'type' => 'text',
                'label' => $extractLabel($tag, $name),
                'default' => $extractInner($html, $name),
            ];
        }
    }

    // Parse data-edit-image (image — bisa di tag apapun: <img>, <section>, <div>, dll)
    // Untuk <img>: replace src. Untuk non-<img>: replace background-image di inline style
    if (preg_match_all('/<(\w+)([^>]*\bdata-edit-image="([^"]+)"[^>]*)>/i', $html, $matches, PREG_SET_ORDER)) {
        $seen = [];
        foreach ($matches as $m) {
            $tagName = strtolower($m[1]);
            $attrs = $m[2];
            $name = $m[3];
            if (isset($seen[$name])) continue;
            $seen[$name] = true;
            $tag = '<' . $m[1] . $attrs . '>';

            // Default value: src untuk <img>, background-image URL untuk non-<img>
            $default = '';
            if ($tagName === 'img') {
                $default = $extractAttr($tag, 'src');
            } else {
                // Extract background-image URL dari style attribute
                if (preg_match('/\bstyle\s*=\s*"([^"]*)"/i', $tag, $sm)) {
                    if (preg_match('/background-image\s*:\s*url\(["\']?([^"\')\s]*)["\']?\)/i', $sm[1], $bm)) {
                        $default = $bm[1];
                    }
                }
            }

            $fields[] = [
                'name' => $name,
                'type' => 'image',
                'label' => $extractLabel($tag, $name),
                'default' => $default,
                'tag' => $tagName, // info tambahan untuk frontend (render img preview vs background)
            ];
        }
    }

    // Parse data-edit-link (link href)
    if (preg_match_all('/<a([^>]*\bdata-edit-link="([^"]+)"[^>]*)>/i', $html, $matches, PREG_SET_ORDER)) {
        $seen = [];
        foreach ($matches as $m) {
            $name = $m[2];
            if (isset($seen[$name])) continue;
            $seen[$name] = true;
            $tag = '<a' . $m[1] . '>';
            $fields[] = [
                'name' => $name,
                'type' => 'link',
                'label' => $extractLabel($tag, $name),
                'default' => $extractAttr($tag, 'href'),
            ];
        }
    }

    return response()->json([
        'fields' => $fields,
        'html' => $html,
        'has_data_edit' => count($fields) > 0,
    ]);
})->name('template.editor.fields');

// ── Editor: save ke salinan orders/{user_id}/{template_id}/ (master TIDAK diubah) ─────
Route::post('/template/{id}/editor/save', function (\Illuminate\Http\Request $request, $id) {
    $t = \App\Models\Template::findOrFail($id);
    $folder = $t->zip_file;
    if (!$folder) {
        return response()->json(['ok' => false, 'error' => 'Template belum punya file upload.'], 400);
    }

    // Cari login.html
    $loginPath = null;
    $loginFile = $folder . '/login.html';
    if (\Storage::disk('public')->exists($loginFile)) {
        $loginPath = $loginFile;
    } else {
        foreach (\Storage::disk('public')->allFiles($folder) as $candidate) {
            if (basename($candidate) === 'login.html') {
                $loginPath = $candidate;
                break;
            }
        }
    }

    if (!$loginPath) {
        return response()->json(['ok' => false, 'error' => 'login.html tidak ditemukan di template.'], 404);
    }

    $html = \Storage::disk('public')->get($loginPath);
    $values = $request->input('values', []);

    if (!is_array($values)) {
        $values = [];
    }

    // Apply replacements — preserve MikroTik variables ($, $(if...), $(endif), etc.)
    foreach ($values as $name => $value) {
        $escaped = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

        // Text: replace inner content of tag with data-edit="name"
        // FIX: capture group $m[2] diakhiri `>` (termasuk kurung tutup tag buka).
        //      Rekonstruksi sebagai: '<' + tag + attrs (TANPA trailing `>`) + escaped + closing.
        //      Regex lama: $m[2] . $escaped . $m[4] . '>' menghasilkan `>escaped</tag>>` (dobel `>`).
        //      Regex baru: attrs (group 2) hanya sampai `>` pembuka, lalu di-trim.
        $html = preg_replace_callback(
            '/<(\w+)((?:[^>]*\bdata-edit="' . preg_quote($name, '/') . '")[^>]*)>(.*?)(<\/\1>)/is',
            function ($m) use ($escaped) {
                return '<' . $m[1] . $m[2] . '>' . $escaped . $m[4];
            },
            $html
        );

        // Image: replace asset sesuai tag type
        // - <img>: replace src attribute
        // - non-<img> (section, div, dll): replace background-image URL di inline style
        $html = preg_replace_callback(
            '/<(\w+)((?:[^>]*\bdata-edit-image="' . preg_quote($name, '/') . '")[^>]*)>/i',
            function ($m) use ($escaped) {
                $tagName = strtolower($m[1]);
                $openTag = '<' . $m[1] . $m[2] . '>';
                if ($tagName === 'img') {
                    // Replace src attribute in opening tag
                    $newOpen = preg_replace('/\bsrc="[^"]*"/', 'src="' . $escaped . '"', $openTag, 1);
                    return $newOpen ?: $openTag;
                }
                // Non-img: replace background-image URL di style attribute
                // Jika belum ada style, tambahkan; jika ada, replace
                if (preg_match('/\bstyle\s*=\s*"([^"]*)"/i', $openTag, $sm)) {
                    $oldStyle = $sm[1];
                    if (preg_match('/background-image\s*:\s*url\([^)]*\)/i', $oldStyle)) {
                        $newStyle = preg_replace(
                            '/background-image\s*:\s*url\([^)]*\)/i',
                            'background-image: url("' . $escaped . '")',
                            $oldStyle
                        );
                    } else {
                        $newStyle = 'background-image: url("' . $escaped . '"); ' . $oldStyle;
                    }
                    $newOpen = preg_replace('/\bstyle\s*=\s*"[^"]*"/i', 'style="' . $newStyle . '"', $openTag, 1);
                    return $newOpen ?: $openTag;
                }
                // Tidak ada style attribute — tambahkan di posisi yang aman
                // (sebelum `>` penutup opening tag)
                return preg_replace(
                    '/(<\w+(?:\s[^>]*)?)(\s*\/\s*)?>$/i',
                    '$1 style="background-image: url(\'' . $escaped . '\')">',
                    $openTag,
                    1
                );
            },
            $html
        );

        // Link: replace href attribute on tag with data-edit-link="name"
        $html = preg_replace_callback(
            '/<a((?:[^>]*\bdata-edit-link="' . preg_quote($name, '/') . '")[^>]*)>/i',
            function ($m) use ($escaped) {
                $openTag = '<a' . $m[1] . '>';
                $newOpen = preg_replace('/\bhref="[^"]*"/', 'href="' . $escaped . '"', $openTag, 1);
                return $newOpen ?: $openTag;
            },
            $html
        );
    }

    // Simpan ke salinan: orders/{user_id}/{template_id}/
    $userId = $request->user()?->id ?? 'guest';
    $outDir = "templates/orders/{$userId}/{$id}";
    \Storage::disk('public')->makeDirectory($outDir);

    // ── Copy asset statis (style.css, images/, assets/, status.html, logout.html) ──
    // PATCH KRITIS #1: save closure SEBELUMNYA hanya tulis login.html, sedangkan
    // HTML-nya refer ke style.css / images/logo.svg / assets/script.js → 404.
    // Fix: copy SEMUA file dari folder master (dirname login.html) ke folder
    // draft, KECUALI login.html (akan ditulis ulang di bawah dengan replaced
    // values). Recursive copy supaya sub-folder (images/, assets/) ikut.
    //
    // Idempotent: kalau file di draft sudah ada (save ulang), overwrite dengan
    // versi master terbaru — supaya kalau admin update master, draft konsisten.
    $assetSrcDir = dirname($loginPath);
    if ($assetSrcDir && \Storage::disk('public')->exists($assetSrcDir)) {
        foreach (\Storage::disk('public')->allFiles($assetSrcDir) as $srcFile) {
            // Skip login.html — ditulis ulang dengan replaced values di bawah
            if (basename($srcFile) === 'login.html') continue;
            $rel = substr($srcFile, strlen($assetSrcDir) + 1);
            $dstFile = $outDir . '/' . $rel;
            // makeDirectory untuk sub-folder (images/, assets/) — no-op kalau sudah ada
            \Storage::disk('public')->makeDirectory(dirname($dstFile));
            // Copy file dari master ke draft (overwrite kalau ada)
            \Storage::disk('public')->put(
                $dstFile,
                \Storage::disk('public')->get($srcFile)
            );
        }
    }

    // ── Inject <base href> menunjuk ke route handler /templates/{id}/preview/ ──────
    // FIX FINAL: draft HTML (orders/{u}/{id}/login.html) akan di-load oleh iframe
    // editor dari URL `/templates/{id}/preview/login.html`. Route handler
    // `templates.preview` me-render HTML, dan route `templates.preview.asset`
    // (yang baru) me-serve asset (style.css, images/, assets/) dari folder
    // master. <base href> menunjuk ke route handler agar semua relative
    // asset path (style.css, images/logo.svg, assets/script.js) resolve ke
    // `/templates/{id}/preview/{asset}` dan dilayani oleh route asset.
    //
    // Alasan TIDAK pakai `asset('storage/...')` symlink path:
    //   1) Symlink bisa tidak ada di production (beberapa shared hosting).
    //   2) Browser bisa cache agresif (private, no-store, dll) sehingga edit
    //      baru tidak kelihatan.
    //   3) Single source of truth: route handler konsisten baca dari folder
    //      master templates/{id}/original/.
    //
    // PATCH KRITIS: trailing slash WAJIB ada. Tanpa trailing slash, browser
    // resolve `<base href="/foo">` + relative `style.css` jadi `/foostyle.css`
    // (bukan `/foo/style.css`). Lihat HTML5 §4.2.3 — <base href> resolution
    // algorithm: kalau base href tidak diakhiri `/`, treat as file, replace
    // last segment. Solusi: paksa trailing `/` agar base jadi direktori.
    $baseHref = rtrim(url('/templates/' . $id . '/preview'), '/') . '/';
    $baseTag = '<base href="' . $baseHref . '">';

    // Idempotent: kalau <base> sudah ada (save ulang), replace isinya.
    if (preg_match('/<base\s+href="[^"]*"\s*\/?>/i', $html)) {
        $html = preg_replace('/<base\s+href="[^"]*"\s*\/?>/i', $baseTag, $html, 1);
    } elseif (preg_match('/<head[^>]*>/i', $html, $hm, PREG_OFFSET_CAPTURE)) {
        $insertAt = $hm[0][1] + strlen($hm[0][0]);
        $html = substr($html, 0, $insertAt) . "\n  " . $baseTag . substr($html, $insertAt);
    }

    $outPath = "{$outDir}/login.html";
    \Storage::disk('public')->put($outPath, $html);

    return response()->json([
        'ok' => true,
        'path' => $outPath,
        'url' => asset('storage/' . $outPath),
    ]);
})->middleware('auth')->name('template.editor.save');

// ── Client Routes ───────────────────────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Client/Dashboard');
    })->name('dashboard');

    Route::get('/dashboard/templates', function () {
        return Inertia::render('Client/Templates');
    })->name('dashboard.templates');

    Route::get('/dashboard/purchases', function () {
        return Inertia::render('Client/Purchases');
    })->name('dashboard.purchases');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Template Generator Routes (scoped by {id} — WAJIB untuk hindari
    // data bentrok antar template, setiap proses mengacu ke ID spesifik)
    Route::get('/template/{id}/editor', [TemplateController::class, 'edit'])->name('template.edit');
    Route::post('/template/{id}/editor', [TemplateController::class, 'update'])->name('template.update');
});

// ── Admin Routes ───────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\AdminTemplateController::class, 'dashboard'])->name('dashboard');
    Route::get('/templates', [\App\Http\Controllers\AdminTemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/create', [\App\Http\Controllers\AdminTemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates', [\App\Http\Controllers\AdminTemplateController::class, 'store'])->name('templates.store');
    Route::get('/templates/{template}/edit', [\App\Http\Controllers\AdminTemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{template}', [\App\Http\Controllers\AdminTemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{template}', [\App\Http\Controllers\AdminTemplateController::class, 'destroy'])->name('templates.destroy');
    Route::patch('/templates/{template}/toggle', [\App\Http\Controllers\AdminTemplateController::class, 'togglePublish'])->name('templates.toggle');

    // Placeholder admin routes
    Route::get('/transactions', fn() => Inertia::render('Admin/Transactions'))->name('transactions');
    Route::get('/users', fn() => Inertia::render('Admin/Users'))->name('users');
    Route::get('/categories', fn() => Inertia::render('Admin/Categories'))->name('categories');
    Route::get('/settings', fn() => Inertia::render('Admin/Settings'))->name('settings');

    // Panduan Standar Template Editor (untuk creator)
    Route::get('/standard', fn() => Inertia::render('Admin/TemplateStandard'))->name('standard');
});

// ── Buyer Actions (scoped by {id} — setiap proses mengacu ke ID spesifik) ──
Route::middleware(['auth'])->group(function () {
    // Cart: add/remove template by id
    Route::post('/cart/{id}', [\App\Http\Controllers\CartController::class, 'add'])
        ->name('cart.add');
    Route::delete('/cart/{id}', [\App\Http\Controllers\CartController::class, 'remove'])
        ->name('cart.remove');

    // Checkout: GET menampilkan halaman, POST buat order
    Route::get('/checkout/{id}', [\App\Http\Controllers\CheckoutController::class, 'show'])
        ->name('checkout.show');
    Route::post('/checkout/{id}', [\App\Http\Controllers\CheckoutController::class, 'process'])
        ->name('checkout.process');

    // Payment: lanjut ke payment gateway untuk order tertentu
    Route::get('/payment/{order}', [\App\Http\Controllers\PaymentController::class, 'show'])
        ->name('payment.show');
    Route::post('/payment/{order}/process', [\App\Http\Controllers\PaymentController::class, 'process'])
        ->name('payment.process');
    Route::get('/payment/{order}/success', [\App\Http\Controllers\PaymentController::class, 'success'])
        ->name('payment.success');

    // Purchase history per-template
    Route::get('/purchases/{id}', [\App\Http\Controllers\PurchaseController::class, 'show'])
        ->name('purchase.show');
});

require __DIR__.'/auth.php';
