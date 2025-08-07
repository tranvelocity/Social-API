<?php

namespace Modules\Member\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'admin_uuid',
        'user_id',
        'member_number',
        'resigned_at',
        'is_resigned',
        'nickname',
        'deleted_at',
        'created_at',
        'updated_at',
    ];
}
