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
    return Inertia::render('Catalog', [
        'templates' => \App\Models\Template::where('status', 'published')
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
            ]),
    ]);
});

// Download template sebagai ZIP (scope ke {id}) — HARUS dideklarasikan
// SEBELUM /template/{id} supaya route match spesifik duluan.
Route::get('/template/{id}/download', [\App\Http\Controllers\TemplateController::class, 'download'])
    ->middleware('auth')
    ->name('template.download');

Route::get('/template/{id}', function ($id) {
    $t = \App\Models\Template::findOrFail($id);
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
        'canLogin' => Route::has('login'),
    ]);
});

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

    // Resolve path ID-based: templates/{id}/original/{file}
    $basePath = "templates/{$t->id}/original";
    $filePath = null;

    if (\Storage::disk('public')->exists($basePath . '/' . $file)) {
        // Priority 1: User-uploaded template (ID-based)
        $filePath = \Storage::disk('public')->path($basePath . '/' . $file);
        $baseHref = asset('storage/' . $basePath);
    } elseif (file_exists(storage_path('app/master_template/' . $file))) {
        // Priority 2: Master template fallback
        $filePath = storage_path('app/master_template/' . $file);
        $baseHref = asset('storage/master_template');
    }

    if (!$filePath) abort(404, 'Page not found: ' . $file);

    $html = file_get_contents($filePath);

    // Inject base tag untuk asset relatif
    $baseTag = '<base href="' . $baseHref . '/">';
    $html = str_replace('<head>', "<head>\n" . $baseTag, $html);

    // Demo MikroTik variables (replace placeholder)
    $demo = [
        '$(username)' => 'demo',
        '$(ip)' => '192.168.88.10',
        '$(mac)' => 'AA:BB:CC:DD:EE:FF',
        '$(uptime)' => '00:15:23',
        '$(bytes-in-nice)' => '12 MB',
        '$(bytes-out-nice)' => '30 MB',
        '$(session-time-left)' => '00:44:37',
        // Link ke preview lain pakai ID yang sama (tidak ada bentrok)
        '$(link-login-only)' => url('/templates/' . $t->id . '/preview/alogin'),
        '$(link-login)' => url('/templates/' . $t->id . '/preview/login'),
        '$(link-logout)' => url('/templates/' . $t->id . '/preview/logout'),
        '$(link-status)' => url('/templates/' . $t->id . '/preview/status'),
        '$(link-redirect)' => url('/templates/' . $t->id . '/preview/status'),
        '$(link-redirect-esc)' => url('/templates/' . $t->id . '/preview/status'),
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

    // Read from template's folder first, fallback to master template
    $templateFolder = $t->zip_file; // e.g., "templates/hotspot-coffee"
    $filePath = null;

    if ($templateFolder && Storage::disk('public')->exists($templateFolder . '/' . $file)) {
        // Priority 1: User-uploaded template folder
        $filePath = Storage::disk('public')->path($templateFolder . '/' . $file);
        $baseHref = asset('storage/' . $templateFolder);
    } elseif (file_exists(public_path('storage/' . $templateFolder . '/' . $file))) {
        // Priority 2: Default MikroTik (copied to public/storage)
        $filePath = public_path('storage/' . $templateFolder . '/' . $file);
        $baseHref = asset('storage/' . $templateFolder);
    } elseif (file_exists(storage_path('app/master_template/' . $file))) {
        // Priority 3: Master template
        $filePath = storage_path('app/master_template/' . $file);
        $baseHref = asset('storage/master_template');
    }

    if (!$filePath) {
        return response('Page not found: ' . $file, 404);
    }
    $html = file_get_contents($filePath);

    // Demo data for MikroTik variables
    $demo = [
        '$(username)' => 'demo',
        '$(ip)' => '192.168.88.10',
        '$(mac)' => 'AA:BB:CC:DD:EE:FF',
        '$(uptime)' => '00:15:23',
        '$(bytes-in-nice)' => '12 MB',
        '$(bytes-out-nice)' => '30 MB',
        '$(session-time-left)' => '00:44:37',
        '$(link-login-only)' => route('template.preview-frame', ['id' => $id, 'page' => 'alogin']),
        '$(link-login)' => route('template.preview-frame', ['id' => $id, 'page' => 'login']),
        '$(link-logout)' => route('template.preview-frame', ['id' => $id, 'page' => 'logout']),
        '$(link-status)' => route('template.preview-frame', ['id' => $id, 'page' => 'status']),
        '$(link-redirect)' => route('template.preview-frame', ['id' => $id, 'page' => 'status']),
        '$(link-redirect-esc)' => route('template.preview-frame', ['id' => $id, 'page' => 'status']),
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
