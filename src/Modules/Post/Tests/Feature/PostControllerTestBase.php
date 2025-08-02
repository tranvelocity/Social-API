<?php

namespace Modules\Post\Tests\Feature;

use Modules\Admin\Entities\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Media\app\Models\Media;
use Modules\Post\app\Models\Post;
use Modules\Poster\app\Models\Poster;
use Modules\Test\Tests\Feature\ApiTestCase;

/**
 * @group parallel
 */
class PostControllerTestBase extends ApiTestCase
{
    use DatabaseTransactions;

    protected string $endpoint = '/1/posts';

    /**
     * Attributes for PostSocialResource.
     *
     * @return array
     */
    protected function collectionPostResourceAttributes(): array
    {
        return [
            'id',
            'admin_uuid',
            'type',
            'content',
            'is_published',
            'published_start_at',
            'published_end_at',
            'updated_at',
            'created_at',
            'poster' => [
                'id',
                'user_id',
                'nickname',
                'avatar',
            ],
        ];
    }

    /**
     * Define the expected post resource attributes.
     *
     * @return array
     */
    protected function simplePostResourceAttributes(): array
    {
        return [
            'id',
            'admin_uuid',
            'type',
            'content',
            'is_published',
            'published_start_at',
            'published_end_at',
            'updated_at',
            'created_at',
            'poster' => [
                'id',
                'user_id',
                'nickname',
                'avatar',
            ],
            'total_medias',
            'total_comments',
            'total_likes',
            'medias',
            'comments',
        ];
    }

    /**
     * Generate an array of valid parameters for testing post creation.
     */
    protected function generateValidParameters(Admin $admin = null): array
    {
        $admin = $admin ?? $this->generateMockAdmin();
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid()])->create();
        $image = Media::factory()->imageItem()->create();
        $video = Media::factory()->videoItem()->create();

        return [
            'admin_uuid' => $admin->getUuid(),
            'is_published' => $this->faker->boolean,
            'poster_id' => $poster->id,
            'type' => $this->faker->randomElement([Post::FREE_TYPE, Post::PREMIUM_TYPE]),
            'published_start_at' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s'),
            'published_end_at' => $this->faker->dateTimeBetween('+1 years', '+3 years')->format('Y-m-d H:i:s'),
            'content' => $this->faker->text,
            'media_ids' => [
                ['id' => $image->id],
                ['id' => $video->id],
            ],
        ];
    }
}
