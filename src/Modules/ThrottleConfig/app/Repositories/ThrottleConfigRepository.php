<?php

namespace Modules\ThrottleConfig\app\Repositories;

use Modules\Core\app\Repositories\Repository;
use Modules\ThrottleConfig\app\Models\ThrottleConfig;

class ThrottleConfigRepository extends Repository implements ThrottleConfigRepositoryInterface
{
    /**
     * Retrieve the ThrottleConfig resource associated with a specific admin UUID.
     *
     * @param string $adminUuid The UUID of the admin whose ThrottleConfig is to be retrieved.
     *
     * @return ThrottleConfig|null The ThrottleConfig object if found, or null if not found.
     */
    public function getThrottleConfig(string $adminUuid): ?ThrottleConfig
    {
        return ThrottleConfig::query()
            ->where('admin_uuid', '=', $adminUuid)
            ->first();
    }
}
