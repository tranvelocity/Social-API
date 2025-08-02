<?php

namespace Modules\ThrottleConfig\app\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\app\Http\Controllers\Controller;
use Modules\Core\app\Http\Requests\JsonRequest;
use Modules\Core\app\Responses\NoContentResponse;
use Modules\ThrottleConfig\app\Http\Requests\CreateThrottleConfigRequest;
use Modules\ThrottleConfig\app\Resources\ThrottleConfigResource;
use Modules\ThrottleConfig\app\Services\ThrottleConfigService;

class ThrottleConfigController extends Controller
{
    private ThrottleConfigService $throttleConfigService;

    /**
     * Constructor to initialize the ThrottleConfigService instance.
     *
     * @param ThrottleConfigService $throttleConfigService The service responsible for throttle configuration operations.
     */
    public function __construct(ThrottleConfigService $throttleConfigService)
    {
        $this->throttleConfigService = $throttleConfigService;
    }

    /**
     * Retrieve and display a listing of throttle configuration resources.
     *
     * @Route("/throttle-config", methods={"GET"})
     *
     * @param JsonRequest $request The HTTP request instance containing query parameters.
     *
     * @return ThrottleConfigResource A resource collection containing throttle configuration details.
     */
    public function index(JsonRequest $request): ThrottleConfigResource
    {
        $adminUuid = $this->getAuthorizedAdminId();

        $throttleConfig = $this->throttleConfigService->getThrottleConfig($adminUuid);

        return new ThrottleConfigResource($throttleConfig);
    }

    /**
     * Create a new throttle configuration resource.
     *
     * @Route("/throttle-config", methods={"POST"})
     *
     * @param CreateThrottleConfigRequest $request The request containing validated input data for creating a throttle configuration.
     *
     * @return ThrottleConfigResource The newly created throttle configuration resource.
     */
    public function store(CreateThrottleConfigRequest $request): ThrottleConfigResource
    {
        $inputs = $request->validation();

        $adminUuid = $this->getAuthorizedAdminId();

        $throttleConfig = $this->throttleConfigService->createThrottleConfig($inputs, $adminUuid);

        return new ThrottleConfigResource($throttleConfig);
    }

    /**
     * Delete the specified throttle configuration resource.
     *
     * @Route("/throttle-config/{id}", methods={"DELETE"})
     *
     * @return JsonResponse A JSON response indicating successful deletion.
     */
    public function destroy(): JsonResponse
    {
        $adminUuid = $this->getAuthorizedAdminId();

        $this->throttleConfigService->deleteThrottleConfig($adminUuid);

        return NoContentResponse::make();
    }
}
