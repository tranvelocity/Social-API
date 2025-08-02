<?php

namespace Modules\Comment\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\app\Traits\WithDataFormatters;
use Modules\Post\app\Models\Post;
use Modules\Poster\app\Models\Poster;
use Modules\Test\Tests\Feature\ApiTestCase;

/**
 * @group parallel
 */
class CommentControllerTestBase extends ApiTestCase
{
    use DatabaseTransactions;
    use WithDataFormatters;
    use NGWordGenerator;

    protected string $endpoint = '/1/posts';

    /**
     * Attributes for PostSocialResource.
     *
     * @return array
     */
    protected function commentResourceAttributes(bool $isFullResource = false): array
    {
        $attributes = [
            'id',
            'user_id',
            'comment',
            'nickname',
            'avatar',
            'is_hidden',
            'updated_at',
            'created_at',
        ];

        if ($isFullResource) {
            $attributes += ['post'];
        } else {
            $attributes += ['post_id'];
        }

        return $attributes;
    }

    /**
     * Create a new Poster with the given admin and User ID.
     */
    protected function createPosterWithAdminAndUserId(Admin $admin, int $userId): Poster
    {
        return Poster::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userId])->create();
    }

    /**
     * Create a new Post with the given admin and Poster.
     */
    protected function createPostWithAdmin(Admin $admin, Poster $poster): Post
    {
        return Post::factory()->create(['admin_uuid' => $admin->getUuid(), 'poster_id' => $poster->id]);
    }

    /**
     * Create a new Poster with the given admin.
     */
    protected function createPosterWithAdmin(Admin $admin): Poster
    {
        return Poster::factory(['admin_uuid' => $admin->getUuid()])->create();
    }
}
