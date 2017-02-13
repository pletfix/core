<?php

namespace Core\Services;

use Core\Services\Contracts\Request as RequestContract;

/**
 * The Request class represents an HTTP request.
 */
class Request implements RequestContract
{
//    /**
//     * Full URL for the request.
//     *
//     * @var string
//     */
//    private $fullUrl;
//
//    /**
//     * URL for the request (without query string).
//     *
//     * @var string
//     */
//    private $url;

    /**
     * Root URL for the application.
     *
     * @var string
     */
    private $baseUrl;

//    /**
//     * Canonical URL for the request.
//     *
//     * @var string
//     */
//    private $canonicalUrl;

    /**
     * Path for the request (without query string).
     *
     * @var string
     */
    private $path;

//    /*
//     * Query Parameter
//     *
//     * @var array
//     */
//    private $query;

    /**
     * The input from the request ($_GET and $_POST).
     *
     * @var array
     */
    private $input;

//    /*
//     * Cookies from the request
//     *
//     * @var array
//     */
//    private $cookies;

//    /**
//     * Uploaded files from the request.
//     *
//     * @var array
//     */
//    private $files;

//    /**
//     * HTTP header.
//     *
//     * @var array
//     */
//    private $headers;

    /**
     * The body of the request.
     *
     * @var string;
     */
    private $body;

    /**
     * Request method (GET, HEAD, POST, PUT, PATCH, DELETE).
     *
     * @var string
     */
    private $method;

//    /**
//     * Create a new Request instance.
//     */
//    public function __construct()
//    {
//    }

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
    public function fullUrl()
    {
        return $this->url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    }

    /**
     * Get the URL for the request (without query string) .
     *
     * Example: http://localhost/myapp/public/path
     *
     * @return string
     */
    public function url()
    {
        $path = $this->path();

        return $this->baseUrl() . (!empty($path) ? '/' . $path : '');
    }

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
     * @link http://stackoverflow.com/questions/6768793/get-the-full-url-in-php

     * @return string
     */
    public function baseUrl()
    {
        if (is_null($this->baseUrl)) {
            $scheme        = $this->isSecure() ? 'https' : 'http';
            $host          = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : (isset($_SERVER['SERVER_NAME']) ? strtolower($_SERVER['SERVER_NAME']) : $this->ip());
            $port          = $_SERVER['SERVER_PORT'];
            $basePath      = dirname($_SERVER['PHP_SELF']); // PHP_SELF = /myapp/public/index.php
            $isDefaultPort = ($scheme == 'http' && $port == '80') || ($scheme == 'https' && $port == '443');

            // As the host can come from the user (HTTP_HOST and depending on the configuration, SERVER_NAME too can
            // come from the user) check that it does not contain forbidden characters (see RFC 952 and RFC 2181)
            // use preg_replace() instead of preg_match() to prevent DoS attacks with long host names.
            // (see Symfony\Component\HttpFoundation\Request, getHost())
            if ($host && '' !== preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $host)) {
                throw new \UnexpectedValueException(sprintf('Invalid Host "%s"', $host));
            }

            $this->baseUrl = $scheme . '://' . $host . (!$isDefaultPort ? ':' . $port : '') . $basePath;
        }

        return $this->baseUrl;
    }

    /**
     * Get the canonical URL for the request.
     *
     * This URL is importend for SEO (Search Engine Optimizing).
     *
     * Example: fullUrl = "http://example.com/path?a=3" --> canonicalUrl = "https://www.example.de/path"
     *
     * @return string
     */
    public function canonicalUrl()
    {
        return config('app.url') . '/' . $this->path();
    }

    /**
     * Get the path for the request (without query string).
     *
     * Example: fullUrl = "http://localhost/myapp/public/test?a=4" --> path = "test"
     *
     * @return string
     */
    public function path()
    {
        if (is_null($this->path)) {
            $uri = $_SERVER['REQUEST_URI'];            // /myapp/public/path?a=4
            if (false !== $pos = strpos($uri, '?')) {
                $uri = substr($uri, 0, $pos);
            }
            $basePath = dirname($_SERVER['PHP_SELF']); // PHP_SELF = /myapp/public/index.php
            $this->path = trim(substr($uri, strlen($basePath)), '/');
        }

        return $this->path;
    }

