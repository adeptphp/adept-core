<?php

namespace Adept\Http;

class StatusCode implements StatusCodeInterface
{
    /**
     * @var array
     */
    protected static $statuses = [
        // Informational 1xx
        self::STATUS_CONTINUE => 'Continue',
        self::STATUS_SWITCHING_PROTOCOLS => 'Switching Protocols',
        self::STATUS_PROCESSING => 'Processing',
        // Successful 2xx
        self::STATUS_OK => 'OK',
        self::STATUS_CREATED => 'Created',
        self::STATUS_ACCEPTED => 'Accepted',
        self::STATUS_NON_AUTHORITATIVE_INFORMATION => 'Non-Authoritative Information',
        self::STATUS_NO_CONTENT => 'No Content',
        self::STATUS_RESET_CONTENT => 'Reset Content',
        self::STATUS_PARTIAL_CONTENT => 'Partial Content',
        self::STATUS_MULTI_STATUS => 'Multi-Status',
        self::STATUS_ALREADY_REPORTED => 'Already Reported',
        self::STATUS_IM_USED => 'IM Used',
        // Redirection 3xx
        self::STATUS_MULTIPLE_CHOICES => 'Multiple Choices',
        self::STATUS_MOVED_PERMANENTLY => 'Moved Permanently',
        self::STATUS_FOUND => 'Found',
        self::STATUS_SEE_OTHER => 'See Other',
        self::STATUS_NOT_MODIFIED => 'Not Modified',
        self::STATUS_USE_PROXY => 'Use Proxy',
        self::STATUS_RESERVED => '(Unused)',
        self::STATUS_TEMPORARY_REDIRECT => 'Temporary Redirect',
        self::STATUS_PERMANENT_REDIRECT => 'Permanent Redirect',
        // Client Error 4xx
        self::STATUS_BAD_REQUEST => 'Bad Request',
        self::STATUS_UNAUTHORIZED => 'Unauthorized',
        self::STATUS_PAYMENT_REQUIRED => 'Payment Required',
        self::STATUS_FORBIDDEN => 'Forbidden',
        self::STATUS_NOT_FOUND => 'Not Found',
        self::STATUS_METHOD_NOT_ALLOWED => 'Method Not Allowed',
        self::STATUS_NOT_ACCEPTABLE => 'Not Acceptable',
        self::STATUS_PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
        self::STATUS_REQUEST_TIMEOUT => 'Request Timeout',
        self::STATUS_CONFLICT => 'Conflict',
        self::STATUS_GONE => 'Gone',
        self::STATUS_LENGTH_REQUIRED => 'Length Required',
        self::STATUS_PRECONDITION_FAILED => 'Precondition Failed',
        self::STATUS_PAYLOAD_TOO_LARGE => 'Request Entity Too Large',
        self::STATUS_URI_TOO_LONG => 'Request-URI Too Long',
        self::STATUS_UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type',
        self::STATUS_RANGE_NOT_SATISFIABLE => 'Requested Range Not Satisfiable',
        self::STATUS_EXPECTATION_FAILED => 'Expectation Failed',
        self::STATUS_IM_A_TEAPOT => 'I\'m a teapot',
        self::STATUS_MISDIRECTED_REQUEST => 'Misdirected Request',
        self::STATUS_UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
        self::STATUS_LOCKED => 'Locked',
        self::STATUS_FAILED_DEPENDENCY => 'Failed Dependency',
        self::STATUS_UPGRADE_REQUIRED => 'Upgrade Required',
        self::STATUS_PRECONDITION_REQUIRED => 'Precondition Required',
        self::STATUS_PRECONDITION_REQUIRED => 'Too Many Requests',
        self::STATUS_TOO_MANY_REQUESTS => 'Request Header Fields Too Large',
        self::STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE => 'Connection Closed Without Response',
        self::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS => 'Unavailable For Legal Reasons',
        499 => 'Client Closed Request',
        // Server Error 5xx
        self::STATUS_INTERNAL_SERVER_ERROR => 'Internal Server Error',
        self::STATUS_NOT_IMPLEMENTED => 'Not Implemented',
        self::STATUS_BAD_GATEWAY => 'Bad Gateway',
        self::STATUS_SERVICE_UNAVAILABLE => 'Service Unavailable',
        self::STATUS_GATEWAY_TIMEOUT => 'Gateway Timeout',
        self::STATUS_VERSION_NOT_SUPPORTED => 'HTTP Version Not Supported',
        self::STATUS_VARIANT_ALSO_NEGOTIATES => 'Variant Also Negotiates',
        self::STATUS_INSUFFICIENT_STORAGE => 'Insufficient Storage',
        self::STATUS_LOOP_DETECTED => 'Loop Detected',
        self::STATUS_NOT_EXTENDED => 'Not Extended',
        self::STATUS_NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error',
    ];

    /**
     * @return array
     */
    public static function all()
    {
        return static::$statuses;
    }

    /**
     * @param int $code
     *
     * @return string
     */
    public static function getReasonPhrase($code)
    {
        return isset(static::$statuses[$code]) ? static::$statuses[$code] : '';
    }
}
