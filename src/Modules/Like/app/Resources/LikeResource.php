<?php

namespace Modules\Like\app\Resources;

use Illuminate\Http\Request;
use Modules\Core\app\Resources\JsonResource;

class LikeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $response = parent::toArray($request);
        unset($response['updated_at'], $response['deleted_at']);
        $response['created_at'] = $this->formatDateTime($this->resource->created_at);

        return $response;
    }
}
