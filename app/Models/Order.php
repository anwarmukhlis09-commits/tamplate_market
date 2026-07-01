<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'template_id',
        'status',
        'amount',
        'payment_method',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Cek apakah user sudah pernah order template ini dengan status completed.
     * Pakai ini di seluruh aplikasi sebagai single source of truth.
     */
    public static function isUserPaid(int $userId, int $templateId): bool
    {
        return self::where('user_id', $userId)
            ->where('template_id', $templateId)
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Ambil semua template ID yang sudah di-order (completed) oleh user.
     * Dipakai untuk badge "Sudah Dibeli" di katalog & dashboard.
     */
    public static function getPaidTemplateIds(int $userId): array
    {
        return self::where('user_id', $userId)
            ->where('status', 'completed')
            ->pluck('template_id')
            ->map(fn($id) => (int) $id)
            ->all();
    }
}