<?php

namespace Modules\User\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'email', 'last_name', 'first_name', 'first_name_kana', 'last_name_kana', 'tel',
        'post_code', 'prefecture', 'address', 'password', 'gender', 'deleted_at', 'created_at', 'updated_at'
    ];
    protected static function newFactory()
    {
        return \Modules\User\database\factories\UserFactory::new();
    }
}
