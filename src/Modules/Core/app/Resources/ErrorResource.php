<?php

namespace Modules\Core\app\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;
use Modules\Core\app\Constants\StatusCodeConstant;
use Modules\Core\app\Traits\WithApiWrapping;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ErrorResource
 *
 * Custom JSON resource for handling errors in API responses.
 *
 * @package Modules\Core\Resources
 */
class ErrorResource extends JsonResource
{
    use WithApiWrapping;

    public function response($request = null): JsonResponse
    {
        $response = parent::response($request);
        $response->setStatusCode($this->getErrorHttpCode());

        return $response;
    }

    /**
     * Convert the resource to an array.
     *
     * @param Request|null $request
     *
     * @return object
     */
    public function toArray($request): object
    {
        return (object)[];
    }

    /**
     * Get the HTTP status code for the error.
     *
     * @return int
     */
    public function getErrorHttpCode(): int
    {
        return match (get_class($this->resource)) {
            ValidationException::class => StatusCodeConstant::STATUS_CODE_BAD_REQUEST,
            HttpException::class => $this->resource->getStatusCode(),
        };
    }

    /**
     * Get the error message for the response.
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        if (config('app.debug')) {
            return '[' . get_class($this->resource) . '] ' . $this->resource->getMessage();
        }

        return match (get_class($this->resource)) {
            ValidationException::class => 'The request parameters are incorrect, please make sure to follow the documentation about request parameters of the resource.',
            HttpException::class => $this->resource->getMessage(),
        };
    }

    /**
     * Get additional details for the error.
     *
     * @return array
     */
    public function getErrorDetails(): array
    {
        if ($this->resource instanceof ValidationException) {
            return $this->transformValidationErrors($this->resource);
        }

        return [];
    }

    /**
     * Transform validation errors into a structured format.
     *
     * @param ValidationException $validationException
     *
     * @return array
     */
    protected function transformValidationErrors(ValidationException $validationException): array
    {
        $validator = $validationException->validator;
        $errorMessages = $validator->errors();
        $failedRules = $validator->failed();

        $validation = [];
        foreach ($failedRules as $attribute => $rules) {
            $errors = [];

            foreach (array_keys($rules) as $i => $rule) {
                $errors[] = [
                    'key' => $rule,
                    'message' => $errorMessages->get($attribute)[$i],
                ];
            }

            $validation[] = compact('attribute', 'errors');
        }

        return compact('validation');
    }

    /**
     * Get additional data for the response.
     *
     * @param Request $request
     *
     * @return array
     */
    public function with(Request $request): array
    {
        return [
            'success' => false,
            'code' => $this->getErrorHttpCode(),
            'errors' => array_merge(
                [
                    'message' => $this->getErrorMessage(),
                ],
                $this->getErrorDetails()
            ),
        ];
    }
}
