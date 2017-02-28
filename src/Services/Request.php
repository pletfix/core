<?php

namespace Core\Services;

use Core\Services\Contracts\Request as RequestContract;

/**
 * The Request class represents an HTTP request.
 *
 * Function baseUrl() based on the post "Get the full URL in PHP" at stackoverflow.
 * Function method() based on Symfony's Request Object.
 *
 * @see http://stackoverflow.com/questions/6768793/get-the-full-url-in-php
 * @see https://github.com/symfony/http-foundation/blob/3.2/Request.php
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
     * @inheritdoc
     */
    public function fullUrl()
    {
        return $this->url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    }

    /**
     * @inheritdoc
     */
    public function url()
    {
        $path = $this->path();

        return $this->baseUrl() . (!empty($path) ? '/' . $path : '');
    }

    /**
     * @inheritdoc
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

            $this->baseUrl = $scheme . '://' . $host . (!$isDefaultPort ? ':' . $port : '') . rtrim($basePath, '/.');
        }

        return $this->baseUrl;
    }

    /**
     * @inheritdoc
     */
    public function canonicalUrl()
    {
        return config('app.url') . '/' . $this->path();
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
     */
    public function cookie($key = null, $default = null)
    {
        if (is_null($key)) {
            return $_COOKIE;
        }

        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function body()
    {
        if (is_null($this->body)) {
            $this->body = file_get_contents('php://input') ?: '';
        }

        return $this->body;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function ip()
    {
        return $_SERVER['SERVER_ADDR'] != '::1' ? $_SERVER['SERVER_ADDR'] : '127.0.0.1';
    }

    /**
     * @inheritdoc
     */
    public function isSecure()
    {
        $https = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : null;

        return !empty($https) && strtolower($https) !== 'off';
    }

    /**
     * @inheritdoc
     */
    public function isAjax()
    {
        return (isset($_SERVER['X-Requested-With']) ? $_SERVER['X-Requested-With'] : null) == 'XMLHttpRequest';
    }

    /**
     * @inheritdoc
     */
    public function isJson()
    {
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

        return strpos($contentType, '/json') !== false || strpos($contentType, '+json') !== false;
    }

    /**
     * @inheritdoc
     */
    public function wantsJson()
    {
        $accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';

        return strpos($accept, '/json') !== false || strpos($accept, '+json') !== false;
    }
}