//    /**
//     * Retrieve a query string item from the request.
//     *
//     * @param string|null $key
//     * @param string|null $default
//     * @return string|array
//     */
//    public function query($key = null, $default = null)
//    {
//        if (is_null($this->query)) {
//            parse_str($_SERVER['QUERY_STRING'], $output);
//            $this->query = $output;
//        }
//
//        if (is_null($key)) {
//            return $this->query;
//        }
//
//        return isset($this->query[$key]) ? $this->query[$key] : $default;
//    }

    /**
     * Retrieve an input item from the request ($_GET and $_POST).
     *
     * @param string|null $key
     * @param string|null $default
     * @return string|array
     */
    public function input($key = null, $default = null)
    {
        if (is_null($this->input)) {
            if ($this->isJson()) {
                $body = $this->body();
                $input = !empty($body) ? json_decode($body, true) : [];
            }
            else {
                $input = $_POST;
            }
            $this->input = array_merge($input, $_GET); // todo Prio Get/Post richtig? Cookie nicht?
        }

        if (is_null($key)) {
            return $this->input;
        }

        return isset($this->input[$key]) ? $this->input[$key] : $default;
    }

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
    public function cookie($key = null, $default = null)
    {
        if (is_null($key)) {
            return $_COOKIE;
        }

        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
    }

    /**
     * Retrieve a file from the request.
     *
     * @param string|null $key
     * @param string|null $default
     * @return object|array|null
     */
    public function file($key = null, $default = null)
    {
        if (is_null($key)) {
            return $_FILES;
        }

        return isset($_FILES[$key]) ? $_FILES[$key] : $default;
    }

//    /**
//     * Get a HTTP header item.
//     *
//     * @param string|null $key
//     * @param string|null $default
//     * @return string|array
//     */
//    public function header($key = null, $default = null)
//    {
//        if (is_null($this->headers)) {
//            $this->headers = function_exists('getallheaders') ? getallheaders() : [];
//        }
//
//        if (is_null($key)) {
//            return $this->headers;
//        }
//
//        return isset($this->headers[$key]) ? $this->headers[$key] : $default;
//    }

    /**
     * Gets the raw HTTP request body of the request.
     *
     * @return string
     */
    public function body()
    {
        if (is_null($this->body)) {
            $this->body = file_get_contents('php://input') ?: '';
        }

        return $this->body;
    }

    /**
     * Gets the request method.
     *
     * This code based on Symfony\Component\HttpFoundation\Request.
     *
     * If the X-HTTP-Method-Override header is set, and if the method is a POST,
     * then it is used to determine the "real" intended HTTP method.

     * The method is always an uppercased string.
     *
     * @return string (GET, HEAD, POST, PUT, PATCH or DELETE)
     */
    public function method()
    {
        if (is_null($this->method)) {
            $this->method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
            if ($this->method === 'POST') {
                if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                    $this->method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
                }
                else if (isset($_POST['_method'])) {
                    $this->method = strtoupper($_POST['_method']);
                }
                else if (isset($_GET['_method'])) {
                    $this->method = strtoupper($_GET['_method']);
                }
            }
        }

        return $this->method;
    }

    /**
     * Returns the client IP address.
     *
     * @return string
     */
    public function ip()
    {
        return $_SERVER['SERVER_ADDR'] != '::1' ? $_SERVER['SERVER_ADDR'] : '127.0.0.1';
    }

    /**
     * Checks whether the request is secure or not.
     *
     * @return bool
     */
    public function isSecure()
    {
        $https = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : null;

        return !empty($https) && strtolower($https) !== 'off';
    }

    /**
     * Determine if the request is the result of an AJAX call.
     *
     * @return bool
     */
    public function isAjax()
    {
        return (isset($_SERVER['X-Requested-With']) ? $_SERVER['X-Requested-With'] : null) == 'XMLHttpRequest';
    }

    /**
     * Determine if the request is sending JSON.
     *
     * @return bool
     */
    public function isJson()
    {
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

        return strpos($contentType, '/json') !== false || strpos($contentType, '+json') !== false;
    }

    /**
     * Determine if the current request is asking for JSON in return.
     *
     * @return bool
     */
    public function wantsJson()
    {
        $accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';

        return strpos($accept, '/json') !== false || strpos($accept, '+json') !== false;
    }
}