<?php

namespace Core\Services;

use Core\Services\Contracts\Request as RequestContract;
use Core\Services\Contracts\UploadedFile;

/**
 * The Request class represents an HTTP request.
 *
 * Method baseUrl() is based on the post "Get the full URL in PHP" at stackoverflow.
 * method() and isAjax() are based on the getMethod() and the isXmlHttpRequest() methods by Symfony's Request Object.
 * header() is adapted from Symfony's ServerBag (http-foundation/ServerBag.php).
 *
 * @see http://stackoverflow.com/questions/6768793/get-the-full-url-in-php Post on stackoverflow
 * @see https://github.com/symfony/http-foundation/blob/3.2/Request.php Symfony's Request Object on GitHub
 */
class Request implements RequestContract
{
    /**
     * Root URL for the application.
     *
     * @var string
     */
    private $baseUrl;

    /**
     * Path for the request (without query string).
     *
     * @var string
     */
    private $path;

    /**
     * The input from the request ($_GET and $_POST).
     *
     * @var array
     */
    private $input;

    /**
     * Uploaded files from the request.
     *
     * @var UploadedFile[]
     */
    private $files;

//    /**
//     * HTTP header.
//     *
//     * The header is taken from $_SERVER.
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
        if ($this->baseUrl === null) {
            $scheme        = $this->isSecure() ? 'https' : 'http';
            $host          = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : (isset($_SERVER['SERVER_NAME']) ? strtolower($_SERVER['SERVER_NAME']) : $this->ip());
            $port          = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;
            $basePath      = dirname($_SERVER['PHP_SELF']); // PHP_SELF = /myapp/public/index.php
            $isDefaultPort = ($scheme == 'http' && $port == '80') || ($scheme == 'https' && $port == '443');

            // As the host can come from the user (HTTP_HOST and depending on the configuration, SERVER_NAME too can
            // come from the user) check that it does not contain forbidden characters (see RFC 952 and RFC 2181)
            // use preg_replace() instead of preg_match() to prevent DoS attacks with long host names.
            // (see Symfony\Component\HttpFoundation\Request, getHost())
            if ($host && '' !== preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $host)) {
                throw new \UnexpectedValueException(sprintf('Invalid Host "%s"', $host));
            }

            $this->baseUrl = $scheme . '://' . $host . (!$isDefaultPort ? ':' . $port : '') . rtrim($basePath, '/');
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
        if ($this->path === null && isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI']; // /myapp/public/path?a=4
            if (($pos = strpos($uri, '?')) !== false) {
                $uri = substr($uri, 0, $pos);
            }
            $basePath = dirname($_SERVER['PHP_SELF']); // PHP_SELF = /myapp/public/index.php
            $this->path = trim(substr($uri, strlen($basePath)), '/');
        }

        return $this->path;
    }

    /**
     * @inheritdoc
     */
    public function input($key = null, $default = null)
    {
        if ($this->input === null) {
            if ($this->isJson()) {
                $input = json_decode($this->body(), true) ?: [];
            }
            else {
                $input = $_POST;
            }
            $this->input = array_merge($_GET, $input);
        }

        if ($key === null) {
            return $this->input;
        }

        return isset($this->input[$key]) ? $this->input[$key] : $default;
    }

    /**
     * @inheritdoc
     */
    public function cookie($key = null, $default = null)
    {
        if ($key === null) {
            return $_COOKIE;
        }

        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
    }

    /**
     * @inheritdoc
     */
    public function file($key)
    {
        if (!isset($this->files)) {
            $this->files = [];
            foreach ($_FILES as $key => $file) {
                $this->files[$key] = $this->convertFile($file);
            }
        }

        if (strpos($key, '.') === false) {
            return isset($this->files[$key]) ? $this->files[$key] : null;
        }

        $subitems = $this->files;
        foreach (explode('.', $key) as $subkey) {
            if (!isset($subitems[$subkey])) {
                return null;
            }
            $subitems = $subitems[$subkey];
        }

        return $subitems;
    }

    /**
     * Convert PHP's $_FILES to UploadedFile instances.
     *
     * @param array $file
     * @return \Core\Services\UploadedFile|array
     */
    private function convertFile(array $file)
    {
        if (!isset($file['name']) || !isset($file['type']) || !isset($file['tmp_name']) || !isset($file['error']) || !isset($file['size'])) {
            return null;
        }

        if (is_array($file['error'])) {
            $result = [];
            foreach ($file['error'] as $key => $error) {
                $result[$key] = $this->convertFile([
                    'name'     => $file['name'][$key],
                    'type'     => $file['type'][$key],
                    'tmp_name' => $file['tmp_name'][$key],
                    'error'    => $file['error'][$key],
                    'size'     => $file['size'][$key],
                ]);
            }
        }
        else {
            $result = new \Core\Services\UploadedFile($file['tmp_name'], $file['name'], $file['error']);
        }

        return $result;
    }

