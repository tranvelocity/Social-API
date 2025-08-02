<?php

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Admin\app\Models\Admin;
use Modules\Poster\app\Models\Poster;
use Modules\Test\Tests\Feature\ApiTestCase;

/**
 * @group parallel
 */
class MediaControllerTestBase extends ApiTestCase
{
    use DatabaseTransactions;

    protected string $endpoint = '/1/medias';

    protected function mediaResourceAttributes(): array
    {
        return [
            'id',
            'post_id',
            'type',
            'thumbnail',
            'path',
            'post_id',
            'updated_at',
            'created_at',
        ];
    }

    /**
     * Create a mock Poster with specified admin and user data.
     *
     * @param Admin $admin
     * @param array $user
     */
    protected function createMockPoster(Admin $admin, array $user): Poster
    {
        return Poster::factory(['user_id' => $user['user_id'], 'admin_uuid' => $admin->getUuid()])->create();
    }
}
