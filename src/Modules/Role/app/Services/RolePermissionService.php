<?php

namespace Modules\Role\app\Services;

use Illuminate\Support\Facades\Config;
use Modules\Role\app\Entities\Role;

/**
 * Class RolePermissionService.
 *
 * Provides functionality for checking and retrieving acceptable endpoints based on user roles.
 */
class RolePermissionService
{
    /**
     * Check if the given endpoint and method are acceptable for the specified user role.
     *
     * @param int    $userRole The user's role.
     * @param string $endpoint The API endpoint to check.
     * @param string $method   The HTTP method to check.
     *
     * @return bool Returns true if the endpoint and method are acceptable for the user role, false otherwise.
     */
    public static function isAcceptable(int $userRole, string $endpoint, string $method): bool
    {
        $acceptableEndpoints = self::getAcceptableEndpoints($userRole);

        return isset($acceptableEndpoints[$endpoint]) && in_array($method, $acceptableEndpoints[$endpoint]);
    }

    /**
     * Get acceptable API endpoints based on the user's role.
     *
     * @param int $userRole The role of the user.
     * @return array An array of endpoints with their corresponding HTTP methods.
     */
    private static function getAcceptableEndpoints(int $userRole): array
    {
        $version = Config::get('api.version');
        $endpoints = Config::get('api.endpoints');

        $commonEndpoints = [
            "{$version}/{$endpoints['clear_member_caches']}" => ['DELETE'],
            "{$version}/{$endpoints['clear_ng_word_caches']}" => ['DELETE'],

            "{$version}/{$endpoints['throttle_config']}" => ['GET', 'POST'],
            "{$version}/{$endpoints['throttle_config_deletion']}" => ['DELETE'],
            "{$version}/{$endpoints['restricted_users']}" => ['GET', 'POST'],
            "{$version}/{$endpoints['restricted_user_item']}" => ['GET', 'PUT', 'DELETE'],
        ];

        $roleEndpoints = [
            Role::poster()->getRole() => [
                "{$version}/{$endpoints['posts_social']}" => ['GET'],
                "{$version}/{$endpoints['posts']}" => ['GET', 'POST'],
                "{$version}/{$endpoints['post_item']}" => ['GET', 'PUT', 'DELETE'],

                "{$version}/{$endpoints['post_likes']}" => ['GET'],
                "{$version}/{$endpoints['post_like']}" => ['POST'],
                "{$version}/{$endpoints['post_unlike']}" => ['POST'],

                "{$version}/{$endpoints['post_comments']}" => ['GET', 'POST'],
                "{$version}/{$endpoints['post_comment']}" => ['GET', 'PUT', 'DELETE'],
                "{$version}/{$endpoints['post_comment_publish']}" => ['POST'],
                "{$version}/{$endpoints['post_comment_unpublish']}" => ['POST'],

                "{$version}/{$endpoints['medias']}" => ['GET', 'POST'],
                "{$version}/{$endpoints['media_item']}" => ['GET', 'PUT', 'DELETE'],
            ],
            Role::paidMember()->getRole() => [
                "{$version}/{$endpoints['posts_social']}" => ['GET'],
                "{$version}/{$endpoints['post_like']}" => ['POST'],
                "{$version}/{$endpoints['post_unlike']}" => ['POST'],
                "{$version}/{$endpoints['post_comments']}" => ['GET', 'POST'],
                "{$version}/{$endpoints['post_comment']}" => ['GET', 'PUT', 'DELETE'],
            ],
            Role::freeMember()->getRole() => [
                "{$version}/{$endpoints['posts_social']}" => ['GET'],
                "{$version}/{$endpoints['post_comments']}" => ['GET'],
                "{$version}/{$endpoints['post_comment']}" => ['GET'],
                "{$version}/{$endpoints['post_like']}" => ['POST'],
                "{$version}/{$endpoints['post_unlike']}" => ['POST'],
            ],
            Role::nonRegisteredUser()->getRole() => [
                "{$version}/{$endpoints['posts_social']}" => ['GET'],
            ],
        ];

        return array_merge($roleEndpoints[$userRole] ?? [], $commonEndpoints);
    }
}
