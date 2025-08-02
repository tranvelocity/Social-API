<?php

namespace Modules\Media\app\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Media\database\factories\MediaFactory;

/**
 * @property string|null $thumbnail
 */
class Media extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'medias';
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'path',
        'thumbnail',
        'type',
        'post_id',
        'deleted_at',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const IMAGE_TYPE = 'image';
    public const VIDEO_TYPE = 'video';
    private ?string $path = null;
    private ?string $thumbnail = null;

    protected static function newFactory(): MediaFactory
    {
        return MediaFactory::new();
    }

    /**
     * Get the path of the media file.
     *
     * @return string|null The path of the media file, or null if not set.
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Set the path of the media file.
     *
     * @param string|null $path The path of the media file, or null to unset.
     * @return void
     */
    public function setPath(?string $path = null): void
    {
        $this->path = $path;
    }

    /**
     * Get the post ID associated with the media.
     *
     * @return string|null The post ID associated with the media, or null if not set.
     */
    public function getPostId(): ?string
    {
        return $this->post_id; /* @phpstan-ignore-line */
    }

    /**
     * Get the thumbnail of the media file.
     *
     * @return string|null The thumbnail of the media file, or null if not set.
     */
    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    /**
     * Set the thumbnail of the media file.
     *
     * @param string|null $thumbnail The thumbnail of the media file, or null to unset.
     * @return void
     */
    public function setThumbnail(?string $thumbnail = null): void
    {
        $this->thumbnail = $thumbnail;
    }
}
