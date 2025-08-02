<?php

namespace Modules\Core\app\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection as BaseResourceCollection;
use Modules\Core\app\Traits\WithApiWrapping;
use Modules\Core\app\Traits\WithDataFormatters;

class ResourceCollection extends BaseResourceCollection
{
    use WithDataFormatters;
    use WithApiWrapping;

    protected int $offset;
    protected int $limit;
    protected int  $total;

    public function withPagination(int $offset = 0, int $limit = 30, int $total = 0): self
    {
        $this->offset = $offset;
        $this->limit = $limit;
        $this->total = $total;

        return $this;
    }
}
