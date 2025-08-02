<?php

namespace Modules\ThrottleConfig\app\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\ThrottleConfig\database\factories\ThrottleConfigFactory;

class ThrottleConfig extends Model
{
    use HasFactory;
    use HasUlids;

    protected $table = 'throttle_configs';
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'admin_uuid',
        'time_frame_minutes',
        'max_comments',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function newFactory(): ThrottleConfigFactory
    {
        return ThrottleConfigFactory::new();
    }
}
