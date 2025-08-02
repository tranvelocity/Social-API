<?php

namespace Modules\Like\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Test\Tests\Feature\ApiTestCase;

/**
 * @group parallel
 */
class LikeControllerTestBase extends ApiTestCase
{
    use DatabaseTransactions;

    protected string $endpoint = '/1/posts';

    /**
     * Get the attributes expected in the response when dealing with Like resources.
     *
     * @return array
     */
    protected function likeResourceAttributes(): array
    {
        return [
            'action',
            'user_id',
            'post_id',
            'total_likes',
        ];
    }

    protected function likeHistoryResourceAttributes(): array
    {
        return [
            'id',
            'user_id',
            'post_id',
            'created_at',
        ];
    }
}
