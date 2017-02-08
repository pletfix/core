<?php

/*
 * HTTP Status codes
 *
 * The list of codes is complete according to the HTTP Status Code Registry:
 * http://www.iana.org/assignments/http-status-codes.
 *
 * Last updated: 2016-03-01
 *
 * Unless otherwise noted, the status code is defined in RFC2616.
 */

// todo evtl als constante bei Response (vrgl. Laravel)

if (!defined('HTTP_STATUS_OK')) {

    // 1xx Informational
    define('HTTP_STATUS_CONTINUE',                  100);
    define('HTTP_STATUS_SWITCHING_PROTOCOLS',       101);
    define('HTTP_STATUS_PROCESSING',                102); // RFC2518

    // 2xx Success
    define('HTTP_STATUS_OK',                        200);
    define('HTTP_STATUS_CREATED',                   201);
    define('HTTP_STATUS_ACCEPTED',                  202);
    define('HTTP_STATUS_NON_AUTHORITATIVE_INFO',    203);
    define('HTTP_STATUS_NO_CONTENT',                204);
    define('HTTP_STATUS_RESET_CONTENT',             205);
    define('HTTP_STATUS_PARTIAL_CONTENT',           206);
    define('HTTP_STATUS_MULTI_STATUS',              207); // RFC4918
    define('HTTP_STATUS_ALREADY_REPORTED',          208); // RFC5842
    define('HTTP_STATUS_IM_USED',                   226); // RFC3229

    // 3xx Redirection
    define('HTTP_STATUS_MULTIPLE_CHOICES',          300);
    define('HTTP_STATUS_MOVED_PERMANENTLY',         301);
    define('HTTP_STATUS_FOUND',                     302);
    define('HTTP_STATUS_SEE_OTHER',                 303);
    define('HTTP_STATUS_NOT_MODIFIED',              304);
    define('HTTP_STATUS_USE_PROXY',                 305);
    define('HTTP_STATUS_RESERVED',                  306);
    define('HTTP_STATUS_TEMPORARY_REDIRECT',        307);
    define('HTTP_STATUS_PERMANENTLY_REDIRECT',      308); // RFC7238

    // 4xx Client Error
    define('HTTP_STATUS_BAD_REQUEST',               400);
    define('HTTP_STATUS_UNAUTHORIZED',              401);
    define('HTTP_STATUS_PAYMENT_REQUIRED',          402);
    define('HTTP_STATUS_FORBIDDEN',                 403);
    define('HTTP_STATUS_NOT_FOUND',                 404);
    define('HTTP_STATUS_METHOD_NOT_ALLOWED',        405);
    define('HTTP_STATUS_NOT_ACCEPTABLE',            406);
    define('HTTP_STATUS_PROXY_AUTH_REQUIRED',       407);
    define('HTTP_STATUS_REQUEST_TIMEOUT',           408);
    define('HTTP_STATUS_CONFLICT',                  409);
    define('HTTP_STATUS_GONE',                      410);
    define('HTTP_STATUS_LENGTH_REQUIRED',           411);
    define('HTTP_STATUS_PRECONDITION_FAILED',       412);
    define('HTTP_STATUS_REQUEST_ENTITY_TOO_LARGE',  413);
    define('HTTP_STATUS_REQUEST_URI_TOO_LONG',      414);
    define('HTTP_STATUS_UNSUPPORTED_MEDIA_TYPE',    415);
    define('HTTP_STATUS_RANGE_NOT_SATISFIABLE',     416);
    define('HTTP_STATUS_EXPECTATION_FAILED',        417);
    define('HTTP_STATUS_I_AM_A_TEAPOT',             418); // RFC2324
    define('HTTP_STATUS_MISDIRECTED_REQUEST',       421); // RFC7540
    define('HTTP_STATUS_UNPROCESSABLE_ENTITY',      422); // RFC4918
    define('HTTP_STATUS_LOCKED',                    423); // RFC4918
    define('HTTP_STATUS_FAILED_DEPENDENCY',         424); // RFC4918
    define('HTTP_STATUS_UPGRADE_REQUIRED',          426); // RFC2817
    define('HTTP_STATUS_PRECONDITION_REQUIRED',     428); // RFC6585
    define('HTTP_STATUS_TOO_MANY_REQUESTS',         429); // RFC6585
    define('HTTP_STATUS_HEADER_FIELDS_TOO_LARGE',   431); // RFC6585
    define('HTTP_STATUS_UNAVAILABLE',               451);

    // 5xx Server Error
    define('HTTP_STATUS_INTERNAL_SERVER_ERROR',     500);
    define('HTTP_STATUS_NOT_IMPLEMENTED',           501);
    define('HTTP_STATUS_BAD_GATEWAY',               502);
    define('HTTP_STATUS_SERVICE_UNAVAILABLE',       503);
    define('HTTP_STATUS_GATEWAY_TIMEOUT',           504);
    define('HTTP_STATUS_VERSION_NOT_SUPPORTED',     505);
    define('HTTP_STATUS_VARIANT_ALSO_NEGOTIATES',   506); // RFC2295
    define('HTTP_STATUS_INSUFFICIENT_STORAGE',      507); // RFC4918
    define('HTTP_STATUS_LOOP_DETECTED',             508); // RFC5842
    define('HTTP_STATUS_NOT_EXTENDED',              510); // RFC2774
    define('HTTP_STATUS_NETWORK_AUTH_REQUIRED',     511); // RFC6585
}

if (!function_exists('http_status_text')) {
    /**
     * Translate HTTP Status code to plain text.
     *
     * @param int $status
     * @return string
     */
    function http_status_text($status)
    {
        $statusTexts = [

            // 1xx Informational
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',

            // 2xx Success
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            208 => 'Already Reported',
            226 => 'IM Used',

            // 3xx Redirection
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',

            // 4xx Client Error
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Payload Too Large',
            414 => 'URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot',
            421 => 'Misdirected Request',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            451 => 'Unavailable For Legal Reasons',

            // 5xx Server Error
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            508 => 'Loop Detected',
            510 => 'Not Extended',
            511 => 'Network Authentication Required',
        ];

        return isset($statusTexts[$status]) ? $statusTexts[$status] : '';
    }
}