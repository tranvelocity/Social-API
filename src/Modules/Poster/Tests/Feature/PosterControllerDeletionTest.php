<?php

namespace Modules\Poster\Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Modules\Poster\app\Models\Poster;

/**
 * @group parallel
 */
class PosterControllerDeletionTest extends PosterControllerTestBase
{
    /** @test */
    public function test_204_response_delete_a_specific_poster(): void
    {
        // 1. Generate the mock data for admin and poster.
        $admin = $this->generateMockAdmin();
        $poster = Poster::factory(['admin_uuid' => $admin->getUuid()])->create();

        // 2. Mock the dependencies, including the account service and user merchant session.
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($this->generateMockUserUser());

        // 3. Execute the endpoint or method that uses the mocked service or class to delete the post.
        $response = $this->deleteJson("{$this->endpoint}/{$poster->id}", [], $this->generateMockSocialApiHeaders($admin, false));

        // 4. Assertions: Ensure that the response is a 204 No Content response.
        $this->assert204NoContentResponse($response);
    }

    /** @test */
    public function test_404_response_delete_a_specific_poster_fails_poster_not_found(): void
    {
        // 1. Generate the mock data for admin and poster.
        $admin = $this->generateMockAdmin();

        // 2. Mock the dependencies, including the account service and user merchant session.
        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($this->generateMockUserUser());

        // 3. Attempt to delete a non-existing post by providing a non-existing poster ID.
        $nonExistingPosterId = 'non-exist-poster-id';
        $endpoint = $this->endpoint . '/' . $nonExistingPosterId;
        $response = $this->deleteJson($endpoint, [], $this->generateMockSocialApiHeaders($admin, false));

        // 4. Assertions: Ensure that the response is a 404 Not Found, indicating the post is not found.
        $this->assert404NotFoundResponse($response, Lang::get('poster::errors.poster_not_found', ['id' => $nonExistingPosterId]));
    }
}
