<?php

namespace Modules\Admin\app\Repositories;

use Modules\Admin\app\Models\Admin;

interface AdminRepositoryInterface
{
    public function findById(string $id): ?Admin;
}
