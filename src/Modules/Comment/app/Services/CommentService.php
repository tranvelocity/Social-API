<?php

namespace Modules\Comment\app\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Lang;
use Modules\Cache\app\Services\NGWordCacheService;
use Modules\Cache\app\Services\UserCacheService;
use Modules\Comment\app\Models\Comment;
use Modules\Comment\app\Repositories\CommentRepositoryInterface;
use Modules\Core\app\Constants\StatusCodeConstant;
use Modules\Core\app\Exceptions\ForbiddenException;
use Modules\Core\app\Exceptions\ResourceNotFoundException;
use Modules\Core\app\Exceptions\ValidationErrorException;
use Modules\Core\app\Helpers\StringHelper;
use Modules\Post\app\Repositories\PostRepositoryInterface;
use Modules\Poster\app\Repositories\PosterRepositoryInterface;
use Modules\RestrictedUser\app\Repositories\RestrictedUserRepositoryInterface;
use Modules\Role\app\Entities\Role;
use Modules\ThrottleConfig\app\Repositories\ThrottleConfigRepositoryInterface;

/**
 * Class CommentService.
 *
 * Service for managing comments and interacting with the comment repository.
 */
class CommentService
{
    /**
     * @var CommentRepositoryInterface The comment repository for interacting with comment data.
     */
    protected CommentRepositoryInterface $commentRepository;

    /**
     * @var PostRepositoryInterface The post repository for interacting with comment data.
     */
    protected PostRepositoryInterface $postRepository;
    protected PosterRepositoryInterface $posterRepository;
    private NGWordCacheService $ngWordCacheService;
    private UserCacheService $userCacheService;
    private RestrictedUserRepositoryInterface $restrictedUserRepository;
    private ThrottleConfigRepositoryInterface $throttleConfigRepository;

    /**
     * CommentService constructor.
     *
     * @param CommentRepositoryInterface $commentRepository The comment repository for interacting with comment data.
     * @param PostRepositoryInterface $postRepository The post repository for interacting with comment data.
     * @param PosterRepositoryInterface $posterRepository The poster repository for interacting with poster data.
     * @param UserCacheService $userCacheService The user cache service for caching user data.
     * @param NGWordCacheService $ngWordCacheService The NG word cache service for caching NG words.
     * @param ThrottleConfigRepositoryInterface $throttleConfigRepository The NG word cache service for caching NG words.
     * @param RestrictedUserRepositoryInterface $restrictedUserRepository The NG word cache service for caching NG words.
     */
    public function __construct(
        RestrictedUserRepositoryInterface $restrictedUserRepository,
        ThrottleConfigRepositoryInterface $throttleConfigRepository,
        CommentRepositoryInterface $commentRepository,
        PostRepositoryInterface $postRepository,
        PosterRepositoryInterface $posterRepository,
        UserCacheService $userCacheService,
        NGWordCacheService $ngWordCacheService
    ) {
        $this->commentRepository = $commentRepository;
        $this->postRepository = $postRepository;
        $this->posterRepository = $posterRepository;
        $this->userCacheService = $userCacheService;
        $this->ngWordCacheService = $ngWordCacheService;
        $this->restrictedUserRepository = $restrictedUserRepository;
        $this->throttleConfigRepository = $throttleConfigRepository;
    }

    /**
     * Retrieves comments and total count based on provided parameters.
     *
     * @param array $params Parameters for filtering comments.
     * @param string $adminUuid The admin ID associated with the comment.
     * @return array An array containing 'data' (array of comments) and 'total' (total count of comments).
     */
    public function getComments(string $postId, array $params, string $adminUuid): array
    {
        $this->validatePostExistence($postId, $adminUuid);
        $params['post_id'] = $postId;

        $comments = $this->commentRepository->getComments($params, $adminUuid);
        $this->setCommentersInfo($comments, $adminUuid);

        return [
            'data' => $comments,
            'total' => $this->commentRepository->getCommentTotal($params, $adminUuid),
        ];
    }

    /**
     * Retrieves a specific comment for a given post and comment ID.
     *
     * @param string $postId The ID of the post associated with the comment.
     * @param string $id The ID of the comment to retrieve.
     * @param string $adminUuid The admin ID associated with the comment.
     * @return Comment The retrieved comment.
     *
     * @throws ResourceNotFoundException If the comment is not found.
     */
    public function getComment(string $postId, string $id, string $adminUuid): Comment
    {
        $this->validatePostExistence($postId, $adminUuid);

        $comment = $this->getCommentOrFail($postId, $id, $adminUuid);

        $commentTotal = $this->commentRepository->getCommentTotal(['post_id' => $postId], $adminUuid);

        $comment->setCommentTotal($commentTotal);
        $this->setCommenterInfo($comment, $adminUuid);

        return $comment;
    }

