<?php

namespace Modules\Poster\Tests\Feature;

use Modules\Admin\Entities\Admin;
use Modules\Test\Tests\Feature\ApiTestCase;

class PosterControllerTestBase extends ApiTestCase
{
    protected string $endpoint = '/1/posters';

    /**
     * Define the expected Poster resource attributes.
     *
     * @return array
     */
    protected function posterResourceAttributes(): array
    {
        return [
            'id',
            'admin_uuid',
            'user_id',
            'nickname',
            'avatar',
            'description',
            'updated_at',
            'created_at',
        ];
    }

    /**
     * Generate an array of valid parameters for testing Poster creation.
     */
    protected function generateValidParameters(Admin $admin = null): array
    {
        $admin = $admin ?? $this->generateMockAdmin();

        return [
            'admin_uuid' => $admin->getUuid(),
            'user_id' => $this->faker->randomNumber(5, false),
            'description' => $this->faker->text,
        ];
    }
}
