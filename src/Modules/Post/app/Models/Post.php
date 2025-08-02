<?php

namespace Modules\Post\app\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Comment\app\Models\Comment;
use Modules\Like\app\Models\Like;
use Modules\Post\database\factories\PostFactory;
use Modules\Poster\app\Models\Poster;

class Post extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'posts';
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'published_start_at' => 'datetime',
        'published_end_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'admin_uuid',
        'poster_id',
        'content',
        'is_published',
        'type',
        'published_start_at',
        'published_end_at',
        'deleted_at',
    ];

    public const FREE_TYPE = 'free';
    public const PREMIUM_TYPE = 'premium';
    private ?array $images = [];
    private ?array $videos = [];
    private int $mediaTotal = 0;
    private int $commentTotal = 0;
    private int $likeTotal = 0;

    /**
     * Post constructor.
     *
     * @param array $attributes The attributes to initialize the post with.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * Get the poster associated with the post.
     *
     * @return BelongsTo
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(Poster::class, 'poster_id', 'id');
    }

    /**
     * Get the comments associated with the post.
     *
     * @return HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id', 'id');
    }

    /**
     * Get the likes associated with the post.
     *
     * @return HasMany
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class, 'post_id', 'id');
    }

    /**
     * Check if the post is liked by the specified user ID.
     *
     * @param int $userId The user ID to check for.
     *
     * @return bool
     */
    public function isLiked(int $userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    /**
     * Get comments for the post.
     *
     * @param int|null $limit The maximum number of comments to retrieve.
     *
     * @return array
     */
    public function getComments(?int $limit = null): array
    {
        $query = $this->comments()
            ->select('id', 'user_id', 'comment')
            ->orderByDesc('created_at');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()->toArray();
    }

    /**
     * Set the images associated with the post.
     *
     * @param array|null $images The array of image URLs.
     *
     * @return void
     */
    public function setImages(?array $images): void
    {
        $this->images = $images;
    }

    /**
     * Get the images associated with the post.
     *
     * @return array|null
     */
    public function getImages(): ?array
    {
        return $this->images;
    }

    /**
     * Set the videos associated with the post.
     *
     * @param array|null $videos The array of video URLs.
     *
     * @return void
     */
    public function setVideos(?array $videos): void
    {
        $this->videos = $videos;
    }

    /**
     * Get the videos associated with the post.
     *
     * @return array|null
     */
    public function getVideos(): ?array
    {
        return $this->videos;
    }

    /**
     * Get the total number of media associated with the post.
     *
     * @return int The total number of media.
     */
    public function getMediaTotal(): int
    {
        return $this->mediaTotal;
    }

    /**
     * Set the total number of media associated with the post.
     *
     * @param int $total The total number of media to set.
     * @return int The updated total number of media.
     */
    public function setMediaTotal(int $total): int
    {
        return $this->mediaTotal = $total;
    }

    public function getPostType(): string
    {
        return $this->type; /* @phpstan-ignore-line */
    }

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }

    /**
     * Get the total number of comments.
     *
     * @return int The total number of comments.
     */
    public function getCommentTotal(): int
    {
        return $this->commentTotal;
    }

    /**
     * Set the total number of comments.
     *
     * @param int $total The total number of comments to set.
     *
     * @return int The updated total number of comments.
     */
    public function setCommentTotal(int $total): int
    {
        return $this->commentTotal = $total;
    }

    /**
     * Get the total number of likes.
     *
     * @return int The total number of likes.
     */
    public function getLikeTotal(): int
    {
        return $this->likeTotal;
    }

    /**
     * Set the total number of likes.
     *
     * @param int $total The total number of likes to set.
     *
     * @return int The updated total number of likes.
     */
    public function setLikeTotal(int $total): int
    {
        return $this->likeTotal = $total;
    }
}