    /**
     * Validates the comment for any prohibited words.
     *
     * @param string $adminUuid The UUID of the admin.
     * @param string $comment The comment to be validated.
     * @throws ValidationErrorException If the comment contains a prohibited word.
     */
    private function validateComment(string $adminUuid, string $comment): void
    {
        $ngWords = $this->ngWordCacheService->getNGWordsFromCache($adminUuid);

        foreach ($ngWords as $ngWord) {
            if (str_contains($comment, $ngWord)) {
                throw new ValidationErrorException(
                    StatusCodeConstant::BAD_REQUEST_VALIDATION_FAILED_CODE,
                    Lang::get('comment::errors.error_comment_contain_ng_word', ['word' => $ngWord])
                );
            }
        }
    }

    /**
     * Updates an existing comment for a given post and user.
     *
     * Allows commenters to edit their own comments, while posters can edit any comment.
     * The function first validates the existence of the associated post and comment.
     * If the user does not have permission to edit the comment, a ForbiddenException is thrown.
     *
     * @param string $id The ID of the comment to update.
     * @param string $postId The ID of the post associated with the comment.
     * @param array $params An array containing parameters for updating the comment, including the new comment text and the user's User ID.
     * @param string $adminUuid The UUID of the admin performing the update.
     * @param int $role The role permission of the commenter
     * @return Comment The updated comment object.
     *
     * @throws ResourceNotFoundException If the comment or associated post is not found.
     * @throws ForbiddenException If the user does not have permission to edit the comment.
     */
    public function updateComment(string $id, string $postId, array $params, string $adminUuid, int $role): Comment
    {
        $this->validatePostExistence($postId, $adminUuid);
        $comment = $this->getCommentOrFail($postId, $id, $adminUuid);

        $userId = $params['user_id'];
        if (!$this->isPoster($userId, $adminUuid)) {
            $this->validateEditPermission($comment, $userId);
        }

        // Validate the comment for any prohibited words
        if ($role != Role::poster()->getRole()) {
            $this->validateComment($adminUuid, StringHelper::convertToUnicodeCharacters($params['comment']));
        }

        $comment = $this->commentRepository->update($comment, ['comment' => $params['comment']]);
        $commentTotal = $this->commentRepository->getCommentTotal(['post_id' => $postId], $adminUuid);
        $comment->setCommentTotal($commentTotal);
        $this->setCommenterInfo($comment, $adminUuid);

        return $comment;
    }

    /**
     * Deletes a comment for a given post, user, and role.
     *
     * Allows posters to delete any comment, while commenters can only delete their own comments.
     * The function first validates the existence of the associated post and comment.
     * If the user does not have permission to delete the comment, a ForbiddenException is thrown.
     *
     * @param string $postId The ID of the post associated with the comment.
     * @param string $id The ID of the comment to delete.
     * @param string $adminUuid The UUID of the admin performing the deletion.
     * @param int $userId The User ID of the user attempting to delete the comment.
     *
     * @throws ResourceNotFoundException If the comment or associated post is not found.
     * @throws ForbiddenException If the user does not have permission to delete the comment.
     */
    public function deleteComment(string $postId, string $id, string $adminUuid, int $userId): void
    {
        $this->validatePostExistence($postId, $adminUuid);

        $comment = $this->getCommentOrFail($postId, $id, $adminUuid);

        if (!$this->isPoster($userId, $adminUuid)) {
            $this->validateDeletionPermission($comment, $userId);
        }

        $this->commentRepository->delete($comment);
    }

    /**
     * Check if the user with the given user ID and admin UUID is a poster.
     *
     * @param  int    $userId The User ID of the user.
     * @param  string $adminUuid  The admin ID associated with the user.
     * @return bool               True if the user is a poster, false otherwise.
     */
    public function isPoster(int $userId, string $adminUuid): bool
    {
        $poster = $this->posterRepository->getPosterByUserId($userId, $adminUuid);

        return (bool) $poster;
    }

    /**
     * Publishes a comment associated with a post.
     *
     * Allows only the poster to publish a specific comment.
     * The function first validates the existence of the associated post and comment.
     * If the user attempting to publish the comment is not the poster, a ForbiddenException is thrown.
     *
     * @param string $id The ID of the comment to publish.
     * @param string $postId The ID of the post associated with the comment.
     * @param string $adminUuid The UUID of the admin performing the action.
     * @param int $userId The User ID of the user attempting to publish the comment.
     * @return Comment The updated comment after publishing.
     *
     * @throws ResourceNotFoundException If the associated post or comment is not found.
     * @throws ForbiddenException If the user does not have permission to publish the comment.
     */
    public function publishComment(string $id, string $postId, string $adminUuid, int $userId): Comment
    {
        $this->validatePostExistence($postId, $adminUuid);

        $comment = $this->getCommentOrFail($postId, $id, $adminUuid);

        if (!$this->isPoster($userId, $adminUuid)) {
            throw new ForbiddenException(Lang::get('comment::errors.access_denied_publish_permission'));
        }

        $comment = $this->commentRepository->update($comment, ['is_hidden' => false]);
        $this->setCommenterInfo($comment, $adminUuid);

        return $comment;
    }

