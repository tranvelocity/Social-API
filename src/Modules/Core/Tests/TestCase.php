<?php

declare(strict_types=1);

namespace Modules\Core\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    use ApiSignatureTrait;
    use WithFaker;
    use RandomDataGenerator;

    /**
     * @return HttpKernelInterface
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Generate authentication headers for API requests.
     *
     * @param array $additionalHeaders Additional headers to be merged with the generated headers.
     * @param string $algorithm The algorithm used for generating the signature (default is 'sha2').
     *
     * @return array The array of authentication headers.
     */
    protected function generateMockValidHeaders(Admin $admin, array $additionalHeaders = [], string $algorithm = 'sha2'): array
    {
        $this->mockAdminService($admin);
//        $this->mockUserAuthenticationService($admin);
//
//        $authentication = $this->generateSignatureHeaders($admin->getApiKey(), $admin->getApiSecret(), $algorithm);

        $headers = [
            Config::get('api.auth.headers.api_key') => $authentication['api_key'],
            Config::get('api.auth.headers.timestamp') => $authentication['timestamp'],
            Config::get('api.auth.headers.signature') => $authentication['signature'],
        ];

        // Merge with additional headers
        return array_merge($headers, $additionalHeaders);
    }

    /**
     * Mock the AdminService and set up behavior for getAdminByApiKey and getAdminByAdminUuid.
     *
     * @param Admin $admin
     *
     * @return MockInterface
     */
    protected function mockAdminService(Admin $admin): MockInterface
    {
        $adminService = $this->mock(AdminService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $adminService->shouldReceive('getAdminByApiKey')->andReturn($admin);
        $adminService->shouldReceive('getAdminByAdminUuid')->andReturn($admin);

        return $adminService;
    }

    /**
     * Asserts the structure and content of a successful retrieval response for a specific resource.
     *
     * @param TestResponse $response          The HTTP response to be asserted.
     * @param array        $resourceAttributes The expected structure of the resource data.
     * @param array        $expectedData       The expected resource data to be validated.
     *
     * @return void
     */
    protected function assert200ResponseWithDataCollection(TestResponse $response, array $resourceAttributes, array $expectedData)
    {
        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => $resourceAttributes
                ],
                'success',
                'code',
                'pagination' => [
                    'offset',
                    'limit',
                    'total',
                ],
            ])
            ->assertJson([
                'data' => $expectedData,
                'success' => true,
                'code' => Response::HTTP_OK,
                'pagination' => [
                    'offset' => 0,
                    'limit' => 30,
                    'total' => count($expectedData),
                ],
            ]);
    }

    /**
     * Assert that the given response is a 403 Forbidden HTTP response with specific JSON structure.
     *
     * @param TestResponse $response The response to be asserted.
     * @param array|string $errorMessage The expected error message in the response.
     * @param int $code The expected error code in the response.
     *
     * @return void
     *
     * @throws AssertionFailedError If the assertion fails.
     */
    protected function assert403ForbiddenResponse(TestResponse $response, array|string $errorMessage, int $code = StatusCodeConstant::FORBIDDEN_PERMISSION_DENIED)
    {
        $response
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'code' => Response::HTTP_FORBIDDEN,
                "errors" => [
                    "message" => $errorMessage,
                    "code" => $code
                ]
            ]);
    }

    /**
     * Asserts that a response is a successful HTTP 200 response with a simple resource structure.
     *
     * @param TestResponse $response The HTTP response object to be asserted.
     * @param array $resourceAttributes The expected structure of the 'data' attribute in the JSON response.
     * @param array $expectedData The expected data to be found in the 'data' attribute of the JSON response.
     *
     * @return void
     */
    protected function assert200ResponseWithSimpleResource(TestResponse $response, array $resourceAttributes, array $expectedData, int $code = Response::HTTP_OK)
    {
        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => $resourceAttributes,
                'success',
                'code',
            ])
            ->assertJson([
                'data' => $expectedData,
                'success' => true,
                'code' => $code,
            ]);
    }

    /**
     * Asserts that a response is a HTTP 404 Not Found response with the specified error message and error code.
     *
     * @param TestResponse $response The HTTP response object to be asserted.
     * @param array|string $errorMessage The expected error message found in the 'errors' attribute of the JSON response.
     * @param int $errorCode The expected error code found in the 'errors' attribute of the JSON response.
     *
     * @return void
     */
    protected function assert404NotFoundResponse(TestResponse $response, array|string $errorMessage, int $errorCode = StatusCodeConstant::RESOURCE_NOT_FOUND)
    {
        $response
            ->assertNotFound()
            ->assertJsonStructure([
                'success',
                'code',
                'errors' => [
                    'message',
                    'code',
                ]
            ])
            ->assertJson([
                'success' => false,
                'code' => Response::HTTP_NOT_FOUND,
                'errors' => [
                    'message' => $errorMessage,
                    'code' => $errorCode,
                ],
            ]);
    }

    /**
     * Asserts that a response is a successful HTTP 201 Created response for a resource creation.
     *
     * @param TestResponse $response The HTTP response object to be asserted.
     * @param array $resourceAttributes The expected structure of the 'data' attribute in the JSON response.
     * @param array $expectedData The expected data to be found in the 'data' attribute of the JSON response.
     *
     * @return void
     */
    protected function assert201SuccessCreationResponse(TestResponse $response, array $resourceAttributes, array $expectedData)
    {
        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => $resourceAttributes,
                'success',
                'code',
            ])
            ->assertJson([
                'data' => $expectedData,
                'success' => true,
                'code' => Response::HTTP_CREATED,
            ]);
    }

    /**
     * Asserts the structure and content of an invalid payload response.
     *
     * @param TestResponse $response The HTTP response to be asserted.
     * @param array $expectedErrors The expected validation error messages and codes.
     * @return void
     */
    protected function assert400BadRequestValidationFailureResponse(TestResponse $response, array $expectedErrors)
    {
        $response
            ->assertBadRequest()
            ->assertJsonStructure([
                'data',
                'success',
                'code',
                'errors' => [
                    'message',
                    'validation',
                ],
            ])
            ->assertJson([
                'data' => [],
                'success' => false,
                'code' => Response::HTTP_BAD_REQUEST,
                'errors' => $expectedErrors,
            ]);
    }

    /**
     * Asserts that the response is a simple 400 Bad Request response with the specified error message and code.
     *
     * @param TestResponse $response The response to assert.
     * @param string $errorMessage The error message to check in the response.
     * @param int $code The error code to check in the response. Defaults to StatusCodeConstant::BAD_REQUEST_VALIDATION_FAILED_CODE.
     * @return void
     */
    protected function assert400BadRequestSimpleResponse(TestResponse $response, string $errorMessage, int $code = StatusCodeConstant::BAD_REQUEST_VALIDATION_FAILED_CODE)
    {
        $response
            ->assertBadRequest()
            ->assertJsonStructure([
                'success',
                'code',
                'errors' => [
                    'message',
                    'code',
                ],
            ])
            ->assertJson([
                'success' => false,
                'code' => Response::HTTP_BAD_REQUEST,
                'errors' => [
                    'message' => $errorMessage,
                    'code' => $code,
                ],
            ]);
    }

    /**
     * Asserts that the given TestResponse represents a 409 Conflict response for resource conflicts.
     *
     * @param TestResponse $response The response to be asserted.
     * @param array|string $errorMessage The error message to be checked in the response.
     * @param int $code The error code to be checked in the response. Defaults to StatusCodeConstant::RESOURCE_CONFLICT.
     *
     * @return void
     */
    protected function assert409ResourceConflictResponse(TestResponse $response, array|string $errorMessage, int $code = StatusCodeConstant::RESOURCE_CONFLICT)
    {
        $response
            ->assertConflict()
            ->assertJsonStructure([
                'success',
                'code',
                'errors' => [
                    'message',
                    'code',
                ],
            ])
            ->assertJson([
                'success' => false,
                'code' => Response::HTTP_CONFLICT,
                'errors' => [
                    'message' => $errorMessage,
                    'code' => $code,
                ],
            ]);
    }

    /**
     * Asserts that the given TestResponse represents a 500 Internal Server Error response for failures.
     *
     * @param TestResponse $response The response to be asserted.
     * @param string $errorMessage The error message to be checked in the response.
     * @param int $errorCode The error code to be checked in the response. Defaults to StatusCodeConstant::INTERNAL_SERVER_ERROR_DEFAULT_CODE.
     *
     * @return void
     */
    protected function assert500FailureResponse(TestResponse $response, string $errorMessage, int $errorCode = StatusCodeConstant::INTERNAL_SERVER_ERROR_DEFAULT_CODE)
    {
        $response
            ->assertInternalServerError()
            ->assertJsonStructure([
                'success',
                'code',
                'errors' => [
                    'message',
                    'code',
                ],
            ]);

        $response->assertJson([
            'success' => false,
            'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'errors' => [
                'message' => $errorMessage,
                'code' => $errorCode,
            ],
        ]);
    }

    /**
     * Asserts that the given TestResponse represents a 204 No Content response.
     *
     * @param TestResponse $response The response to be asserted.
     *
     * @return void
     */
    protected function assert204NoContentResponse(TestResponse $response)
    {
        $response->assertNoContent();
    }

    /**
     * Create a dummy image file for testing purposes.
     *
     * @param string $filename The name of the dummy image file. Default is 'test.png'.
     * @return UploadedFile The dummy image file instance.
     */
    protected function createDummyImageFile(string $filename = 'test.png'): UploadedFile
    {
        Storage::fake('images');
        return UploadedFile::fake()->image($filename);
    }

    /**
     * Create a dummy video file for testing purposes.
     *
     * This function generates a dummy video file with base64-encoded MP4 content.
     *
     * @return UploadedFile The dummy video file instance.
     */
    protected function createDummyVideoFile(string $filename = 'dummy_video.mp4', int $kilobytes = 1024): UploadedFile
    {
        // Use Laravel's Storage fake to simulate the file storage
        Storage::fake('videos');

        // Create a fake video file with the given filename and size
        return UploadedFile::fake()->create($filename, $kilobytes, 'video/mp4');
    }

    /**
     * Generates mock prohibited words for testing purposes.
     *
     * This function generates an array of mock prohibited words to be used in testing. If no expected prohibited words
     * are provided, it generates them using the NGWordGenerator class. It then mocks the behavior of the
     * CrmNGWordRepositoryInterface, specifically the getBulkNGWords method, to return the generated prohibited words.
     *
     * @param array|null $expectedNGWords An array of expected prohibited words for testing. If not provided, mock
     *                                    prohibited words are generated using NGWordGenerator::generate().
     * @return void
     */
    protected function generateMockNGWords(array $expectedNGWords = null)
    {
        // Generate mock prohibited words if not provided
        $expectedNGWords = $expectedNGWords ?? NGWordGenerator::generate();

        // Create a partial mock for the CrmNGWordRepositoryInterface
        $this->mock(CrmNGWordRepositoryInterface::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('getBulkNGWords')
            ->andReturn($expectedNGWords);
    }

    /**
     * Asserts that the provided TestResponse matches the expected structure of a successful custom response.
     *
     * @param TestResponse $response The response object to be checked.
     * @param array $expectedData The expected data array that should be present in the response.
     * @param int $statusCode The expected HTTP status code. Defaults to HTTP_OK (200).
     * @return void
     */
    protected function assertSuccessCustomResponse(TestResponse $response, array $expectedData, int $statusCode = Response::HTTP_OK)
    {
        $response
            ->assertStatus($statusCode)
            ->assertJsonStructure([
                'data',
                'success',
                'code',
            ])
            ->assertJson([
                'data' => $expectedData,
                'success' => true,
                'code' => $statusCode,
            ]);
    }

    /**
     * Assert that the response indicates a 401 Unauthorized status with a specific error message and code.
     *
     * @param TestResponse $response The response object to be checked.
     * @param string|array $errorMessage The error message to be asserted in the response. Default is 'HMAC authentication was failed. Please make sure your credentials again.'
     * @param int $code The error code to be asserted in the response. Default is StatusCodeConstant::UNAUTHORIZED_DEFAULT_CODE.
     *
     * @return void
     */
    protected function assert401UnauthorizedResponse(
        TestResponse $response,
        string|array $errorMessage = 'HMAC authentication was failed. Please make sure your credentials again.',
        int $code = StatusCodeConstant::UNAUTHORIZED_DEFAULT_CODE
    ) {
        // Ensure the response has a 401 Unauthorized status
        $response->assertUnauthorized();

        // Structure the expected JSON response
        $expectedResponse = [
            'errors' => [
                'message' => $errorMessage,
                'code' => $code,
            ],
        ];

        // Assert that the JSON response matches the expected structure
        $response->assertJson($expectedResponse);
    }

    /**
     * Assert that the given response is a 422 UnprocessableEntityException HTTP response with specific JSON structure.
     *
     * @param TestResponse $response The response to be asserted.
     * @param array|string $errorMessage The expected error message in the response.
     * @param int $code The expected error code in the response.
     *
     * @return void
     */
    protected function assert422UnprocessableEntityResponse(TestResponse $response, array|string $errorMessage, int $code = StatusCodeConstant::UNPROCESSABLE_ENTITY_DEFAULT_CODE): void
    {
        $response
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => [
                    'message' => $errorMessage,
                    'code' => $code,
                ],
            ]);
    }
}
