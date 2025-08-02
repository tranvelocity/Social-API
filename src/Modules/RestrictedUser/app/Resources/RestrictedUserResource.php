<?php

namespace Modules\RestrictedUser\app\Resources;

use Modules\Core\app\Resources\JsonResource;

class RestrictedUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'admin_uuid' => $this->resource->admin_uuid,
            'remarks' => $this->resource->remarks,
            'user_id' => $this->resource->user_id,
            'nickname' => $this->resource->getNickname(),
            'avatar' => $this->resource->getAvatar(),
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];
    }
}