    /**
     * Unpublishes a comment associated with a post.
     *
     * Allows only the poster to unpublish a specific comment.
     * The function first validates the existence of the associated post and comment.
     * If the user attempting to unpublish the comment is not the poster, a ForbiddenException is thrown.
     *
     * @param string $id The ID of the comment to unpublish.
     * @param string $postId The ID of the post associated with the comment.
     * @param string $adminUuid The UUID of the admin performing the action.
     * @param int $userId The User ID of the user attempting to unpublish the comment.
     * @return Comment The updated comment after unpublishing.
     *
     * @throws ResourceNotFoundException If the associated post or comment is not found.
     * @throws ForbiddenException If the user does not have permission to unpublish the comment.
     */
    public function unpublishComment(string $id, string $postId, string $adminUuid, int $userId): Comment
    {
        $this->validatePostExistence($postId, $adminUuid);

        $comment = $this->getCommentOrFail($postId, $id, $adminUuid);

        if (!$this->isPoster($userId, $adminUuid)) {
            throw new ForbiddenException(Lang::get('comment::errors.access_denied_unpublish_permission'));
        }

        $comment = $this->commentRepository->update($comment, ['is_hidden' => true]);
        $this->setCommenterInfo($comment, $adminUuid);

        return $comment;
    }

    /**
     * Retrieves a specific comment for a given post and comment ID or throws an exception if not found.
     *
     * @param string $postId The ID of the post associated with the comment.
     * @param string $id The ID of the comment to retrieve.
     * @param string $adminUuid The admin ID associated with the comment.
     * @return Comment The retrieved comment.
     *
     * @throws ResourceNotFoundException If the comment is not found.
     */
    protected function getCommentOrFail(string $postId, string $id, string $adminUuid): Comment
    {
        $comment = $this->commentRepository->getComment($id, $adminUuid, $postId);

        if (!$comment) {
            throw new ResourceNotFoundException(Lang::get('comment::errors.comment_item_not_found', ['id' => $id]));
        }

        return $comment;
    }

    /**
     * Validates whether the user has permission to perform an action on the comment.
     *
     * @param Comment $comment The comment being validated.
     * @param int $userId The user ID for validation.
     * @param string $action The action to validate ('edit' or 'delete').
     *
     * @throws ForbiddenException If the user does not have permission for the specified action.
     */
    protected function validateCommentPermission(Comment $comment, int $userId, string $action): void
    {
        $errorMessageKey = 'comment::errors.access_denied_' . $action . '_permission';

        if ($comment->getUserId() !== $userId) {
            throw new ForbiddenException(Lang::get($errorMessageKey));
        }
    }

    /**
     * Validates whether the user has permission to edit the comment.
     *
     * @param Comment $comment The comment being edited.
     * @param int $userId The user ID for validation.
     */
    protected function validateEditPermission(Comment $comment, int $userId): void
    {
        $this->validateCommentPermission($comment, $userId, 'edit');
    }

    /**
     * Validates whether the user has permission to delete the comment.
     * If the role is not administrator or poster, only the commenter has permission to delete their own comment.
     *
     * @param Comment $comment The comment being deleted.
     * @param int $userId The user ID.
     *
     * @throws ForbiddenException If the user does not have permission to delete the comment.
     */
    protected function validateDeletionPermission(Comment $comment, int $userId): void
    {
        $this->validateCommentPermission($comment, $userId, 'delete');
    }

    /**
     * Validates whether the associated post exists.
     *
     * @param string $postId The ID of the post.
     * @param string $adminUuid The ID of the administrator performing the action.
     *
     * @throws ResourceNotFoundException If the associated post is not found.
     */
    protected function validatePostExistence(string $postId, string $adminUuid): void
    {
        $post = $this->postRepository->getPost($postId, $adminUuid);

        if (!$post) {
            throw new ResourceNotFoundException(Lang::get('post::errors.post_item_not_found', ['id' => $postId]));
        }
    }

    /**
     * Sets the commenter information for a specific comment.
     *
     * Retrieves the nickname and profile image of the commenter using the provided User ID and admin UUID
     * and sets them in the given comment object.
     *
     * @param Comment $comment The comment object for which to set the commenter information.
     * @param string $adminUuid The UUID of the admin performing the action.
     * @return void
     */
    public function setCommenterInfo(Comment &$comment, string $adminUuid): void
    {
        $user = $this->userCacheService->getUserDataFromCache($comment->getUserId(), $adminUuid);
        $comment->setNickname($user->nickname ?? null);
        $comment->setAvatar($user->profile_image ?? null);
    }

