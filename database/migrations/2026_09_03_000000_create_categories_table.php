<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('icon')->nullable();
            $table->string('color')->default('from-indigo-500 to-violet-500');
            $table->timestamps();
        });

        $defaults = [
            [
                'name' => 'Minimalis',
                'slug' => 'minimalis',
                'icon' => 'M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z',
                'color' => 'from-slate-400 to-slate-500',
            ],
            [
                'name' => 'Modern',
                'slug' => 'modern',
                'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                'color' => 'from-indigo-500 to-violet-500',
            ],
            [
                'name' => 'Gaming',
                'slug' => 'gaming',
                'icon' => 'M11 17a1 1 0 102 0v-1a1 1 0 10-2 0v1zM5 17a1 1 0 102 0v-1a1 1 0 10-2 0v1zm5-15a7 7 0 00-7 7c0 2 .5 3.5 1.5 5L3 17h14l-1.5-3c1-1.5 1.5-3 1.5-5a7 7 0 00-7-7z',
                'color' => 'from-fuchsia-500 to-pink-500',
            ],
            [
                'name' => 'Hotel',
                'slug' => 'hotel',
                'icon' => 'M3 21h18M3 7v14M21 7v14M6 21V11h12v10M9 7V3h6v4',
                'color' => 'from-amber-400 to-orange-500',
            ],
            [
                'name' => 'Cafe & Restaurant',
                'slug' => 'cafe',
                'icon' => 'M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z',
                'color' => 'from-rose-400 to-pink-500',
            ],
            [
                'name' => 'Sekolah',
                'slug' => 'sekolah',
                'icon' => 'M12 14l9-5-9-5-9 5 9 5z',
                'color' => 'from-cyan-400 to-blue-500',
            ],
            [
                'name' => 'Voucher',
                'slug' => 'voucher',
                'icon' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
                'color' => 'from-emerald-400 to-teal-500',
            ],
            [
                'name' => 'ISP',
                'slug' => 'isp',
                'icon' => 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M2 8.82a15 15 0 0120 0M5 12.859a10 10 0 0114 0M8.5 16.429a5 5 0 017 0',
                'color' => 'from-blue-500 to-indigo-600',
            ],
            [
                'name' => 'Classic',
                'slug' => 'classic',
                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                'color' => 'from-emerald-400 to-teal-500',
            ],
            [
                'name' => 'Professional',
                'slug' => 'pro',
                'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
                'color' => 'from-purple-500 to-pink-500',
            ],
        ];

        $now = now();
        foreach ($defaults as &$d) {
            $d['created_at'] = $now;
            $d['updated_at'] = $now;
        }

        DB::table('categories')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
