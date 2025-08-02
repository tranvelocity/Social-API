<?php

namespace Modules\ThrottleConfig\app\Repositories;

use Modules\Core\app\Repositories\RepositoryInterface;
use Modules\ThrottleConfig\app\Models\ThrottleConfig;

interface ThrottleConfigRepositoryInterface extends RepositoryInterface
{
    /**
     * Retrieve the ThrottleConfig resource associated with a specific admin UUID.
     *
     * @param string $adminUuid The UUID of the admin whose ThrottleConfig is to be retrieved.
     *
     * @return ThrottleConfig|null The ThrottleConfig object if found, or null if not found.
     */
    public function getThrottleConfig(string $adminUuid): ?ThrottleConfig;
}
