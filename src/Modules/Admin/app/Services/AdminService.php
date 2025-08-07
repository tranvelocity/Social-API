<?php

namespace Modules\Admin\app\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Lang;
use Modules\Admin\app\Repositories\AdminRepository;
use Modules\Admin\app\Repositories\AdminRepositoryInterface;
use Modules\Cache\app\Services\NGWordCacheService;
use Modules\Cache\app\Services\UserCacheService;
use Modules\Admin\app\Models\Admin;
use Modules\Admin\app\Repositories\CommentRepositoryInterface;
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
 * Class AdminService.
 *
 * Service for managing comments and interacting with the comment repository.
 */
class AdminService
{
    public function __construct(
        AdminRepositoryInterface $adminRepository
    ) {
        $this->adminRepository = $adminRepository;
    }

    /**
     * Retrieves comments and total count based on provided parameters.
     *
     * @param array $params Parameters for filtering comments.
     * @param string $adminUuid The admin ID associated with the comment.
     * @return array An array containing 'data' (array of comments) and 'total' (total count of comments).
     */
    public function getComments(string $id, array $params, string $adminUuid): array
    {
        $this->validatePostExistence($id, $adminUuid);
        $params['post_id'] = $id;

        $comments = $this->adminRepository->getComments($params, $adminUuid);
        $this->setCommentersInfo($comments, $adminUuid);

        return [
            'data' => $comments,
            'total' => $this->adminRepository->getCommentTotal($params, $adminUuid),
        ];
    }

    /**
     * Retrieves a specific comment for a given post and comment ID.
     *
     * @param string $apiKey
     * @return Admin|null The retrieved comment.
     */
    public function getAdminByApiKey(string $apiKey): ?Admin
    {
        return $this->adminRepository->getAdminByApiKey($apiKey);
    }
}
