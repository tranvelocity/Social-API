<?php

declare(strict_types=1);

namespace Tranauth\Laravel\Api\AuditLog\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Tranauth\Laravel\Api\AuditLog\Models\AuditLog;
use Tranauth\Laravel\Api\AuditLog\Repositories\AuditLogRepositoryInterface;
use Tranauth\Laravel\Api\Resources\CreateApiResource;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class AuditLogService
 *
 * This service handles the business logic for interacting with audit logs.
 * It acts as an intermediary between the controllers and the repository layer,
 * providing methods for retrieving and creating audit logs.
 */
class AuditLogService
{
    /**
     * @var AuditLogRepositoryInterface
     */
    private AuditLogRepositoryInterface $auditLogRepository;

    /**
     * AuditLogService constructor.
     *
     * @param AuditLogRepositoryInterface $auditLogRepository The repository interface for handling audit logs.
     */
    public function __construct(AuditLogRepositoryInterface $auditLogRepository)
    {
        $this->auditLogRepository = $auditLogRepository;
    }

    /**
     * Retrieves a list of audit logs and their total count for a given admin UUID.
     *
     * @param string $adminUuid The UUID of the admin whose logs are to be retrieved.
     * @param array $params Optional parameters for filtering and pagination.
     *                      Supported keys: 'offset', 'limit', 'log', 'creation_from', 'creation_to'.
     * @return array An associative array containing:
     *               - 'data': The list of logs (iterable).
     *               - 'total': The total count of logs matching the criteria.
     */
    public function getLogs(string $adminUuid, array $params): array
    {
        return [
            'data' => $this->auditLogRepository->getLogs($adminUuid, $params),
            'total' => $this->auditLogRepository->getLogTotal($adminUuid, $params),
        ];
    }

    /**
     * Creates a new audit log entry for a given admin UUID.
     *
     * @param string $adminUuid The UUID of the admin associated with the log.
     * @param array $params Parameters for the log.
     *                      Should include 'log' as an array and other optional fields.
     * @return void
     */
    public function createLog(string $adminUuid, array $params): void
    {
        $this->auditLogRepository->createLog($adminUuid, $params);
    }
}
