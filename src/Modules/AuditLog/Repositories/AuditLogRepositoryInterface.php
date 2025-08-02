<?php

declare(strict_types=1);

namespace Tranauth\Laravel\Api\AuditLog\Repositories;

use Tranauth\Laravel\Api\Databases\RepositoryInterface;
use Tranauth\Laravel\Api\AuditLog\Models\AuditLog;
use Tranauth\Laravel\Api\Requests\ApiRequest;
use Illuminate\Support\Str;

interface AuditLogRepositoryInterface extends RepositoryInterface
{
    /**
     * Retrieves the total number of logs for a given admin UUID, with optional filters.
     *
     * @param string $adminUuid The UUID of the admin to filter logs by.
     * @param array $params Optional parameters for filtering logs.
     * @return int The total count of logs matching the filters.
     */
    public function getLogTotal(string $adminUuid, array $params): int;

    /**
     * Retrieves logs for a given admin UUID, with optional filters and pagination.
     *
     * @param string $adminUuid The UUID of the admin to filter logs by.
     * @param array $params Optional parameters for filtering logs.
     *                      Supported keys: 'offset', 'limit', 'log', 'creation_from', 'creation_to'.
     * @param bool $withPagination Whether to apply pagination (default: true).
     * @return iterable A collection of logs matching the filters.
     */
    public function getLogs(string $adminUuid, array $params, bool $withPagination = true): iterable;

    /**
     * Creates a new log entry in the audit_logs table.
     *
     * @param string $adminUuid The UUID of the admin associated with the log.
     * @param array $params Parameters for the log.
     *                      Should include 'log' as an array and other optional fields.
     * @return void
     */
    public function createLog(string $adminUuid, array $params): void;
}
