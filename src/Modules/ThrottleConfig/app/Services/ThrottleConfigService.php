<?php

namespace Modules\ThrottleConfig\app\Services;

use Illuminate\Support\Facades\Lang;
use Modules\Core\app\Exceptions\ConflictException;
use Modules\Core\app\Exceptions\ResourceNotFoundException;
use Modules\ThrottleConfig\app\Models\ThrottleConfig;
use Modules\ThrottleConfig\app\Repositories\ThrottleConfigRepositoryInterface;

class ThrottleConfigService
{
    protected ThrottleConfigRepositoryInterface $throttleConfigRepository;

    /**
     * Constructor to initialize the ThrottleConfigService with a repository instance.
     *
     * @param ThrottleConfigRepositoryInterface $throttleConfigRepository The repository responsible for interacting with ThrottleConfig data.
     */
    public function __construct(
        ThrottleConfigRepositoryInterface $throttleConfigRepository
    ) {
        $this->throttleConfigRepository = $throttleConfigRepository;
    }

    /**
     * Retrieve the ThrottleConfig for a given admin.
     *
     * @param string $adminUuid The unique identifier of the admin whose ThrottleConfig is to be retrieved.
     *
     * @return ThrottleConfig The retrieved ThrottleConfig object associated with the provided admin.
     *
     * @throws ResourceNotFoundException If no ThrottleConfig is found for the given admin.
     */
    public function getThrottleConfig(string $adminUuid): ThrottleConfig
    {
        $throttleConfig = $this->throttleConfigRepository->getThrottleConfig($adminUuid);

        if (!$throttleConfig) {
            throw new ResourceNotFoundException(Lang::get('throttleconfig::errors.throttle_config_not_found'));
        }

        return $throttleConfig;
    }

    /**
     * Create a new ThrottleConfig for a given admin.
     *
     * @param array $params The array of parameters to create the new ThrottleConfig.
     * @param string $adminUuid The unique identifier of the admin for whom the ThrottleConfig is being created.
     *
     * @return ThrottleConfig The newly created ThrottleConfig object.
     *
     * @throws ConflictException If a ThrottleConfig already exists for the given admin.
     */
    public function createThrottleConfig(array $params, string $adminUuid): ThrottleConfig
    {
        $throttleConfig = $this->throttleConfigRepository->getThrottleConfig($adminUuid);

        if ($throttleConfig) {
            throw new ConflictException(Lang::get('throttleconfig::errors.throttle_config_already_exists'));
        }

        $params['admin_uuid'] = $adminUuid;

        return $this->throttleConfigRepository->create(new ThrottleConfig(), $params);
    }

    /**
     * Delete the ThrottleConfig for a given admin.
     *
     * @param string $adminUuid The unique identifier of the admin whose ThrottleConfig is to be deleted.
     *
     * @return void
     *
     * @throws ResourceNotFoundException If no ThrottleConfig is found for the given admin.
     */
    public function deleteThrottleConfig(string $adminUuid): void
    {
        $throttleConfig = $this->throttleConfigRepository->getThrottleConfig($adminUuid);

        if (!$throttleConfig) {
            throw new ResourceNotFoundException(Lang::get('throttleconfig::errors.throttle_config_not_found'));
        }

        $this->throttleConfigRepository->delete($throttleConfig);
    }
}
