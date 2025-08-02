<?php

namespace Modules\Poster\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Lang;
use Modules\Poster\app\Models\Poster;
use Modules\Poster\app\Resources\PosterResource;

/**
 * @group parallel
 */
class PosterControllerRetrievalTest extends PosterControllerTestBase
{
    use DatabaseTransactions;

    /**
     * Test retrieving the list of posts.
     *
     * @test
     */
    public function test_200_response_retrieve_list_of_posters(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $headers = $this->generateMockSocialApiHeaders($admin, false);
        $posters = Poster::factory(['admin_uuid' => $admin->getUuid()])->count(10)->create();

        // 2. Mock the dependencies
        $this->generateMockUserUserSession($this->generateMockUserUser());
        $this->mockUserAuthenticationService($admin);

        // 3. Execute the endpoint
        $response = $this->getJson($this->endpoint, $headers);

        // 4. Prepare expected data
        $resourceCollection = $posters->map(function ($poster) {
            return new PosterResource($poster);
        });

        $expectedData = $resourceCollection->map(function ($posterResource) {
            return $posterResource->toArray(request());
        })->sortBy('created_at', 0, true)->values()->all();

        // 5. Assertions
        $this->assert200ResponseWithDataCollection($response, $this->posterResourceAttributes(), $expectedData);
    }

    /**
     * Test that can show a specific post item.
     *
     * @test
     */
    public function test_200_response_retrieve_a_specific_poster(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($this->generateMockUserUser());

        // 3. Execute the endpoint
        $response = $this->getJson($this->endpoint . '/' . $poster->id, $this->generateMockSocialApiHeaders($admin, false));

        // 4. Extract expected data
        $expectedData = PosterResource::make($poster)->resolve();

        // 5. Assertions
        $this->assert200ResponseWithSimpleResource($response, $this->posterResourceAttributes(), $expectedData);
    }

    /**
     * Test retrieving a specific post, failure due to post not found.
     *
     * @test
     */
    public function test_404_response_retrieve_a_specific_poster_fails_poster_not_found(): void
    {
        // 1. Generate the mock data
        $admin = $this->generateMockAdmin();

        // 2. Mock the dependencies
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($this->generateMockUserUser());

        $id = 'non-existing-id';

        // 3. Execute the endpoint
        $response = $this->getJson($this->endpoint . '/' . $id, $this->generateMockSocialApiHeaders($admin, false));

        // 4. Assertions
        $this->assert404NotFoundResponse($response, Lang::get('poster::errors.poster_not_found', ['id' => $id]));
    }
}
