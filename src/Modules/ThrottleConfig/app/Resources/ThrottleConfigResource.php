<?php

namespace Modules\ThrottleConfig\app\Resources;

use Modules\Core\app\Resources\JsonResource;

class ThrottleConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'admin_uuid' => $this->resource->admin_uuid,
            'time_frame_minutes' => $this->resource->time_frame_minutes,
            'max_comments' => $this->resource->max_comments,
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];
    }
}
