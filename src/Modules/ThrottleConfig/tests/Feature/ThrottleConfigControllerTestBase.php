<?php

namespace Modules\ThrottleConfig\tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Test\Tests\Feature\ApiTestCase;

/**
 * @group parallel
 */
class ThrottleConfigControllerTestBase extends ApiTestCase
{
    use DatabaseTransactions;

    protected string $endpoint = '/1/throttle-config';

    /**
     * Attributes for ThrottleConfigSocialResource.
     *
     * @return array
     */
    protected function throttleConfigResourceAttributes(): array
    {
        return [
            'id',
            'admin_uuid',
            'time_frame_minutes',
            'max_comments',
            'updated_at',
            'created_at',
        ];
    }
}
