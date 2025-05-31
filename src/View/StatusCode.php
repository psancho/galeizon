<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\View;

class StatusCode
{
    // [Informational 1xx]
    const HTTP_100_CONTINUE = 100;                        // RFC 2616
    const HTTP_101_SWITCHING_PROTOCOLS = 101;             // RFC 2616
    const HTTP_102_PROCESSING = 102;                      // WebDAV
    // [Successful 2xx]
    const HTTP_200_OK = 200;                              // RFC 2616
    const HTTP_201_CREATED = 201;                         // RFC 2616
    const HTTP_202_ACCEPTED = 202;                        // RFC 2616
    const HTTP_203_NONAUTHORITATIVE_INFORMATION = 203;    // RFC 2616
    const HTTP_204_NO_CONTENT = 204;                      // RFC 2616
    const HTTP_205_RESET_CONTENT = 205;                   // RFC 2616
    const HTTP_206_PARTIAL_CONTENT = 206;                 // RFC 2616
    const HTTP_207_MULTI_STATUS = 207;                    // WebDAV
    const HTTP_208_ALREADY_REPORTED = 208;                // WebDAV
    const HTTP_210_CONTENT_DIFFERENT = 210;               // WebDAV
    const HTTP_226_IM_USED = 226;                         // RFC 3229
    // [Redirection 3xx]
    const HTTP_300_MULTIPLE_CHOICES = 300;                // RFC 2616
    const HTTP_301_MOVED_PERMANENTLY = 301;               // RFC 2616
    const HTTP_302_FOUND = 302;                           // RFC 2616
    const HTTP_303_SEE_OTHER = 303;                       // RFC 2616
    const HTTP_304_NOT_MODIFIED = 304;                    // RFC 2616
    const HTTP_305_USE_PROXY = 305;                       // RFC 2616
    const HTTP_306_UNUSED = 306;                          //
    const HTTP_307_TEMPORARY_REDIRECT = 307;              // RFC 2616
    const HTTP_308_PERMANENT_REDIRECT = 308;              // RFC 7238
    const HTTP_310_TOO_MANY_REDIRECT = 310;               // NET::error
    // [Client Error 4xx]
    const HTTP_400_BAD_REQUEST = 400;                     // RFC 2616
    const HTTP_401_UNAUTHORIZED = 401;                    // RFC 2616
    const HTTP_402_PAYMENT_REQUIRED = 402;                // RFC 2616
    const HTTP_403_FORBIDDEN = 403;                       // RFC 2616
    const HTTP_404_NOT_FOUND = 404;                       // RFC 2616
    const HTTP_405_METHOD_NOT_ALLOWED = 405;              // RFC 2616
    const HTTP_406_NOT_ACCEPTABLE = 406;                  // RFC 2616
    const HTTP_407_PROXY_AUTHENTICATION_REQUIRED = 407;   // RFC 2616
    const HTTP_408_REQUEST_TIMEOUT = 408;                 // RFC 2616
    const HTTP_409_CONFLICT = 409;                        // RFC 2616
    const HTTP_410_GONE = 410;                            // RFC 2616
    const HTTP_411_LENGTH_REQUIRED = 411;                 // RFC 2616
    const HTTP_412_PRECONDITION_FAILED = 412;             // RFC 2616
    const HTTP_413_REQUEST_ENTITY_TOO_LARGE = 413;        // RFC 2616
    const HTTP_414_REQUEST_URI_TOO_LONG = 414;            // RFC 2616
    const HTTP_415_UNSUPPORTED_MEDIA_TYPE = 415;          // RFC 2616
    const HTTP_416_REQUESTED_RANGE_NOT_SATISFIABLE = 416; // RFC 2616
    const HTTP_417_EXPECTATION_FAILED = 417;              // RFC 2616
    const HTTP_418_TEAPOT = 418;                          // RFC 2324 (1st april)
    const HTTP_419_AUTHENTICATION_TIMEOUT = 419;          //
    const HTTP_420_ENHANCE_YOUR_CALM = 420;               // Twitter
    const HTTP_421_MISDIRECTED_REQUEST = 421;             // HTTP/2
    const HTTP_422_UNPROCESSABLE_ENTITY = 422;            // WebDAV
    const HTTP_423_LOCKED = 423;                          // WebDAV
    const HTTP_424_METHOD_FAILURE = 424;                  // WebDAV
    const HTTP_425_UNORDERED_COLLECTION = 425;            // WebDAV
    const HTTP_426_UPGRADE_REQUIRED = 426;                //
    const HTTP_428_PRECONDITION_REQUIRED = 428;           // RFC 6585
    const HTTP_429_TOO_MANY_REQUESTS = 429;               // RFC 6585
    const HTTP_431_REQUEST_HEADER_FIELDS_TOO_LARGE = 431; // RFC 6585
    const HTTP_440_LOGIN_TIMEOUT = 440;                   // Microsoft
    const HTTP_444_NO_RESPONSE = 444;                     // Nginx
    const HTTP_449_RETRY_WITH = 449;                      // Microsoft
    const HTTP_450_BLOCKED_BY_PARENTAL_CONTROLS = 450;    // Microsoft
    const HTTP_451_UNAVAILABLE_FOR_LEGAL_REASONS = 451;   // Draft
    const HTTP_456_UNRECOVERABLE_ERROR = 456;             // WebDAV
    const HTTP_494_REQUEST_HEADER_TOO_LARGE = 494;        // Nginx
    const HTTP_495_CERT_ERROR = 495;                      // Nginx
    const HTTP_496_NO_CERT = 496;                         // Nginx
    const HTTP_497_HTTP_TO_HTTPS = 497;                   // Nginx
    const HTTP_498_TOKEN_EXPIRED_INVALID = 498;           // Esri
    const HTTP_499_CLIENT_HAS_CLOSED_CONNECTION = 499;    // Nginx
    // [Server ERROR 5XX]
    const HTTP_500_INTERNAL_SERVER_ERROR = 500;           // RFC 2616
    const HTTP_501_NOT_IMPLEMENTED = 501;                 // RFC 2616
    const HTTP_502_BAD_GATEWAY = 502;                     // RFC 2616
    const HTTP_503_SERVICE_UNAVAILABLE = 503;             // RFC 2616
    const HTTP_504_GATEWAY_TIMEOUT = 504;                 // RFC 2616
    const HTTP_505_VERSION_NOT_SUPPORTED = 505;           // RFC 2616
    const HTTP_506_VARIANT_ALSO_NEGOCIATE = 506;          // RFC 2295
    const HTTP_507_INSUFFICIENT_STORAGE = 507;            // WebDAV
    const HTTP_508_LOOP_DETECTED = 508;                   // WebDAV
    const HTTP_509_BANDWIDTH_LIMIT_EXCEEDED = 509;        // servers
    const HTTP_510_NOT_EXTENDED = 510;                    // RFC 6585
    const HTTP_511_NETWORK_AUTHENTICATION_REQUIRED = 511; // RFC 2774
    const HTTP_520_SERVER_RETURNING_UNKNOWN_ERROR = 520;  //
    const HTTP_598_NETWORK_READ_TIMEOUT_ERROR = 598;      // Microsoft
    const HTTP_599_NETWORK_CONNECT_TIMEOUT_ERROR = 599;   // Microsoft

