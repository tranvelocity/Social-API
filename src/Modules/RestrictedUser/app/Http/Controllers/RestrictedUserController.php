<?php

namespace Modules\RestrictedUser\app\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\app\Exceptions\ForbiddenException;
use Modules\Core\app\Http\Controllers\Controller;
use Modules\Core\app\Resources\ResourceCollection;
use Modules\Core\app\Responses\NoContentResponse;
use Modules\RestrictedUser\app\Http\Requests\CreateRestrictedUserRequest;
use Modules\RestrictedUser\app\Http\Requests\SearchRestrictedUserRequest;
use Modules\RestrictedUser\app\Http\Requests\UpdateRestrictedUserRequest;
use Modules\RestrictedUser\app\Resources\RestrictedUserResource;
use Modules\RestrictedUser\app\Services\RestrictedUserService;

/**
 * Controller for managing RestrictedUser resources.
 *
 * Handles CRUD operations and enforces permission checks for the RestrictedUser entities.
 */
class RestrictedUserController extends Controller
{
    /**
     * Service to manage RestrictedUser operations.
     *
     * @var RestrictedUserService
     */
    private RestrictedUserService $restrictedUserService;

    /**
     * Constructor.
     *
     * @param RestrictedUserService $restrictedUserService The service that handles RestrictedUser business logic.
     */
    public function __construct(RestrictedUserService $restrictedUserService)
    {
        $this->restrictedUserService = $restrictedUserService;
    }

    /**
     * Display a listing of RestrictedUser resources.
     *
     * @Route("/restricted-users", methods={"GET"})
     *
     * @param SearchRestrictedUserRequest $request The request containing search filters for RestrictedUsers.
     *
     * @return ResourceCollection The resource collection containing paginated RestrictedUser items.
     *
     * @throws ForbiddenException If the user does not have permission to retrieve the list of RestrictedUsers.
     */
    public function index(SearchRestrictedUserRequest $request): ResourceCollection
    {
        $inputs = $request->validation();
        $inputs['admin_uuid'] = $this->getAuthorizedAdminId();
        $result = $this->restrictedUserService->getRestrictedUsers($inputs);

        $resourceCollection = $result['data']->map(function ($restrictedUserItem) {
            return new RestrictedUserResource($restrictedUserItem);
        });

        return (new ResourceCollection($resourceCollection))
            ->withPagination($inputs['offset'], $inputs['limit'], $result['total']);
    }

    /**
     * Retrieve a specific RestrictedUser resource by its ID.
     *
     * @Route("/restricted-users/{id}", methods={"GET"})
     *
     * @param string $id The ID of the RestrictedUser to retrieve.
     *
     * @return RestrictedUserResource The RestrictedUser resource.
     */
    public function show(string $id): RestrictedUserResource
    {
        $adminId = $this->getAuthorizedAdminId();
        $restrictedUser = $this->restrictedUserService->getRestrictedUser($id, $adminId);

        return new RestrictedUserResource($restrictedUser);
    }

    /**
     * Store a newly created RestrictedUser resource.
     *
     * @Route("/restricted-users", methods={"POST"})
     *
     * @param CreateRestrictedUserRequest $request The request containing the parameters for creating a new RestrictedUser.
     *
     * @return RestrictedUserResource The newly created RestrictedUser resource.
     */
    public function store(CreateRestrictedUserRequest $request): RestrictedUserResource
    {
        $inputs = $request->validation();
        $adminUuid = $this->getAuthorizedAdminId();
        $restrictedUser = $this->restrictedUserService->createRestrictedUser($inputs, $adminUuid);

        return new RestrictedUserResource($restrictedUser);
    }

    /**
     * Update the specified RestrictedUser resource.
     *
     * @Route("/restricted-users/{id}", methods={"PUT"})
     *
     * @param UpdateRestrictedUserRequest $request The request containing the parameters for updating a RestrictedUser.
     * @param string $id The ID of the RestrictedUser to update.
     *
     * @return RestrictedUserResource The updated RestrictedUser resource.
     */
    public function update(UpdateRestrictedUserRequest $request, string $id): RestrictedUserResource
    {
        $inputs = $request->validation();
        $adminUuid = $this->getAuthorizedAdminId();
        $restrictedUser = $this->restrictedUserService->updateRestrictedUser($id, $adminUuid, $inputs);

        return new RestrictedUserResource($restrictedUser);
    }

    /**
     * Remove the specified RestrictedUser resource from storage.
     *
     * @Route("/restricted-users/{id}", methods={"DELETE"})
     *
     * @param string $id The ID of the RestrictedUser to delete.
     *
     * @return JsonResponse A JSON response indicating successful deletion.
     */
    public function destroy(string $id): JsonResponse
    {
        $adminId = $this->getAuthorizedAdminId();
        $this->restrictedUserService->deleteRestrictedUser($id, $adminId);

        return NoContentResponse::make();
    }
}
