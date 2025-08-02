<?php

namespace Modules\Comment\app\Resources;

use Illuminate\Http\Request;
use Modules\Comment\app\Models\Comment;
use Modules\Core\app\Resources\JsonResource;
use Modules\Post\app\Resources\PostResource;

class CommentResource extends JsonResource
{
    private bool $withExtraData;

    public function __construct(
        Comment $comment,
        bool $withExtraData = true,
    ) {
        parent::__construct($comment);
        $this->withExtraData = $withExtraData;
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
            'user_id' => $this->resource->getUserId(),
            'nickname' => $this->resource->getNickname(),
            'avatar' => $this->resource->getAvatar(),
            'post_id' => $this->resource->post_id,
            'comment' => $this->resource->comment,
            'is_hidden' => boolval($this->resource->is_hidden),
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];

        if ($this->withExtraData) {
            unset($response['post_id']);
            $response['total_comments'] = $this->resource->getCommentTotal();

            $response += [
                'post' => new PostResource($this->resource->post, false, false),
            ];
        }

        return $response;
    }
}
