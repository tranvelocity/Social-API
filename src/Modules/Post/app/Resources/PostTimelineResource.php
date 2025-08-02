<?php

namespace Modules\Post\app\Resources;

use Illuminate\Http\Request;
use Modules\Core\app\Resources\JsonResource;
use Modules\Post\app\Models\Post;
use Modules\Role\app\Entities\Role;

class PostSocialResource extends JsonResource
{
    /**
     * The role of the user.
     *
     * @var int
     */
    private int $role;

    /**
     * Create a new resource instance.
     *
     * @param Post $post
     * @param int $role
     */
    public function __construct(Post $post, int $role)
    {
        parent::__construct($post);
        $this->role = $role;
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'admin_uuid' => $this->resource->admin_uuid,
            'type' => $this->resource->type,
            'content' => $this->getContentBasedOnRole(),
            'is_published' => boolval($this->resource->is_published),
            'published_start_at' => $this->formatDateTime($this->resource->published_start_at),
            'published_end_at' => $this->formatDateTime($this->resource->published_end_at),
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
            'created_at' => $this->formatDateTime($this->resource->created_at),
            'medias' => [
                'images' => $this->resource->getImages(),
                'videos' => $this->resource->getVideos(),
            ],
            'total_medias' => $this->resource->getMediaTotal(),
            'total_comments' => $this->resource->getCommentTotal(),
            'total_likes' => $this->resource->getLikeTotal(),
            'is_liked' => $this->resource->is_liked,
            'poster' => $this->getPosterDetails(),
        ];
    }

    /**
     * Retrieve details of the poster.
     *
     * @return array|null An array containing details of the poster if available, otherwise null.
     */
    private function getPosterDetails(): array|null
    {
        $poster = $this->resource->poster;

        return $poster ? [
            'id' => $poster->id,
            'user_id' => $poster->user_id,
            'nickname' => $poster->getNickname(),
            'avatar' => $poster->getAvatar(),
        ] : null;
    }

    /**
     * Get the content based on the user's role and post type.
     *
     * @return string|null The content or null based on the conditions.
     */
    private function getContentBasedOnRole(): ?string
    {
        // If the post type is premium and the user role is free member or non-registered, return null, otherwise, return the content.
        return (
            $this->resource->type == Post::PREMIUM_TYPE &&
            ($this->role === Role::freeMember()->getRole() || $this->role === Role::nonRegisteredUser()->getRole())
        )
            ? null
            : $this->resource->content;
    }
}
