<?php

namespace Modules\Core\app\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthCheckController extends Controller
{
    /**
     * Check the health of the DB connection.
     * [Note] Currently, it only checks the DB connection, not the Redis connection.
     *
     * Endpoint:  /health  [GET]
     *
     * @return JsonResponse
     */
    public function checkDatabase(): JsonResponse
    {
        $databaseHealth = $this->isDatabaseHealthy();

        if (!$databaseHealth) {
            Log::error('Health check: Database connection failed.');
        }

        return $this->createHealthResponse($databaseHealth);
    }

    /**
     * Attempt to execute a query to check the database connection.
     *
     * @return bool
     */
    private function isDatabaseHealthy(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create a JSON response indicating the health status.
     *
     * @param bool $databaseHealth
     * @return JsonResponse
     */
    private function createHealthResponse(bool $databaseHealth): JsonResponse
    {
        $status = $databaseHealth ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR;

        return response()->json([
            'database' => $databaseHealth,
            'redis' => null, // Currently not checked
        ], $status);
    }
}
