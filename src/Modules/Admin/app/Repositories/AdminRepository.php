<?php

namespace Modules\Admin\app\Repositories;

use Modules\Admin\app\Models\Admin;

class AdminRepository implements AdminRepositoryInterface
{
    public function findById(string $id): ?Admin
    {
        return Admin::find($id);
    }

    public function getAdminByApiKey(string $apiKey): ?Admin
    {
        return Admin::where('api_key', $apiKey)
            ->first();
    }
}
