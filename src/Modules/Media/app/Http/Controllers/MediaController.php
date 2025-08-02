<?php

namespace Modules\Media\app\Http\Controllers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Modules\Core\app\Exceptions\ForbiddenException;
use Modules\Core\app\Http\Controllers\Controller;
use Modules\Core\app\Resources\ResourceCollection;
use Modules\Core\app\Responses\NoContentResponse;
use Modules\Media\app\Http\Requests\CreateMediaRequest;
use Modules\Media\app\Http\Requests\SearchMediaRequest;
use Modules\Media\app\Http\Requests\UpdateMediaRequest;
use Modules\Media\app\Resources\MediaResource;
use Modules\Media\app\Services\MediaService;
use Modules\Role\app\Services\RolePermissionService;
use Symfony\Component\HttpFoundation\JsonResponse;

class MediaController extends Controller
{
    private MediaService $mediaService;

    /**
     * Constructor.
     */
    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Display a listing of the resource.
     *
     * @Route("/medias", methods={"GET"})
     *
     * @param SearchMediaRequest $request The search request for filtering medias.
     *
     * @return ResourceCollection The resource collection containing media items.
     *
     * @throw ForbiddenException If the user does not have permission to retrieve the list of medias.
     */
    public function index(SearchMediaRequest $request): ResourceCollection
    {
        //@TODO This should be moved to a middleware in a refactoring task
        if (!RolePermissionService::isAcceptable($request->get('role'), Config::get('api.version') . '/' . Config::get('api.endpoints.medias'), 'GET')) {
            throw new ForbiddenException(Lang::get('media::errors.access_denied_retrieval_list_permission'));
        }

        $params = $request->validation();

        $result = $this->mediaService->getMedias($params);

        $resourceCollection = $result['data']->map(function ($mediaItem) {
            return new MediaResource($mediaItem);
        });

        return (new ResourceCollection($resourceCollection))->withPagination($params['offset'], $params['limit'], $result['total']);
    }

    /**
     * Retrieve the specified media resource by given ID.
     *
     * @Route("/medias/{id}", methods={"GET"})
     *
     * @param string $id The ID of the media to retrieve.
     *
     * @return MediaResource The media resource.
     *
     * @throw ForbiddenException If the user does not have permission to retrieve the specified media resource.
     */
    public function show(string $id): MediaResource
    {
        //@TODO This should be moved to a middleware in a refactoring task
        if (!RolePermissionService::isAcceptable(\request()->get('role'), Config::get('api.version') . '/' . Config::get('api.endpoints.media_item'), 'GET')) {
            throw new ForbiddenException(Lang::get('media::errors.access_denied_retrieval_item_permission'));
        }

        $media = $this->mediaService->getMedia($id);

        return new MediaResource($media);
    }

    /**
     * Store a newly created media in storage.
     *
     * @Route("/media", methods={"POST"})
     *
     * @param CreateMediaRequest $request The request containing the parameters for creating a media.
     *
     * @return MediaResource The newly created media resource.
     *
     * @throw ForbiddenException If the user does not have permission to create a new media.
     */
    public function store(CreateMediaRequest $request): MediaResource
    {
        //@TODO This should be moved to a middleware in a refactoring task
        if (!RolePermissionService::isAcceptable(\request()->get('role'), Config::get('api.version') . '/' . Config::get('api.endpoints.medias'), 'POST')) {
            throw new ForbiddenException(Lang::get('media::errors.access_denied_creation_permission'));
        }

        $file = $request->file('file');
        $thumbnail = $request->file('thumbnail');

        $media = $this->mediaService->createMedia($file, $thumbnail);

        return new MediaResource($media);
    }

    /**
     * Update the specified media in storage.
     *
     * @Route("/1/medias/{id}", methods={"PUT"})
     *
     * @param UpdateMediaRequest $request The request containing the parameters for updating a media.
     * @param string $id The ID of the media to update.
     *
     * @return MediaResource The updated media resource.
     *
     * @throw ForbiddenException If the user does not have permission to update the specified media resource.
     */
    public function update(UpdateMediaRequest $request, string $id): MediaResource
    {
        //@TODO This should be moved to a middleware in a refactoring task
        if (!RolePermissionService::isAcceptable(\request()->get('role'), Config::get('api.version') . '/' . Config::get('api.endpoints.media_item'), 'PUT')) {
            throw new ForbiddenException(Lang::get('media::errors.access_denied_edit_permission'));
        }

        $params = $request->validation();

        $media = $this->mediaService->updateMedia($id, $params, $this->getAuthorizedAdminId());

        return new MediaResource($media);
    }

    /**
     * Remove the specified media from storage.
     *
     * @Route("/medias/{id}", methods={"DELETE"})
     *
     * @param string $id The ID of the media to delete.
     *
     * @return JsonResponse A JSON response indicating success.
     *
     * @throw ForbiddenException If the user does not have permission to delete the specified media resource.
     */
    public function destroy(string $id): JsonResponse
    {
        //@TODO This should be moved to a middleware in a refactoring task
        if (!RolePermissionService::isAcceptable(\request()->get('role'), Config::get('api.version') . '/' . Config::get('api.endpoints.media_item'), 'DELETE')) {
            throw new ForbiddenException(Lang::get('media::errors.access_denied_deletion_permission'));
        }

        $this->mediaService->deleteMedia($id);

        return NoContentResponse::make();
    }
}
