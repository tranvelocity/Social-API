<?php

namespace Modules\Post\app\Http\Controllers;

use Exception;
use Modules\Core\app\Http\Controllers\Controller;
use Modules\Core\app\Resources\ResourceCollection;
use Modules\Post\app\Http\Requests\SearchPostSocialRequest;
use Modules\Post\app\Resources\PostSocialResource;
use Modules\Post\app\Services\PostSocialService;

class PostSocialController extends Controller
{
    private PostSocialService $postSocialService;

    /**
     * Constructor.
     */
    public function __construct(PostSocialService $postSocialService)
    {
        $this->postSocialService = $postSocialService;
    }

    /**
     * Get the social of posts based on search criteria.
     *
     * @Route("/posts-social", methods={"GET"})
     *
     * @param SearchPostSocialRequest $request The search request.
     *
     * @return ResourceCollection The resource collection containing post items.
     * @throws Exception
     */
    public function index(SearchPostSocialRequest $request): ResourceCollection
    {
        // Retrieve role and search criteria from the request
        $role = $request->get('role');
        $userId = $this->getAuthorizedUserId();
        $inputs = $request->getSearchCriteria($role);

        $result = $this->postSocialService->getSocialPosts($inputs, $this->getAuthorizedAdminId(), $role, $userId);

        $resourceCollection = $result['data']->map(function ($postItem) use ($role) {
            return new PostSocialResource($postItem, $role);
        });

        return (new ResourceCollection($resourceCollection))->withPagination($inputs['offset'], $inputs['limit'], $result['total']);
    }
}
