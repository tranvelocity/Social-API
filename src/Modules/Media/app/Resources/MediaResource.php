<?php

namespace Modules\Media\app\Resources;

use Modules\AWS\S3\Services\S3FileService;
use Modules\Core\app\Resources\JsonResource;
use Illuminate\Http\Request;

class MediaResource extends JsonResource
{
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
            'post_id' => $this->resource->post_id,
            'type' => $this->resource->type,
            'path' => S3FileService::generateAccessibleFileUrl($this->resource->path),
            'thumbnail' => $this->resource->thumbnail ? S3FileService::generateAccessibleFileUrl($this->resource->thumbnail) : null,
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];
    }
}
