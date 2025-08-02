<?php

namespace Modules\Post\app\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Modules\Core\app\Resources\JsonResource;
use Modules\Post\app\Models\Post;

class PostResource extends JsonResource
{
    private bool $withExtraData;
    private bool $withPosterData;

    public function __construct(
        Post $post,
        bool $withExtraData = true,
        bool $withPosterData = true,
    ) {
        parent::__construct($post);
        $this->withExtraData = $withExtraData;
        $this->withPosterData = $withPosterData;
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $response = [
            'id' => $this->resource->id,
            'admin_uuid' => $this->resource->admin_uuid,
            'type' => $this->resource->type,
            'content' => $this->resource->content,
            'is_published' => boolval($this->resource->is_published),
            'published_start_at' => $this->formatDateTime($this->resource->published_start_at),
            'published_end_at' => $this->formatDateTime($this->resource->published_end_at),
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];

        if ($this->withExtraData) {
            $this->addExtraInfo($response);
        }

        if ($this->withPosterData) {
            $response = array_merge($response, ['poster' => $this->getPosterDetails()]);
        }

        return $response;
    }

    /**
     * Retrieve details of the poster.
     *
     * @return array|null An array containing details of the poster if available, otherwise mull.
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
     * Add expanded response data to the given response array.
     *
     * This method enriches the given response array with additional data for an expanded response,
     * including details about the poster, associated media files (images and videos), comments,
     * and counts of total media files, comments, and likes. The method takes the basic response
     * array as input and returns the expanded response array with the added data.
     *
     * @param array $response
     * @return void The expanded response array with additional data about the poster, media files,
     *               comments, and counts of total media files, comments, and likes.
     */
    private function addExtraInfo(array &$response): void
    {
        $response['total_medias'] = $this->resource->getMediaTotal();
        $response['total_comments'] = $this->resource->comments()->count();
        $response['total_likes'] = $this->resource->likes()->count();

        $response['medias'] = [
            'images' => $this->resource->getImages(),
            'videos' => $this->resource->getVideos(),
        ];

        $response['comments'] = $this->resource->getComments(Config::get('post.default_comment_count_on_post', 30));
        unset($response['poster_id']);
    }
}
