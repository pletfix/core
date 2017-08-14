<?php

namespace Core\Services\Contracts;

/**
 * The Response class represents an HTTP response.
 */
interface Response
{
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

    //= 1xx Informational
    const HTTP_CONTINUE                 = 100;
    const HTTP_SWITCHING_PROTOCOLS      = 101;
    const HTTP_PROCESSING               = 102; // RFC2518

    //= 2xx Success
    const HTTP_OK                       = 200;
    const HTTP_CREATED                  = 201;
    const HTTP_ACCEPTED                 = 202;
    const HTTP_NON_AUTHORITATIVE_INFO   = 203;
    const HTTP_NO_CONTENT               = 204;
    const HTTP_RESET_CONTENT            = 205;
    const HTTP_PARTIAL_CONTENT          = 206;
    const HTTP_MULTI_STATUS             = 207; // RFC4918
    const HTTP_ALREADY_REPORTED         = 208; // RFC5842
    const HTTP_IM_USED                  = 226; // RFC3229

    //= 3xx Redirection
    const HTTP_MULTIPLE_CHOICES         = 300;
    const HTTP_MOVED_PERMANENTLY        = 301;
    const HTTP_FOUND                    = 302;
    const HTTP_SEE_OTHER                = 303;
    const HTTP_NOT_MODIFIED             = 304;
    const HTTP_USE_PROXY                = 305;
    const HTTP_RESERVED                 = 306;
    const HTTP_TEMPORARY_REDIRECT       = 307;
    const HTTP_PERMANENTLY_REDIRECT     = 308; // RFC7238

    //= 4xx Client Error
    const HTTP_BAD_REQUEST              = 400;
    const HTTP_UNAUTHORIZED             = 401;
    const HTTP_PAYMENT_REQUIRED         = 402;
    const HTTP_FORBIDDEN                = 403;
    const HTTP_NOT_FOUND                = 404;
    const HTTP_METHOD_NOT_ALLOWED       = 405;
    const HTTP_NOT_ACCEPTABLE           = 406;
    const HTTP_PROXY_AUTH_REQUIRED      = 407;
    const HTTP_REQUEST_TIMEOUT          = 408;
    const HTTP_CONFLICT                 = 409;
    const HTTP_GONE                     = 410;
    const HTTP_LENGTH_REQUIRED          = 411;
    const HTTP_PRECONDITION_FAILED      = 412;
    const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    const HTTP_REQUEST_URI_TOO_LONG     = 414;
    const HTTP_UNSUPPORTED_MEDIA_TYPE   = 415;
    const HTTP_RANGE_NOT_SATISFIABLE    = 416;
    const HTTP_EXPECTATION_FAILED       = 417;
    const HTTP_I_AM_A_TEAPOT            = 418; // RFC2324
    const HTTP_MISDIRECTED_REQUEST      = 421; // RFC7540
    const HTTP_UNPROCESSABLE_ENTITY     = 422; // RFC4918
    const HTTP_LOCKED                   = 423; // RFC4918
    const HTTP_FAILED_DEPENDENCY        = 424; // RFC4918
    const HTTP_UPGRADE_REQUIRED         = 426; // RFC2817
    const HTTP_PRECONDITION_REQUIRED    = 428; // RFC6585
    const HTTP_TOO_MANY_REQUESTS        = 429; // RFC6585
    const HTTP_HEADER_FIELDS_TOO_LARGE  = 431; // RFC6585
    const HTTP_UNAVAILABLE              = 451; // RFC7725

    //= 5xx Server Error
    const HTTP_INTERNAL_SERVER_ERROR    = 500;
    const HTTP_NOT_IMPLEMENTED          = 501;
    const HTTP_BAD_GATEWAY              = 502;
    const HTTP_SERVICE_UNAVAILABLE      = 503;
    const HTTP_GATEWAY_TIMEOUT          = 504;
    const HTTP_VERSION_NOT_SUPPORTED    = 505;
    const HTTP_VARIANT_ALSO_NEGOTIATES  = 506; // RFC2295
    const HTTP_INSUFFICIENT_STORAGE     = 507; // RFC4918
    const HTTP_LOOP_DETECTED            = 508; // RFC5842
    const HTTP_NOT_EXTENDED             = 510; // RFC2774
    const HTTP_NETWORK_AUTH_REQUIRED    = 511; // RFC6585

