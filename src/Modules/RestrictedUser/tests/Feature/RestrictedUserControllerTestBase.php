<?php

namespace Modules\RestrictedUser\tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Test\Tests\Feature\ApiTestCase;

/**
 * @group parallel
 */
class RestrictedUserControllerTestBase extends ApiTestCase
{
    use DatabaseTransactions;

    protected string $endpoint = '/1/restricted-users';

    /**
     * Attributes for RestrictedUserResource.
     *
     * @return array
     */
    protected function restrictedUserResourceAttributes(): array
    {
        return [
            'id',
            'admin_uuid',
            'user_id',
            'remarks',
            'updated_at',
            'created_at',
        ];
    }
}
