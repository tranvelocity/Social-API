<?php

namespace Tranauth\Laravel\Api\AuditLog\Controllers;

use Tranauth\Laravel\Api\AuditLog\Requests\SearchLogRequest;
use Tranauth\Laravel\Api\AuditLog\Resources\AuditLogResource;
use Tranauth\Laravel\Api\AuditLog\Services\AuditLogService;
use Tranauth\Laravel\Api\Resources\ApiCollection;
use Tranauth\Laravel\Api\Resources\ApiResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class AuditLogController extends Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;

    /**
     * @var AuditLogService
     */
    private AuditLogService $auditLogService;

    /**
     * AuditLogController constructor.
     *
     * @param AuditLogService $auditLogService
     */
    public function __construct(
        AuditLogService $auditLogService
    ) {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Get a list of audit logs.
     *
     * @param  SearchLogRequest  $request
     * @return ApiCollection
     */
    public function index(SearchLogRequest $request): ApiCollection
    {
        $adminUuid = $request->authorized_admin['uuid'];
        $params = $request->getValidatedParams();

        $result = $this->auditLogService->getLogs($adminUuid, $params);

        return (new ApiCollection(AuditLogResource::collection($result['data'])))
            ->setOffsetLimit($params['offset'], $params['limit'], $result['total']);
    }
}
