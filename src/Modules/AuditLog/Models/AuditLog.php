<?php

declare(strict_types=1);

namespace Tranauth\Laravel\Api\AuditLog\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'log',
        'admin_uuid',
        'created_at',
        'updated_at'
    ];

    protected $guarded = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'log' => 'array',
    ];
}
