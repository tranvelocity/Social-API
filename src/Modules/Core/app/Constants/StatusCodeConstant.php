<?php

declare(strict_types=1);

namespace Modules\Core\app\Constants;

/**
 * HTTP defines these standard status codes that can be used to convey the results of a client’s request. The status codes are divided into five categories.
 *  1xx: Informational – Communicates transfer protocol-level information.
 *  2xx: Success – Indicates that the client’s request was accepted successfully.
 *  3xx: Redirection – Indicates that the client must take some additional action in order to complete their request.
 *  4xx: Client Error – This category of error status codes points the finger at clients.
 *  5xx: Server Error – The server takes responsibility for these error status codes.
 *
 * Read more: https://restfulapi.net/http-status-codes/
 */
class StatusCodeConstant
{
    // 2xx Success Codes
    public const STATUS_CODE_OK = 200;
    public const STATUS_CODE_CREATED = 201;
    public const STATUS_CODE_ACCEPTED = 202;
    public const STATUS_CODE_NON_AUTHORITATIVE_INFORMATION = 203;
    public const STATUS_CODE_NO_CONTENT = 204;
    public const STATUS_CODE_RESET_CONTENT = 205;
    public const STATUS_CODE_PARTIAL_CONTENT = 206;
    public const STATUS_CODE_MULTI_STATUS = 207;
    public const STATUS_CODE_ALREADY_REPORTED = 208;
    public const STATUS_CODE_IM_USED = 226;

    // 4xx Client Error Codes
    public const STATUS_CODE_BAD_REQUEST = 400;
    public const STATUS_CODE_UNAUTHORIZED = 401;
    public const STATUS_CODE_PAYMENT_REQUIRED = 402; // Experimental
    public const STATUS_CODE_FORBIDDEN = 403;
    public const STATUS_CODE_NOT_FOUND = 404;
    public const STATUS_CODE_METHOD_NOT_ALLOWED = 405;
    public const STATUS_CODE_NOT_ACCEPTABLE = 406;
    public const STATUS_CODE_PROXY_AUTHENTICATION_REQUIRED = 407;
    public const STATUS_CODE_REQUEST_TIMEOUT = 408;
    public const STATUS_CODE_CONFLICT = 409;
    public const STATUS_CODE_GONE = 410;
    public const STATUS_CODE_LENGTH_REQUIRED = 411;
    public const STATUS_CODE_PRECONDITION_FAILED = 412;
    public const STATUS_CODE_REQUEST_ENTITY_TOO_LARGE = 413;
    public const STATUS_CODE_REQUEST_URI_TOO_LONG = 414;
    public const STATUS_CODE_UNSUPPORTED_MEDIA_TYPE = 415;
    public const STATUS_CODE_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public const STATUS_CODE_EXPECTATION_FAILED = 417;
    public const STATUS_CODE_I_AM_A_TEAPOT = 418; // RFC 2324
    public const STATUS_CODE_ENHANCE_YOUR_CALM = 420; // Twitter
    public const STATUS_CODE_UNPROCESSABLE_ENTITY = 422; // WebDAV
    public const STATUS_CODE_LOCKED = 423; // WebDAV
    public const STATUS_CODE_FAILED_DEPENDENCY = 424; // WebDAV
    public const STATUS_CODE_TOO_EARLY = 425; // WebDAV
    public const STATUS_CODE_UPGRADE_REQUIRED = 426;
    public const STATUS_CODE_PRECONDITION_REQUIRED = 428;
    public const STATUS_CODE_TOO_MANY_REQUESTS = 429;
    public const STATUS_CODE_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const STATUS_CODE_NO_RESPONSE = 444; // Nginx
    public const STATUS_CODE_RETRY_WITH = 449; // Microsoft
    public const STATUS_CODE_BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS = 450; // Microsoft
    public const STATUS_CODE_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    public const STATUS_CODE_CLIENT_CLOSED_REQUEST = 499; // Nginx

    // 5xx Server Error Codes
    public const STATUS_CODE_INTERNAL_SERVER_ERROR = 500;
    public const STATUS_CODE_NOT_IMPLEMENTED = 501;
    public const STATUS_CODE_BAD_GATEWAY = 502;
    public const STATUS_CODE_SERVICE_UNAVAILABLE = 503;
    public const STATUS_CODE_GATEWAY_TIMEOUT = 504;
    public const STATUS_CODE_HTTP_VERSION_NOT_SUPPORTED = 505; // Experimental
    public const STATUS_CODE_VARIANT_ALSO_NEGOTIATES = 506; // Experimental
    public const STATUS_CODE_INSUFFICIENT_STORAGE = 507; // WebDAV
    public const STATUS_CODE_LOOP_DETECTED = 508; // WebDAV
    public const STATUS_CODE_NOT_EXTENDED = 510;
    public const STATUS_CODE_NETWORK_AUTHENTICATION_REQUIRED = 511;

    // Application error codes
    public const UNAUTHORIZED_DEFAULT_CODE = 401001;
    public const UNAUTHORIZED_EXPIRED_CODE = 401002;
    public const UNAUTHORIZED_INVALID_SIGNATURE_CODE = 401003;
    public const UNAUTHORIZED_SESSION_INVALID_CODE = 401004;
    public const UNAUTHORIZED_SESSION_EXPIRED_CODE = 401005;

    public const INTERNAL_SERVER_ERROR_DEFAULT_CODE = 500001;
    public const EXTERNAL_API_FAILED_CODE = 500002;

    public const BAD_REQUEST_VALIDATION_FAILED_CODE = 400001;

    public const FORBIDDEN_PERMISSION_DENIED = 403001;

    public const RESOURCE_CONFLICT = 409001;

    public const RESOURCE_NOT_FOUND = 404001;
    public const ROUTE_NOT_FOUND = 404002;

    public const UNPROCESSABLE_ENTITY_DEFAULT_CODE = 422001;
    public const UNPROCESSABLE_ENTITY_VALUE_FAILED_CODE = 422002;
    public const UNPROCESSABLE_ENTITY_RESOURCE_FAILED_CODE = 422003;
}
