<?php

namespace Modules\Post\app\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Modules\Core\app\Exceptions\ForbiddenException;
use Modules\Core\app\Http\Controllers\Controller;
use Modules\Core\app\Resources\ResourceCollection;
use Modules\Core\app\Responses\NoContentResponse;
use Modules\Post\app\Http\Requests\CreatePostRequest;
use Modules\Post\app\Http\Requests\SearchPostRequest;
use Modules\Post\app\Http\Requests\UpdatePostRequest;
use Modules\Post\app\Resources\PostResource;
use Modules\Post\app\Services\PostService;
use Modules\Role\app\Services\RolePermissionService;

class PostController extends Controller
{
    private PostService $postService;

    /**
     * Constructor.
     */
    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    /**
     * Display a listing of the resource.
     *
     * @Route("/posts", methods={"GET"})
     *
     * @param SearchPostRequest $request The search request for filtering posts.
     *
     * @return ResourceCollection The resource collection containing post items.
     *
     * @throws ForbiddenException If the user does not have permission to retrieve the list of posts.
     */
    public function index(SearchPostRequest $request): ResourceCollection
    {
        $role = $request->get('role');

        //@TODO This should be moved to a middleware in a refactoring task
        if (!RolePermissionService::isAcceptable($role, Config::get('api.version') . '/' . Config::get('api.endpoints.posts'), 'GET')) {
            throw new ForbiddenException(Lang::get('post::errors.access_denied_retrieval_list_permission'));
        }

        $inputs = $request->validation();
        $inputs['admin_uuid'] = $this->getAuthorizedAdminId();

        $result = $this->postService->getPosts($inputs);
        $resourceCollection = $result['data']->map(function ($postItem) {
            return new PostResource($postItem, false, true);
        });

        return (new ResourceCollection($resourceCollection))->withPagination($inputs['offset'], $inputs['limit'], $result['total']);
    }

    /**
     * Retrieve the specified post resource by given ID.
     *
     * @Route("/posts/{id}", methods={"GET"})
     *
     * @param string $id The ID of the post to retrieve.
     *
     * @return PostResource The post resource.
     *
     * @throw ForbiddenException If the user does not have permission to retrieve the specified post resource.
     */
    public function show(string $id): PostResource
    {
        //@TODO This should be moved to a middleware in a refactoring task
        if (!RolePermissionService::isAcceptable(\request()->get('role'), Config::get('api.version') . '/' . Config::get('api.endpoints.post_item'), 'GET')) {
            throw new ForbiddenException(Lang::get('post::errors.access_denied_retrieval_item_permission'));
        }

        $adminId = $this->getAuthorizedAdminId();
        $post = $this->postService->getPost($id, $adminId);

        return new PostResource($post);
    }

    /**
     * Store a newly created post in storage.
     *
     * @Route("/post", methods={"POST"})
     *
     * @param CreatePostRequest $request The request containing the parameters for creating a post.
     *
     * @return PostResource The newly created post resource.
     *
     * @throw ForbiddenException If the user does not have permission to create a new post.
     */
    public function store(CreatePostRequest $request): PostResource
    {
        //@TODO This should be moved to a middleware in a refactoring task
        if (!RolePermissionService::isAcceptable(\request()->get('role'), Config::get('api.version') . '/' . Config::get('api.endpoints.posts'), 'POST')) {
            throw new ForbiddenException(Lang::get('post::errors.access_denied_creation_permission'));
        }

        $inputs = $request->getValidatedParams();
        $adminUuid = $this->getAuthorizedAdminId();
        $userId = $this->getAuthorizedUserId();

        $post = $this->postService->createPost($inputs, $adminUuid, $userId);

        return new PostResource($post);
    }

    /**
     * Update the specified post in storage.
     *
     * @Route("/1/posts/{id}", methods={"PUT"})
     *
     * @param UpdatePostRequest $request The request containing the parameters for updating a post.
     * @param string $id The ID of the post to update.
     *
     * @return PostResource The updated post resource.
     *
     * @throw ForbiddenException If the user does not have permission to update the specified post resource.
     */
    public function update(UpdatePostRequest $request, string $id): PostResource
    {
        //@TODO This should be moved to a middleware in a refactoring task
        if (!RolePermissionService::isAcceptable(\request()->get('role'), Config::get('api.version') . '/' . Config::get('api.endpoints.post_item'), 'PUT')) {
            throw new ForbiddenException(Lang::get('post::errors.access_denied_edit_permission'));
        }

        $inputs = $request->getValidatedParams();

        $post = $this->postService->updatePost($id, $request->get(Config::get('tranauth.authorized_admin_id')), $inputs);

        return new PostResource($post);
    }

    /**
     * Remove the specified post from storage.
     *
     * @Route("/1/posts/{id}", methods={"DELETE"})
     *
     * @param string $id The ID of the post to delete.
     *
     * @return JsonResponse A JSON response indicating success.
     *
     * @throw ForbiddenException If the user does not have permission to delete the specified post resource.
     */
    public function destroy(string $id): JsonResponse
    {
        //@TODO This should be moved to a middleware in a refactoring task
        if (!RolePermissionService::isAcceptable(\request()->get('role'), Config::get('api.version') . '/' . Config::get('api.endpoints.post_item'), 'DELETE')) {
            throw new ForbiddenException(Lang::get('post::errors.access_denied_deletion_permission'));
        }

        $adminId = $this->getAuthorizedAdminId();
        $this->postService->deletePost($id, $adminId);

        return NoContentResponse::make();
    }
}
