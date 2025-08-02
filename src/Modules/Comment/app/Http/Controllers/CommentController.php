<?php

namespace Modules\Comment\app\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Modules\Comment\app\Http\Requests\CreateCommentRequest;
use Modules\Comment\app\Http\Requests\SearchCommentRequest;
use Modules\Comment\app\Http\Requests\UpdateCommentRequest;
use Modules\Comment\app\Resources\CommentResource;
use Modules\Comment\app\Services\CommentService;
use Modules\Core\app\Exceptions\ForbiddenException;
use Modules\Core\app\Http\Controllers\Controller;
use Modules\Core\app\Resources\ResourceCollection;
use Modules\Core\app\Responses\NoContentResponse;
use Modules\Role\app\Services\RolePermissionService;

/**
 * Class CommentController
 * This class handles HTTP requests related to comments on posts.
 */
class CommentController extends Controller
{
    /**
     * @var CommentService The service responsible for comment-related operations.
     */
    private CommentService $commentService;

    /**
     * CommentController constructor.
     *
     * @param CommentService $commentService The CommentService instance for handling comment-related operations.
     */
    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Display a listing of the resource.
     *
     * @Route("/posts/{postId}/comments", methods={"GET"}))
     *
     * @param SearchCommentRequest $request The search request for filtering comments.
     * @return ResourceCollection The resource collection containing comment items.
     */
    public function index(SearchCommentRequest $request, string $postId): ResourceCollection
    {
        $inputs = $request->validation();

        $result = $this->commentService->getComments($postId, $inputs, $this->getAuthorizedAdminId());

        $resourceCollection = $result['data']->map(function ($commentItem) {
            return new CommentResource($commentItem, false);
        });

        return (new ResourceCollection($resourceCollection))->withPagination($inputs['offset'], $inputs['limit'], $result['total']);
    }

    /**
     * Retrieve the specified comment resource by given ID.
     *
     * @Route("/posts/{postId}/comments/{id}", methods={"GET"}))
     *
     * @param string $id The ID of the comment to retrieve.
     * @return CommentResource The comment resource.
     *
     * @throw ForbiddenException If the user does not have permission to retrieve the specified post resource.
     */
    public function show(string $postId, string $id): CommentResource
    {
        $comment = $this->commentService->getComment($postId, $id, $this->getAuthorizedAdminId());

        return new CommentResource($comment);
    }

    /**
     * Store a newly created comment in storage.
     *
     * @Route("/posts/{postId}/comment", methods={"POST"}))
     *
     * @param string $postId The ID of the post for which the comment is created.
     * @param CreateCommentRequest $request The request containing the parameters for creating a comment.
     * @return CommentResource The newly created comment resource.
     *
     * @throw ForbiddenException If the user does not have permission to create a new comment.
     */
    public function store(string $postId, CreateCommentRequest $request): CommentResource
    {
        //@TODO This should be moved to a middleware in a refactoring task
        $role = \request()->get('role');
        if (!RolePermissionService::isAcceptable($role, Config::get('api.version') . '/' . Config::get('api.endpoints.post_comments'), 'POST')) {
            throw new ForbiddenException(Lang::get('comment::errors.access_denied_creation_permission'));
        }

        $inputs = $request->getValidatedParams();
        $adminUuid = $this->getAuthorizedAdminId();
        $inputs['user_id'] = $this->getAuthorizedUserId();

        $comment = $this->commentService->createComment($postId, $inputs, $adminUuid, $role);

        return new CommentResource($comment);
    }

    /**
     * Update the specified comment in storage.
     *  - Administrators have permission to edit comments of everyone
     *  - Posters hae permission to edit their own comments or user"s comments
     *  - Commenter's who have permission to edit only their comments.
     *
     * @Route("/posts/{postId}/comments/{id}", methods={"PUT"}))
     *
     * @param UpdateCommentRequest $request The request containing the parameters for updating a comment.
     * @param string $postId The ID of the post associated with the comment.
     * @param string $id The ID of the comment to update.
     * @return CommentResource The updated comment resource.
     *
     * @throw ForbiddenException If the user does not have permission to update the comment.
     */
    public function update(UpdateCommentRequest $request, string $postId, string $id): CommentResource
    {
        $role = \request()->get('role');
        //@TODO This should be moved to a middleware in a refactoring task
        if (!RolePermissionService::isAcceptable($role, Config::get('api.version') . '/' . Config::get('api.endpoints.post_comment'), 'PUT')) {
            throw new ForbiddenException(Lang::get('comment::errors.access_denied_edit_permission'));
        }

        $inputs = $request->validation();
        $adminUuid = $this->getAuthorizedAdminId();
        $inputs['user_id'] = $this->getAuthorizedUserId();

        $comment = $this->commentService->updateComment($id, $postId, $inputs, $adminUuid, $role);

        return new CommentResource($comment);
    }

    /**
     * Remove the specified comment from storage.
     *  - Posters hae permission to edit their own comments or user"s comments
     *  - Commenter's who have permission to edit only their comments.
     *
     * @Route("/posts/{postId}/comments/{id}", methods={"DELETE"}))
     *
     * @param string $postId The ID of the post associated with the comment.
     * @param string $id The ID of the comment to delete.
     * @return JsonResponse A JSON response indicating success.
     *
     * @throw ForbiddenException If the user does not have permission to delete the specified comment resource.
     */
    public function destroy(string $postId, string $id): JsonResponse
    {
        //@TODO This should be moved to a middleware in a refactoring task
        if (!RolePermissionService::isAcceptable(\request()->get('role'), Config::get('api.version') . '/' . Config::get('api.endpoints.post_comment'), 'DELETE')) {
            throw new ForbiddenException(Lang::get('comment::errors.access_denied_delete_permission'));
        }

        $adminUuid = $this->getAuthorizedAdminId();
        $userId = $this->getAuthorizedUserId();

        $this->commentService->deleteComment($postId, $id, $adminUuid, $userId);

        return NoContentResponse::make();
    }

    /**
     * Publishes a comment associated with a post.
     *
     * @Route("/posts/{postId}/comments/{id}/publish", methods={"POST"}))
     *
     * @param string $postId The ID of the post associated with the comment.
     * @param string $id The ID of the comment to publish.
     * @return CommentResource The published comment resource.
     *
     * @throw ForbiddenException If the user does not have permission to publish the comment.
     */
    public function publishComment(string $postId, string $id): CommentResource
    {
        //@TODO This should be moved to a middleware in a refactoring task
        if (!RolePermissionService::isAcceptable(\request()->get('role'), Config::get('api.version') . '/' . Config::get('api.endpoints.post_comment_publish'), 'POST')) {
            throw new ForbiddenException(Lang::get('comment::errors.access_denied_publish_permission'));
        }

        $adminUuid = $this->getAuthorizedAdminId();
        $userId = $this->getAuthorizedUserId();

        $comment = $this->commentService->publishComment($id, $postId, $adminUuid, $userId);

        return new CommentResource($comment);
    }

    /**
     * Unpublishes a comment associated with a post.
     *
     * @Route("/posts/{postId}/comments/{id}/unpublish", methods={"POST"}))
     *
     * @param string $postId The ID of the post associated with the comment.
     * @param string $id The ID of the comment to unpublish.
     * @return CommentResource The unpublished comment resource.
     *
     * @throw ForbiddenException If the user does not have permission to unpublish the comment.
     */
    public function unpublishComment(string $postId, string $id): CommentResource
    {
        //@TODO This should be moved to a middleware in a refactoring task
        if (!RolePermissionService::isAcceptable(\request()->get('role'), Config::get('api.version') . '/' . Config::get('api.endpoints.post_comment_unpublish'), 'POST')) {
            throw new ForbiddenException(Lang::get('comment::errors.access_denied_unpublish_permission'));
        }

        $adminUuid = $this->getAuthorizedAdminId();
        $userId = $this->getAuthorizedUserId();

        $comment = $this->commentService->unpublishComment($id, $postId, $adminUuid, $userId);

        return new CommentResource($comment);
    }
}
