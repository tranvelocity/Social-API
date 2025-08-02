<?php

namespace Tranauth\Laravel\Api\AuditLog\Resources;

use Tranauth\Laravel\Api\AuditLog\Models\AuditLog;
use Tranauth\Laravel\Api\Resources\ApiResource;

class AuditLogResource extends ApiResource
{
    /**
     * Constructor.
     *
     * @param AuditLog $auditLog
     */
    public function __construct(AuditLog $auditLog)
    {
        parent::__construct($auditLog);
    }

    /**
     * Defines the "data" property of the response.
     *
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'admin_uuid' => $this->resource->admin_uuid,
            'log' => json_encode($this->resource->log),
            'created_at' => $this->formatDateTime($this->resource->created_at),
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
