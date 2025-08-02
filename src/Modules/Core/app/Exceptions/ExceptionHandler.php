<?php

namespace Modules\Core\app\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Core\app\Resources\ErrorResource;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ExceptionHandler extends Handler
{
    /**
     * The configuration for handling exceptions.
     *
     * @var array
     */
    protected $exceptionConfig;

    /**
     * Indicates that an exception instance should only be reported once.
     *
     * @var bool
     */
    protected $withoutDuplicates = true;

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        /**
         * Handle validation exceptions and respond with an error resource.
         *
         * @param  ValidationException  $e
         * @param  Request  $request
         * @return JsonResponse
         */
        $this->renderable(function (ValidationException $e, Request $request) {
            if ($request->is('*')) {
                return (new ErrorResource($e))->response($request);
            }
        });

        /**
         * Report the exception to the application.
         *
         * @param  Throwable  $e
         * @return void
         */
        $this->reportable(function (Throwable $e) {
            $class = get_class($e);
            if (app()->bound('sentry') && Config::get('api.sentry_laravel') === true) {
                if (in_array($class, Config::get('api.should_report_exception'))) {
                    Integration::captureUnhandledException($e);
                }
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * If the exception is an instance of ModelNotFoundException, throw a ResourceNotFoundException.
     * Otherwise, delegate to the parent's render method.
     *
     * @param Request $request
     * @param Throwable $e
     * @return Response
     * @throws Throwable
     * @throws ResourceNotFoundException  If the exception is an instance of ModelNotFoundException.
     */
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof ModelNotFoundException) {
            Log::error('Received an exception', [$e]);
            throw new FatalErrorException();
        }

        if ($e instanceof NotFoundHttpException || $e instanceof MethodNotAllowedHttpException) {
            Log::error('Received an exception', [$e]);
            throw new RouteNotFoundException();
        }

        Log::info('Render an exception into an HTTP response.', ['Request:' => $request, 'Exception:' => $e]);
        return parent::render($request, $e);
    }
}
