<?php

namespace Modules\RestrictedUser\app\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\RestrictedUser\database\factories\RestrictedUserFactory;

class RestrictedUser extends Model
{
    use HasFactory;
    use HasUlids;

    protected $table = 'restricted_users';
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'admin_uuid',
        'user_id',
        'remarks',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    private ?string $avatar = null;
    private ?string $nickname = null;

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

    /**
     * Get the user ID associated with the post.
     *
     * @return int The user ID.
     */
    public function getUserId(): int
    {
        return $this->user_id; /* @phpstan-ignore-line */
    }

    protected static function newFactory(): RestrictedUserFactory
    {
        return RestrictedUserFactory::new();
    }
}
