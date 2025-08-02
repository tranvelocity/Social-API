<?php

namespace Modules\Like\app\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Modules\Core\app\Exceptions\ForbiddenException;
use Modules\Core\app\Exceptions\ResourceNotFoundException;
use Modules\Like\app\Http\Entities\LikePost;
use Modules\Like\app\Models\Like;
use Modules\Like\app\Repositories\LikeRepositoryInterface;
use Modules\Post\app\Models\Post;
use Modules\Post\app\Repositories\PostRepositoryInterface;
use Modules\Role\app\Entities\Role;

/**
 * Class LikeService.
 *
 * Service for managing likes and interacting with the like repository.
 */
class LikeService
{
    /**
     * @var LikeRepositoryInterface The like repository for interacting with like data.
     */
    private LikeRepositoryInterface $likeRepository;

    /**
     * @var PostRepositoryInterface The post repository for interacting with like data.
     */
    private PostRepositoryInterface $postRepository;

    /**
     * LikeService constructor.
     *
     * @param LikeRepositoryInterface $likeRepository The like repository for interacting with like data.
     * @param PostRepositoryInterface $postRepository The post repository for interacting with like data.
     */
    public function __construct(
        LikeRepositoryInterface $likeRepository,
        PostRepositoryInterface $postRepository
    ) {
        $this->likeRepository = $likeRepository;
        $this->postRepository = $postRepository;
    }

    /**
     * Retrieves likes and total count based on provided parameters.
     *
     * @param array $params Parameters for filtering likes.
     * @param string $adminId The ID of the administrator performing the action.
     * @return array An array containing 'data' (array of likes) and 'total' (total count of likes).
     * @throws ResourceNotFoundException If the associated post is not found.
     */
    public function getLikes(array $params, string $adminId): array
    {
        $this->validatePostExistence($params['post_id'], $adminId);

        return [
            'data' => $this->likeRepository->getLikes($params, $adminId),
            'total' => $this->likeRepository->getLikeTotal($params, $adminId),
        ];
    }

    /**
     * Handle liking a post.
     *
     * @param string $postId    The ID of the post to be liked.
     * @param int    $userId The User ID of the user liking the post.
     * @param string $adminId    The admin ID performing the action.
     * @param int $role The user role permission.
     *
     * @return LikePost Returns a LikePost object representing the result of the like action.
     */
    public function like(string $postId, int $userId, string $adminId, int $role): LikePost
    {
        $post = $this->getPost($postId, $adminId);

        if (!$this->canLikeOrUnlike($post, $role)) {
            throw new ForbiddenException(Lang::get('like::errors.access_denied_like_permission'));
        }

        $params = ['post_id' => $postId, 'user_id' => $userId];

        // Retrieve existing likes for the post
        $existingLikes = $this->likeRepository->getLikes($params, $adminId);

        // Get the total number of likes for the post
        $totalLikes = $this->likeRepository->getLikeTotal(['post_id' => $postId], $adminId);

        // Check if the user has already liked the post
        if ($existingLikes instanceof Collection && $existingLikes->isNotEmpty()) {
            return new LikePost($postId, $userId, LikePost::ACTION_LIKED, $totalLikes);
        } else {
            // If the user has not liked the post, create a new like record
            $this->likeRepository->create(new Like(), $params);

            return new LikePost($postId, $userId, LikePost::ACTION_LIKED, $totalLikes + 1);
        }
    }

    /**
     * Handle unliking a post.
     *
     * @param string $postId    The ID of the post to be unliked.
     * @param int    $userId The User ID of the user unliking the post.
     * @param string $adminId    The admin ID performing the action.
     * @param int $role The user role permission.
     *
     * @return LikePost Returns a LikePost object representing the result of the unlike action.
     */
    public function unlike(string $postId, int $userId, string $adminId, int $role): LikePost
    {
        $post = $this->getPost($postId, $adminId);

        if (!$this->canLikeOrUnlike($post, $role)) {
            throw new ForbiddenException(Lang::get('like::errors.access_denied_unlike_permission'));
        }

        $params = ['post_id' => $postId, 'user_id' => $userId];

        // Retrieve existing likes for the post
        $existingLikes = $this->likeRepository->getLikes($params, $adminId);

        // Get the total number of likes for the post
        $totalLikes = $this->likeRepository->getLikeTotal(['post_id' => $postId], $adminId);

        // Check if the user has already liked the post
        if ($existingLikes instanceof Collection && $existingLikes->isNotEmpty()) {
            // If the user has liked the post, delete the like record
            $this->likeRepository->forceDelete($existingLikes->first());

            return new LikePost($postId, $userId, LikePost::ACTION_UNLIKED, $totalLikes - 1);
        } else {
            // If the user has not liked the post, return the existing state
            return new LikePost($postId, $userId, LikePost::ACTION_UNLIKED, $totalLikes);
        }
    }

    /**
     * Validates whether the associated post exists.
     *
     * @param string $postId The ID of the post.
     * @param string $adminId The ID of the administrator performing the action.
     * @throws ResourceNotFoundException If the associated post is not found.
     */
    private function validatePostExistence(string $postId, string $adminId): void
    {
        $post = $this->postRepository->getPost($postId, $adminId);

        if (!$post) {
            throw new ResourceNotFoundException(Lang::get('post::errors.post_item_not_found', ['id' => $postId]));
        }
    }

    /**
     * Get post corresponds to the given ID.
     *
     * @param string $postId The ID of the post.
     * @param string $adminId The ID of the administrator performing the action.
     *
     * @return Post Returns a Post object.
     */
    private function getPost(string $postId, string $adminId): Post
    {
        $post = $this->postRepository->getPost($postId, $adminId);

        if (!$post) {
            throw new ResourceNotFoundException(Lang::get('post::errors.post_item_not_found', ['id' => $postId]));
        }

        return $post;
    }

    /**
     * Checks if the user with the given role has permission to like or unlike the post.
     *
     * @param Post $post The post to check permissions for.
     * @param int $role The role of the user.
     * @return bool Returns true if the user has permission, false otherwise.
     */
    private function canLikeOrUnlike(Post $post, int $role): bool
    {
        // Check if the user is the poster of the post
        if ($role == Role::poster()->getRole()) {
            return true;
        }

        // Check if the user is a paid member
        if ($role == Role::paidMember()->getRole()) {
            return true;
        }

        // Check if the user is a free member and the post is of free type
        if ($role == Role::freeMember()->getRole() && $post->getPostType() == Post::FREE_TYPE) {
            return true;
        }

        // No permission granted
        return false;
    }
}
