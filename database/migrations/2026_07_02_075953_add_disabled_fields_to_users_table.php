<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Audit trail untuk admin yang disable akun. Nullable — user normal
            // tidak punya disabled_at. Di-set manual via controller, BUKAN via
            // mass assignment (tidak masuk $fillable).
            $table->timestamp('disabled_at')->nullable()->after('is_admin');
            $table->foreignId('disabled_by')->nullable()->after('disabled_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['disabled_by']);
            $table->dropColumn(['disabled_at', 'disabled_by']);
        });
    }
};