    /**
     * Sets the commenter information for a collection of comments.
     *
     * Iterates through the given collection of comments and sets the commenter information
     * for each comment using the provided admin UUID.
     *
     * @param Collection $comments The collection of comments for which to set the commenter information.
     * @param string $adminUuid The UUID of the admin performing the action.
     * @return void
     */
    private function setCommentersInfo(Collection &$comments, string $adminUuid): void
    {
        // Retrieve unique hub synch IDs from comments
        $userIds = $comments->pluck('user_id')->unique()->toArray();

        // Get users from cache
        $users = $this->userCacheService->getUsersFromCache($adminUuid, $userIds);

        // Loop through comments and set commenter info
        $comments->each(function ($comment) use ($users) {
            if ($comment instanceof Comment) {
                $userId = $comment->getUserId();
                $user = $users[$userId] ?? null;

                if ($user) {
                    $comment->setNickname($user->nickname);
                    $comment->setAvatar($user->profile_image);
                }
            }
        });
    }

    /**
     * Creates a new comment for a specified post, validating user permissions,
     * throttle limits, and content restrictions.
     *
     * This method performs the following:
     * 1. Validates the existence of the post associated with the provided post ID.
     * 2. Checks if the user is restricted from commenting (restricted user check).
     * 3. Checks if the user has exceeded the maximum number of comments allowed within
     *    a specific throttle time frame.
     * 4. Validates the comment for prohibited content based on the user's role.
     * 5. Creates the comment and updates the total number of comments for the post.
     *
     * @param string $postId The unique identifier of the post to which the comment is being added.
     * @param array $params An associative array of comment data
     * @param string $adminUuid The UUID of the admin performing the action.
     * @param int $role The role of the user attempting to submit the comment.
     *
     * @return Comment The newly created Comment object.
     *
     * @throws ForbiddenException If the user is restricted from commenting or has exceeded
     *         the allowed number of comments within the throttle limit.
     * @throws ResourceNotFoundException If the post does not exist.
     */
    public function createComment(string $postId, array $params, string $adminUuid, int $role): Comment
    {
        // Ensure the post exists
        $this->validatePostExistence($postId, $adminUuid);

        // Set the post ID in the params
        $params['post_id'] = $postId;

        // Check if the user is restricted from commenting
        if ($this->isRestrictedUser($params['user_id'], $adminUuid)) {
            throw new ForbiddenException(Lang::get('comment::errors.comment_denied_due_to_restricted_user'));
        }

        // Validate the comment if the user is not a poster
        if ($role !== Role::poster()->getRole()) {
            // Check throttling rules, if applicable
            $this->checkThrottleConfig($params['user_id'], $adminUuid);
            $this->validateComment($adminUuid, StringHelper::convertToUnicodeCharacters($params['comment']));
        }

        // Create the comment
        $comment = $this->commentRepository->create(new Comment(), $params);

        // Set total comments and commenter info
        $comment->setCommentTotal($this->getPostCommentTotal($postId, $adminUuid));
        $this->setCommenterInfo($comment, $adminUuid);

        return $comment;
    }

    /**
     * Check if the user is restricted from commenting.
     *
     * @param int $userId
     * @param string $adminUuid
     * @return bool
     */
    private function isRestrictedUser(int $userId, string $adminUuid): bool
    {
        return (bool) $this->restrictedUserRepository->getRestrictedUserByUserId($userId, $adminUuid);
    }

    /**
     * Check if the user has exceeded the throttle limits for comments.
     *
     * @param string $userId
     * @param string $adminUuid
     * @throws ForbiddenException
     */
    private function checkThrottleConfig(string $userId, string $adminUuid): void
    {
        $throttleConfig = $this->throttleConfigRepository->getThrottleConfig($adminUuid);

        if ($throttleConfig) {
            $commentCountParams = [
                'user_id' => $userId,
                'comment_creation_from' => Carbon::now()->subMinutes($throttleConfig['time_frame_minutes'])->format('Y-m-d H:i:s'),
                'comment_creation_to' => Carbon::now()->format('Y-m-d H:i:s'),
            ];

            $userCommentTotal = $this->commentRepository->getCommentTotal($commentCountParams, $adminUuid);

            if ($userCommentTotal >= $throttleConfig['max_comments']) {
                throw new ForbiddenException(Lang::get('comment::errors.comment_denied_due_to_reached_to_maximum_comment_number'));
            }
        }
    }

    /**
     * Get the total number of comments for the post.
     *
     * @param string $postId
     * @param string $adminUuid
     * @return int
     */
    private function getPostCommentTotal(string $postId, string $adminUuid): int
    {
        return $this->commentRepository->getCommentTotal(['post_id' => $postId], $adminUuid);
    }
}
