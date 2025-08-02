<?php

namespace Modules\Poster\app\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Modules\Core\app\Exceptions\ForbiddenException;
use Modules\Core\app\Http\Controllers\Controller;
use Modules\Core\app\Resources\ResourceCollection;
use Modules\Core\app\Responses\NoContentResponse;
use Modules\Poster\app\Http\Requests\CreatePosterRequest;
use Modules\Poster\app\Http\Requests\SearchPosterRequest;
use Modules\Poster\app\Http\Requests\UpdatePosterRequest;
use Modules\Poster\app\Resources\PosterResource;
use Modules\Poster\app\Services\PosterCreationService;
use Modules\Poster\app\Services\PosterDeletionService;
use Modules\Poster\app\Services\PosterRetrievalService;
use Modules\Poster\app\Services\PosterUpdateService;

class PosterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @Route("/posters", methods={"GET"})
     *
     * @param SearchPosterRequest $request The search request for filtering posts.
     *
     * @return ResourceCollection The resource collection containing poster items.
     *
     * @throws ForbiddenException If the user does not have permission to retrieve the list of posts.
     */
    public function index(SearchPosterRequest $request): ResourceCollection
    {
        $params = $request->validation();
        $params['admin_uuid'] = $this->getAuthorizedAdminId();

        $result = App::make(PosterRetrievalService::class)->getPosters($params);

        $resourceCollection = $result['data']->map(function ($posterItem) {
            return new PosterResource($posterItem);
        });

        return (new ResourceCollection($resourceCollection))->withPagination($params['offset'], $params['limit'], $result['total']);
    }

    /**
     * Retrieve the specified poster resource by given ID.
     *
     * @Route("/posters/{id}", methods={"GET"})
     *
     * @param string $id The ID of the poster to retrieve.
     *
     * @return PosterResource The poster resource.
     *
     * @throw ForbiddenException If the user does not have permission to retrieve the specified poster resource.
     */
    public function show(string $id): PosterResource
    {
        $adminId = $this->getAuthorizedAdminId();

        $poster = App::make(PosterRetrievalService::class)->getPoster($id, $adminId);

        return new PosterResource($poster);
    }

    /**
     * Store a newly created poster in storage.
     *
     * @Route("/poster", methods={"POST"})
     *
     * @param CreatePosterRequest $request The request containing the parameters for creating a poster.
     *
     * @return PosterResource The newly created poster resource.
     *
     * @throw ForbiddenException If the user does not have permission to create a new poster.
     */
    public function store(CreatePosterRequest $request): PosterResource
    {
        $params = $request->validation();

        $adminUuid = $this->getAuthorizedAdminId();

        $poster = (App::make(PosterCreationService::class))($params, $adminUuid);

        return new PosterResource($poster);
    }

    /**
     * Update the specified poster in storage.
     *
     * @Route("/1/posters/{id}", methods={"PUT"})
     *
     * @param UpdatePosterRequest $request The request containing the parameters for updating a poster.
     * @param string $id The ID of the poster to update.
     *
     * @return PosterResource The updated poster resource.
     *
     * @throw ForbiddenException If the user does not have permission to update the specified poster resource.
     */
    public function update(UpdatePosterRequest $request, string $id): PosterResource
    {
        $params = $request->validation();

        $poster = (App::make(PosterUpdateService::class))($id, $params, $request->get(Config::get('tranauth.authorized_admin_id')));

        return new PosterResource($poster);
    }

    /**
     * Remove the specified poster from storage.
     *
     * @Route("/1/posters/{id}", methods={"DELETE"})
     *
     * @param string $id The ID of the poster to delete.
     *
     * @return JsonResponse A JSON response indicating success.
     *
     * @throw ForbiddenException If the user does not have permission to delete the specified poster resource.
     */
    public function destroy(string $id): JsonResponse
    {
        $adminId = $this->getAuthorizedAdminId();

        (App::make(PosterDeletionService::class))($id, $adminId);

        return NoContentResponse::make();
    }
}
