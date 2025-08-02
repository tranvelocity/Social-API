<?php

namespace Modules\Poster\Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Modules\Poster\app\Models\Poster;
use Modules\Poster\app\Resources\PosterResource;

/**
 * @group parallel
 */
class PosterControllerUpdateTest extends PosterControllerTestBase
{
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
            'user_id',
            'description',
            'updated_at',
            'created_at',
        ];
    }

    /** @test */
    public function test_200_response_update_a_poster_successfully(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($this->generateMockUserUser());

        // 3. Execute the endpoint
        $response = $this->putJson($this->endpoint . "/{$poster->id}", ['description' => $this->faker->text], $this->generateMockSocialApiHeaders($admin, false));

        // 4. Get the expected data
        $updatedPoster = Poster::find($response->json('data.id'));
        $expectedData = PosterResource::make($updatedPoster)->resolve();

        // 5. Assertions
        $this->assert200ResponseWithSimpleResource($response, $this->simplePostResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_404_response__update_a_poster_fails_poster_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($this->generateMockUserUser());

        // 3. Execute the endpoint
        $id = 'non-existing-id';
        $response = $this->putJson($this->endpoint . "/{$id}", ['description' => $this->faker->text], $this->generateMockSocialApiHeaders($admin, false));

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('poster::errors.poster_not_found', ['id' => $id]));
    }
}