    /**
     * Clears the response.
     *
     * @return $this
     */
    public function clear();

    /**
     * Set the output.
     *
     * @param string $content The response content
     * @param int $status The response status code
     * @param array $headers An array of response headers
     *
     * @return $this
     */
    public function output($content, $status = Response::HTTP_OK, $headers = []);

    /**
     * Render the output by the given view.
     *
     * @param string $name Name of the view
     * @param array $variables
     * @param int $status The response status code
     * @param array $headers An array of response headers
     * @return $this
     */
    public function view($name, array $variables = [], $status = Response::HTTP_OK, $headers = []);

    /**
     * Get a JSON response.
     *
     * @param mixed $data
     * @param int $status The response status code
     * @param array $headers An array of response headers
     * @param int $options Bitmask consisting. The behaviour of these constants is described on http://php.net/manual/en/function.json-encode.php.
     * @return $this
     */
    public function json($data = [], $status = 200, array $headers = [], $options = 0);

    /**
     * Get a file download response.
     *
     * @param string $file The path to the file.
     * @param string $name The file name that is seen by the user downloading.
     * @param array $headers An array of response headers.
     * @return $this
     */
    public function download($file, $name = null, array $headers = []);

    /**
     * Get the raw contents of a binary file.
     *
     * It may be used to display a file, such as an image or PDF, directly in the user's browser instead of initiating
     * a download.
     *
     * @param string $file The path to the file.
     * @param array $headers An array of response headers.
     * @return $this
     */
    public function file($file, array $headers = []);

    /**
     * Get a redirect response to the given URL.
     *
     * @param string $url
     * @param int $status 301: permanently, 302: temporarily (default), 303: other
     * @param array $headers An array of response headers
     * @return $this
     */
    public function redirect($url, $status = Response::HTTP_FOUND, $headers = []);

    /**
     * Sets the HTTP status code.
     *
     * @param int $code HTTP status code.
     * @return $this
     */
    public function status($code);

    /**
     * Gets the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode();

    /**
     * Gets the HTTP status text.
     *
     * @return string
     */
    public function getStatusText();

    /**
     * Adds a header to the response.
     *
     * @param string|array $name Header name or array of names and values
     * @param string $value Header value
     * @return $this
     */
    public function header($name, $value = null);

    /**
     * Returns the header from the response.
     *
     * @param string|null $name Header name
     * @return array|string
     */
    public function getHeader($name = null);

    /**
     * Returns the content from the response.
     *
     * @return string
     */
    public function getContent();

    /**
     * Writes content to the response body.
     *
     * @param string $str Response content
     * @return $this
     */
    public function write($str);

    /**
     * Sets caching headers for the response.
     *
     * @param int|string $expires Expiration time
     * @return $this
     */
    public function cache($expires);

    /**
     * Sends a HTTP response.
     */
    public function send();

    /**
     * Flash a piece of data to the session.
     *
     * @param string|null $key Key using "dot" notation.
     * @param mixed $value
     * @return $this
     */
    public function withFlash($key, $value);

    /**
     * Flash an array of input to the session.
     *
     * Omit the argument to flash all of the current input.
     *
     * @param array $input
     * @return $this
     */
    public function withInput(array $input = null);

    /**
     * Flash a message to the session.
     *
     * @param string $message
     * @return $this
     */
    public function withMessage($message);

    /**
     * Flash a list of error messages to the session.
     *
     * @param array $messages
     * @return $this
     */
    public function withErrors(array $messages);

    /**
     * Flash an error message to the session.
     *
     * @param string $message
     * @param string|null $key
     * @return $this
     */
    public function withError($message, $key = null);
}
