<?php

namespace Modules\Like\app\Http\Controllers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Modules\Core\app\Exceptions\ForbiddenException;
use Modules\Core\app\Http\Controllers\Controller;
use Modules\Core\app\Http\Requests\JsonRequest;
use Modules\Core\app\Resources\ResourceCollection;
use Modules\Like\app\Resources\LikePostResource;
use Modules\Like\app\Resources\LikeResource;
use Modules\Like\app\Services\LikeService;
use Modules\Role\app\Services\RolePermissionService;

/**
 * Class LikeController
 * This class handles HTTP requests related to likes on posts.
 */
class LikeController extends Controller
{
    /**
     * @var LikeService The service responsible for like-related operations.
     */
    private LikeService $likeService;

    /**
     * LikeController constructor.
     *
     * @param LikeService $likeService The LikeService instance for handling like-related operations.
     */
    public function __construct(LikeService $likeService)
    {
        $this->likeService = $likeService;
    }

    /**
     * Display a listing of the resource.
     *
     * @Route("/posts/{postId}/likes", methods={"GET"})
     *
     * @param JsonRequest $request The request for filtering likes.
     * @return ResourceCollection The resource collection containing like items.
     * @throws ForbiddenException If the user does not have permission to retrieve the list of likes.
     */
    public function index(JsonRequest $request, string $postId): ResourceCollection
    {
        if (!RolePermissionService::isAcceptable($request->get('role'), Config::get('api.version') . '/' . Config::get('api.endpoints.post_likes'), 'GET')) {
            throw new ForbiddenException(Lang::get('like::errors.access_denied_retrieval_list_permission'));
        }

        $params = $request->validation();
        $params['post_id'] = $postId;

        $result = $this->likeService->getLikes($params, $this->getAuthorizedAdminId());

        $resourceCollection = $result['data']->map(function ($like) {
            return new LikeResource($like);
        });

        return (new ResourceCollection($resourceCollection))->withPagination($params['offset'], $params['limit'], $result['total']);
    }

    /**
     * Store a newly created like in storage.
     *
     * @Route("/posts/{postId}/like", methods={"POST"})
     *
     * @param string $postId The ID of the post for which the like is created.
     * @return LikePostResource The newly created like resource.
     */
    public function like(string $postId): LikePostResource
    {
        $role = \request()->get('role');
        if (!RolePermissionService::isAcceptable($role, Config::get('api.version') . '/' . Config::get('api.endpoints.post_like'), 'POST')) {
            throw new ForbiddenException(Lang::get('like::errors.access_denied_like_permission'));
        }

        $result = $this->likeService->like($postId, $this->getAuthorizedUserId(), $this->getAuthorizedAdminId(), $role);

        return new LikePostResource($result);
    }

    /**
     * Remove like history from storage.
     *
     * @Route("/posts/{postId}/unlike", methods={"POST"})
     *
     * @param string $postId The ID of the post for which the like is created.
     * @return LikePostResource The newly created like resource.
     */
    public function unlike(string $postId): LikePostResource
    {
        $role = \request()->get('role');
        if (!RolePermissionService::isAcceptable($role, Config::get('api.version') . '/' . Config::get('api.endpoints.post_like'), 'POST')) {
            throw new ForbiddenException(Lang::get('like::errors.access_denied_unlike_permission'));
        }

        $userId = $this->getAuthorizedUserId();
        $result = $this->likeService->unlike($postId, $userId, $this->getAuthorizedAdminId(), $role);

        return new LikePostResource($result);
    }
}
