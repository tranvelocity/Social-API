<?php

namespace Modules\Core\app\Resources;

class CustomResource extends JsonResource
{
    public function toArray($request)
    {
        return $this->resource;
    }
}
