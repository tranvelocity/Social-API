<?php

namespace Modules\Like\app\Resources;

use Illuminate\Http\Request;
use Modules\Core\app\Resources\JsonResource;

class LikePostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'post_id' => $this->resource->getPostId(),
            'user_id' => $this->resource->getUserId(),
            'action' => $this->resource->getAction(),
            'total_likes' => $this->resource->getTotalLikes(),
        ];
    }
}