    /** @var array<int, string> */
    private static $_messages = [
        // [Informational 1xx]
        100 => '100 Continue',
        101 => '101 Switching Protocols',
        102 => '102 Processing',
        // [Successful 2xx]
        200 => '200 OK',
        201 => '201 Created',
        202 => '202 Accepted',
        203 => '203 Non-Authoritative Information',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',
        207 => '207 Multi-Status',
        208 => '208 Already Reported',
        210 => '210 Content Different',
        226 => '226 IM Used',
        // [Redirection 3xx]
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        305 => '305 Use Proxy',
        306 => '306 (Unused)',
        307 => '307 Temporary Redirect',
        308 => '308 Permanent Redirect',
        310 => '310 Too many Redirect',
        // [Client Error 4xx]
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        402 => '402 Payment Required',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        407 => '407 Proxy Authentication Required',
        408 => '408 Request Timeout',
        409 => '409 Conflict',
        410 => '410 Gone',
        411 => '411 Length Required',
        412 => '412 Precondition Failed',
        413 => '413 Request Entity Too Large',
        414 => '414 Request-URI Too Long',
        415 => '415 Unsupported Media Type',
        416 => '416 Requested Range Not Satisfiable',
        417 => '417 Expectation Failed',
        418 => '418 I\'m a teapot',
        419 => '419 Authentication Timeout',
        420 => '420 Enhance Your Calm',
        421 => '421 Misdirected Request',
        422 => '422 Unprocessable entity',
        423 => '423 Locked',
        424 => '424 Method failure',
        425 => '425 Unordered Collection',
        426 => '426 Upgrade Required',
        428 => '428 Precondition Required',
        429 => '429 Too Many Requests',
        431 => '431 Request Header Fields Too Large',
        440 => '440 Login Timeout',
        444 => '444 No Response',
        449 => '449 Retry With',
        450 => '450 Blocked by Windows Parental Controls',
        451 => '451 Unavailable For Legal Reasons',
        456 => '456 Unrecoverable Error',
        494 => '494 Request Header Too Large',
        495 => '495 Cert Error',
        496 => '496 No Cert',
        497 => '497 HTTP to HTTPS',
        498 => '498 Token expired/invalid',
        499 => '499 Client has closed connection',
        // [Server Error 5xx]
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
        505 => '505 HTTP Version Not Supported',
        506 => '506 Variant also negociate',
        507 => '507 Insufficient storage',
        508 => '508 Loop detected',
        509 => '509 Bandwidth Limit Exceeded',
        510 => '510 Not extended',
        511 => '511 Network Authentication Required',
        520 => '520 Web server is returning an unknown error',
        598 => '598 Network read timeout error',
        599 => '599 Network connect timeout error',
    ];

    public static function getMessageForCode(int $code): string
    {
        return array_key_exists($code, self::$_messages) ? self::$_messages[$code] : (string) $code;
    }
}
