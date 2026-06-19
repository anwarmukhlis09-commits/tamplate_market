<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Kolom yang dilindungi dari mass assignment.
     * Field privilege (is_admin, role, dll) WAJIB diset manual via ->update() atau
     * query langsung, BUKAN lewat $request->all() / ::create([...]) dari input user.
     * Lihat Auth/RegisteredUserController & Profile update yang harus whitelist field manual.
     */
    protected $guarded = ['is_admin', 'is_super_admin', 'role'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Cek apakah user adalah admin. Dipakai untuk bypass payment guard
     * (admin boleh download semua template tanpa bayar).
     */
    public function isAdmin(): bool
    {
        return (bool) ($this->is_admin ?? false);
    }
}
