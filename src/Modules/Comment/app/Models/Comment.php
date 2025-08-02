<?php

namespace Modules\Comment\app\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Comment\database\factories\CommentFactory;
use Modules\Post\app\Models\Post;

class Comment extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'comments';
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'post_id',
        'comment',
        'is_hidden',
        'deleted_at',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    private int $comment_total = 0;
    public const HIDDEN_COMMENT = 1;
    private ?string $avatar = null;
    private ?string $nickname = null;

    // Relationship with Post
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }

    /**
     * Get the user ID associated with the post.
     *
     * @return int The user ID.
     */
    public function getUserId(): int
    {
        return $this->user_id; /* @phpstan-ignore-line */
    }

    /**
     * Get the total number of comments associated with the post.
     *
     * @return int The total number of comments.
     */
    public function getCommentTotal(): int
    {
        return $this->comment_total;
    }

    /**
     * Set the total number of comments associated with the post.
     *
     * @param int $commentTotal The total number of comments to set.
     * @return int The updated total number of comments.
     */
    public function setCommentTotal(int $commentTotal): int
    {
        return $this->comment_total = $commentTotal;
    }

    /**
     * Get the profile image path.
     *
     * This method retrieves the path of the profile image associated with the object.
     *
     * @return string|null The path of the profile image, or null if no profile image is set.
     */
    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    /**
     * Set the profile image path.
     *
     * This method sets the path of the profile image associated with the object.
     *
     * @param string|null $avatar The path of the profile image, or null to unset the profile image.
     * @return void
     */
    public function setAvatar(?string $avatar = null): void
    {
        $this->avatar = $avatar;
    }

    /**
     * Get the nickname of the poster.
     *
     * This method retrieves the nickname of the poster associated with the object.
     *
     * @return string|null The nickname of the poster, or null if no nickname is set.
     */
    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    /**
     * Set the nickname of the poster.
     *
     * This method sets the nickname of the poster associated with the object.
     *
     * @param string|null $nickname The nickname of the poster, or null to unset the nickname.
     * @return void
     */
    public function setNickname(?string $nickname = null): void
    {
        $this->nickname = $nickname;
    }
}
