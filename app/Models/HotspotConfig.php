<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotspotConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'running_text',
        'logo_path',
    ];
}
