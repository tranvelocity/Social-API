<?php

namespace Modules\Core\app\Resources;

use Illuminate\Http\Resources\Json\JsonResource as BaseResource;
use Modules\Core\app\Traits\WithApiWrapping;
use Modules\Core\app\Traits\WithDataFormatters;

class JsonResource extends BaseResource
{
    use WithDataFormatters;
    use WithApiWrapping;
}
