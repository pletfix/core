<?php

namespace Core\Services\Contracts;

/**
 * The Request class represents an HTTP request.
 */
interface Request
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
     * @param string|null $key
     * @param string|null $default
     * @return string|array
     */
    public function cookie($key = null, $default = null);

    /**
     * Get an uploaded file by given key.
     *
     * If multiple files have been uploaded under the key, an array of the uploaded files is returned.
     * If the key does not exist in $_FILES, null is returned.
     *
     * @param string $key The key of $_FILES, using "dot" notation.
     * @return UploadedFile|array|null
     */
    public function file($key);

//    /**
//     * Get a HTTP header item.
//     *
//     * @param string|null $key
//     * @param string|null $default
//     * @return string|array
//     */
//    public function header($key = null, $default = null);

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
     * @return string ('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE' or 'OPTIONS')
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
     * It works if your JavaScript library sets an X-Requested-With HTTP header.
     * It is known to work with common JavaScript frameworks:
     *
     * @link http://en.wikipedia.org/wiki/List_of_Ajax_frameworks#JavaScript
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
