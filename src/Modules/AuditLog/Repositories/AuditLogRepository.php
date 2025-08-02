<?php

declare(strict_types=1);

namespace Tranauth\Laravel\Api\AuditLog\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Tranauth\Laravel\Api\AuditLog\Models\AuditLog;
use Tranauth\Laravel\Api\Databases\Repository;
use Tranauth\Laravel\Api\Requests\ApiRequest;

/**
 * Class AuditLogRepository
 *
 * This repository handles operations related to the AuditLog model, including
 * creating logs, fetching logs with optional filters, and retrieving log counts.
 */
class AuditLogRepository extends Repository implements AuditLogRepositoryInterface
{
    /**
     * Returns the model class associated with the repository.
     *
     * @return string Fully qualified class name of the model.
     */
    protected function model()
    {
        return AuditLog::class;
    }

    /**
     * Builds a query for fetching audit logs with optional filters.
     *
     * @param string $adminUuid The UUID of the admin to filter logs by.
     * @param array $params Optional parameters for filtering logs.
     *                      Supported keys: 'log', 'creation_from', 'creation_to'.
     * @return Builder The query builder instance.
     */
    private function buildQuery(string $adminUuid, array $params = []): Builder
    {
        $query = AuditLog::query()->where('admin_uuid', $adminUuid);

        $this->applyFilterIfExists($query, 'log', 'LIKE', $params);
        $this->applyDateTimeFilterIfExists($query, 'created_at', '>=', $params, 'creation_to');
        $this->applyDateTimeFilterIfExists($query, 'created_at', '<=', $params, 'creation_from');

        return $query;
    }

    /**
     * Retrieves the total number of logs for a given admin UUID, with optional filters.
     *
     * @param string $adminUuid The UUID of the admin to filter logs by.
     * @param array $params Optional parameters for filtering logs.
     * @return int The total count of logs matching the filters.
     */
    public function getLogTotal(string $adminUuid, array $params): int
    {
        return $this->buildQuery($adminUuid, $params)->count();
    }

    /**
     * Retrieves logs for a given admin UUID, with optional filters and pagination.
     *
     * @param string $adminUuid The UUID of the admin to filter logs by.
     * @param array $params Optional parameters for filtering logs.
     *                      Supported keys: 'offset', 'limit', 'log', 'creation_from', 'creation_to'.
     * @param bool $withPagination Whether to apply pagination (default: true).
     * @return iterable A collection of logs matching the filters.
     */
    public function getLogs(string $adminUuid, array $params, bool $withPagination = true): iterable
    {
        $query = $this->buildQuery($adminUuid, $params)
            ->orderBy('created_at', 'DESC');

        if ($withPagination) {
            $offset = $params['offset'] ?? ApiRequest::DEFAULT_OFFSET;
            $limit = $params['limit'] ?? ApiRequest::DEFAULT_LIMIT;

            $query->skip($offset)->limit($limit);
        }

        return $query->get();
    }

    /**
     * Creates a new log entry in the audit_logs table.
     *
     * @param string $adminUuid The UUID of the admin associated with the log.
     * @param array $params Parameters for the log.
     *                      Should include 'log' as an array and other optional fields.
     * @return void
     */
    public function createLog(string $adminUuid, array $params): void
    {
        $auditLog = new AuditLog();
        $params['uuid'] = Str::uuid();
        $params['admin_uuid'] = $adminUuid;

        // Manually set created_at and updated_at
        $currentTimestamp = now();
        $params['created_at'] = $currentTimestamp;
        $params['updated_at'] = $currentTimestamp;

        $auditLog->fill($params);
        $auditLog->save();
    }
}
