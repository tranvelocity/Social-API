<?php

namespace Modules\Admin\app\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Admin\database\factories\AdminFactory;

class Admin extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'admin';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'app_code', 'app_name', 'description', 'api_key', 'api_secret', 'deleted_at', 'created_at', 'updated_at'
    ];

    protected static function newFactory(): AdminFactory
    {
        return AdminFactory::new();
    }
}
