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
        'tripay_reference',
        'tripay_checkout_url',
        'tripay_pay_code',
        'tripay_pay_url',
        'tripay_qr_string',
        'paid_at',
        'expired_at',
        'callback_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'callback_payload' => 'array',
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

    /**
     * Cek apakah order sudah lewat expired_at (kalau ada).
     * Server-side authoritative — countdown UI di Waiting.vue hanya untuk UX.
     */
    public function isExpired(): bool
    {
        return $this->expired_at !== null && now()->greaterThan($this->expired_at);
    }

    /**
     * Sisa detik sampai expired. Negatif kalau sudah lewat.
     */
    public function secondsUntilExpiry(): ?int
    {
        if ($this->expired_at === null) {
            return null;
        }
        return (int) now()->diffInSeconds($this->expired_at, false);
    }
}