<?php

namespace Modules\Like\app\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Like\database\factories\LikeFactory;
use Modules\Post\app\Models\Post;

class Like extends Model
{
    use HasFactory;
    use HasUlids;

    protected $table = 'likes';
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'post_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship with Post
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }

    protected static function newFactory(): LikeFactory
    {
        return LikeFactory::new();
    }
}