//    /**
//     * Get a HTTP header item.
//     *
//     * @param string|null $key
//     * @param string|null $default
//     * @return string|array
//     */
//    public function header($key = null, $default = null) // evtl wieder raus nehmen, zu kompliziert
//    {
//        if ($this->headers === null) {
//            $headers = [];
//
//            $contentHeaders = ['CONTENT_LENGTH' => true, 'CONTENT_MD5' => true, 'CONTENT_TYPE' => true];
//            foreach ($_SERVER as $key => $value) {
//                if (0 === strpos($key, 'HTTP_')) {
//                    $headers[substr($key, 5)] = $value;
//                }
//                // CONTENT_* are not prefixed with HTTP_
//                elseif (isset($contentHeaders[$key])) {
//                    $headers[$key] = $value;
//                }
//            }
//
//            if (isset($_SERVER['PHP_AUTH_USER'])) {
//                $headers['PHP_AUTH_USER'] = $_SERVER['PHP_AUTH_USER'];
//                $headers['PHP_AUTH_PW']   = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
//            }
//            else {
//                /*
//                 * php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
//                 * For this workaround to work, add these lines to your .htaccess file:
//                 * RewriteCond %{HTTP:Authorization} ^(.+)$
//                 * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
//                 *
//                 * A sample .htaccess file:
//                 * RewriteEngine On
//                 * RewriteCond %{HTTP:Authorization} ^(.+)$
//                 * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
//                 * RewriteCond %{REQUEST_FILENAME} !-f
//                 * RewriteRule ^(.*)$ app.php [QSA,L]
//                 */
//
//                $authorizationHeader = null;
//                if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
//                    $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'];
//                }
//                else if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
//                    $authorizationHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
//                }
//
//                if (null !== $authorizationHeader) {
//                    if (0 === stripos($authorizationHeader, 'basic ')) {
//                        // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
//                        $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)), 2);
//                        if (count($exploded) == 2) {
//                            list($headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']) = $exploded;
//                        }
//                    }
//                    else if (empty($_SERVER['PHP_AUTH_DIGEST']) && (0 === stripos($authorizationHeader, 'digest '))) {
//                        // In some circumstances PHP_AUTH_DIGEST needs to be set
//                        $headers['PHP_AUTH_DIGEST'] = $authorizationHeader;
//                        $_SERVER['PHP_AUTH_DIGEST'] = $authorizationHeader;
//                    }
//                    else if (0 === stripos($authorizationHeader, 'bearer ')) {
//                        /*
//                         * XXX: Since there is no PHP_AUTH_BEARER in PHP predefined variables,
//                         *      I'll just set $headers['AUTHORIZATION'] here.
//                         *      http://php.net/manual/en/reserved.variables.server.php
//                         */
//                        $headers['AUTHORIZATION'] = $authorizationHeader;
//                    }
//                }
//            }
//
//            if (isset($headers['AUTHORIZATION'])) {
//                return $headers;
//            }
//
//            // PHP_AUTH_USER/PHP_AUTH_PW
//            if (isset($headers['PHP_AUTH_USER'])) {
//                $headers['AUTHORIZATION'] = 'Basic '.base64_encode($headers['PHP_AUTH_USER'].':'.$headers['PHP_AUTH_PW']);
//            }
//            elseif (isset($headers['PHP_AUTH_DIGEST'])) {
//                $headers['AUTHORIZATION'] = $headers['PHP_AUTH_DIGEST'];
//            }
//
//            $this->headers = $headers;
//        }
//
//        if ($key === null) {
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
        if ($this->body === null) {
            $this->body = file_get_contents('php://input') ?: '';
        }

        return $this->body;
    }

    /**
     * @inheritdoc
     */
    public function method()
    {
        if ($this->method === null) {
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
        return isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] != '::1' ? $_SERVER['SERVER_ADDR'] : '127.0.0.1';
    }

    /**
     * @inheritdoc
     */
    public function isSecure()
    {
        return !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
    }

    /**
     * @inheritdoc
     */
    public function isAjax()
    {
//        return isset($_SERVER['X-Requested-With']) && $_SERVER['X-Requested-With'] == 'XMLHttpRequest';
        return isset($_SERVER['X-REQUESTED-WITH']) && $_SERVER['X-REQUESTED-WITH'] == 'XMLHttpRequest';
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