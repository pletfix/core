<?php

namespace Core\Services\Contracts;

//use Psr\Http\Message\ServerRequestInterface;

/**
 * The Request class represents an HTTP request.
 */
interface Request //extends ServerRequestInterface
{
    /**
     * Get the full URL for the request.
     *
     * URL structure: scheme://username:password@domain:port/basepath/path?query_string#fragment_id
     *
     * Example: http://localhost/myapp/public/path?a=4
     *
     * Notes:
     * - This function does not include username:password from a full URL or the fragment (hash).
     * - The host is lowercase as per RFC 952/2181.
     * - It will not show the default port 80 for HTTP and port 443 for HTTPS.
     * - The #fragment_id is not sent to the server by the client (browser) and will not be added to the full URL.
     *
     * @return string
     */
    public function fullUrl();

    /**
     * Get the URL for the request (without query string).
     *
     * Example: http://localhost/myapp/public/path
     *
     * @return string
     */
    public function url();

    /**
     * Get the root URL for the application.
     *
     * Example: http://localhost/myapp/public
     *
     * Notes:
     * - This function does not include username:password from a full URL or the fragment (hash).
     * - The host is lowercase as per RFC 952/2181.
     * - It will not show the default port 80 for HTTP and port 443 for HTTPS.
     *
     * @return string
     */
    public function baseUrl();

    /**
     * Get the canonical URL for the request.
     *
     * This URL is important for SEO (Search Engine Optimizing).
     *
     * Example: fullUrl = "http://example.com/path?a=3" --> canonicalUrl = "https://www.example.de/path"
     *
     * @return string
     */
    public function canonicalUrl();

    /**
     * Get the path for the request (without query string).
     *
     * Example: fullUrl = "http://localhost/myapp/public/test?a=4" --> path = "test"
     *
     * @return string
     */
    public function path();

    /**
     * Retrieve an input item from the request ($_GET and $_POST).
     *
     * @param string|null $key
     * @param string|null $default
     * @return string|array
     */
    public function input($key = null, $default = null);

    /**
     * Retrieve a cookie from the request.
     *
     * Todo: Cookie setzen mittels setcookie() in Klasse Response einbauen
     * Todo: Alternative: Klasse Cookie anlegen und die Funktion dahin verlagern
     *
     * @param string|null $key
     * @param string|null $default
     * @return string|array
     */
    public function cookie($key = null, $default = null);

    /**
     * Retrieve a file from the request.
     *
     * @param string|null $key
     * @param string|null $default
     * @return object|array|null
     */
    public function file($key = null, $default = null);

    /**
     * Gets the raw HTTP request body of the request.
     *
     * @return string
     */
    public function body();

    /**
     * Gets the request method.
     *
     * If the X-HTTP-Method-Override header is set, and if the method is a POST, then it is used to determine the
     * "real" intended HTTP method. The method is always an uppercase string.
     *
     * @return string (GET, HEAD, POST, PUT, PATCH or DELETE)
     */
    public function method();

    /**
     * Returns the client IP address.
     *
     * @return string
     */
    public function ip();

    /**
     * Checks whether the request is secure or not.
     *
     * @return bool
     */
    public function isSecure();

    /**
     * Determine if the request is the result of an AJAX call.
     *
     * @return bool
     */
    public function isAjax();

    /**
     * Determine if the request is sending JSON.
     *
     * @return bool
     */
    public function isJson();

    /**
     * Determine if the current request is asking for JSON in return.
     *
     * @return bool
     */
    public function wantsJson();
}
