<?php

namespace Core\Services;

use Core\Application;
use Core\Services\Contracts\HttpClient as HttpClientContract;
use RuntimeException;

class HttpClient implements HttpClientContract
{
    /**
     * Headers
     *
     * @var array
     */
    private $headers = [];

    /**
     * Base URL
     *
     * @var string|null
     */
    private $baseUrl;

    /**
     * Port
     *
     * @var int|null
     */
    private $port;

    /**
     * Authentication
     *
     * @var array|null
     */
    private $auth;

    /**
     * SSL verify
     *
     * @var bool
     */
    private $sslVerify = false;

    /**
     * HTTP proxy
     *
     * @var array|null
     */
    private $proxy;

    /**
     * Cookies
     *
     * @var string|null
     */
    private $cookies;

    /**
     * Information regarding a specific transfer.
     *
     * @var array
     */
    private $info = [];

    /**
     * String containing the last error for the current session.
     *
     * @var string|null
     */
    private $error;

    /**
     * Create a new  instance.
     */
    public function __construct()
    {
        // without curl, we can't do anything
        if (!extension_loaded('curl') || !function_exists('curl_init')) {
            throw new \Exception('cUrl is not enabled on this server.');
        }
    }

    /**
     * @inheritdoc
     */
    public function header($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->headers[$k] = $v;
            }
        }
        else {
            $this->headers[$key] = $value;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function jsonEncoded()
    {
        $this->headers['Content-Type'] = 'application/json';

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function urlEncoded()
    {
        $this->headers['Content-Type'] = 'application/x-www-form-urlencoded';

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function multipart()
    {
        $this->headers['Content-Type'] = 'multipart/form-data';

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function acceptJson()
    {
        $this->headers['Accept'] = 'application/json';

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function acceptXml()
    {
        $this->headers['Accept'] = 'application/xhtml+xml'; // application/xhtml+xml,application/xml // ,text/xml // todo was ist angebracht?

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function acceptTextHtml()
    {
        $this->headers['Accept'] = 'text/html';

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function bearerToken($token)
    {
        if ($token !== null) {
            $this->headers['Authorization'] = 'Bearer ' . $token;
        }
        else {
            unset($this->headers['Authorization']);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function userAgent($userAgent)
    {
        if ($userAgent !== null) {
            $this->headers['User-Agent'] = $userAgent;
        }
        else {
            unset($this->headers['User-Agent']);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function baseUrl($url)
    {
        $this->baseUrl = rtrim($url, '/');

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function port($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function auth($username, $password, $method = CURLAUTH_BASIC)
    {
        $this->auth = compact('username', 'password', 'method');

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function digestAuth($username, $password)
    {
        return $this->auth($username, $password, CURLAUTH_DIGEST);
    }

    /**
     * @inheritdoc
     */
    public function ntlmAuth($username, $password)
    {
        return $this->auth($username, $password, CURLAUTH_NTLM);
    }

    /**
     * @inheritdoc
     */
    public function sslVerify($enable = true)
    {
        $this->sslVerify = $enable;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function proxy($address, $username = null, $password = null, $authMethod = CURLAUTH_BASIC, $type = CURLPROXY_HTTP, $tunnel = false)
    {
        $this->proxy = compact('address', 'port', 'username', 'password', 'authMethod', 'type', 'tunnel');

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function cookies($cookies)
    {
        if (is_array($cookies)) {
            $tmp = [];
            foreach ($cookies as $key => $value) {
                $tmp = $key . '=' . $value;
            }
            $this->cookies = implode('; ', $tmp);
        }
        else {
            $this->cookies = $cookies;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function send($method, $url, $data = null, array $headers = []) // todo auch ermöglichen, dass $data auch text/html ist? Sonst als array definieren.
    {
        $curl = curl_init();

        // set the HTTP method

        $method = strtoupper($method);
        if ($method == 'GET') {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        }
        else if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
        }
        else if ($method == 'HEAD') {
            curl_setopt($curl, CURLOPT_NOBODY, true);
        }
        else {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        }

        // set the headers

        if (!empty($this->headers)) {
            $headers = array_merge($this->headers, $headers);
        }

        if ($method != 'HEAD') {
            if ($method != 'GET' && !isset($headers['Content-Type'])) { // todo klären, ob es auch für OPTIONS benötigt wird
                $headers['Content-Type'] = 'application/json';
            }
            if (!isset($headers['Accept'])) {
                $headers['Accept'] = 'application/json';
            }
            if (!isset($headers['Accept-Encoding'])) {
                $headers['Accept-Encoding'] = 'identity,deflate,gzip';
            }
        }

        if (!isset($headers['User-Agent'])) {
            $headers['User-Agent'] = $this->defaultUserAgent();
        }

        // insert the data

        if ($data !== null) {
            if ($method == 'GET' || $method == 'HEAD') { // todo klären, ob es auch für OPTIONS so gemacht wird. Doku anpassen.
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
            }
            else if (isset($headers['Content-Type']) && $headers['Content-Type'] == 'application/json') {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
            else if (isset($headers['Content-Type']) && $headers['Content-Type'] == 'application/x-www-form-urlencoded') {
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            }
            else { // $headers['Content-Type'] == 'multipart/form-data'
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }

        // set username and password

        if ($this->auth !== null) {
            curl_setopt_array($curl, [
                CURLOPT_USERPWD  => $this->auth['username'] . ':' . $this->auth['password'],
                CURLOPT_HTTPAUTH => $this->auth['method'],
            ]);
        }

        // set HTTP Proxy

        if ($this->proxy !== null) {
            curl_setopt_array($curl, [
                CURLOPT_PROXY           => $this->proxy['address'],
                CURLOPT_PROXYUSERPWD    => $this->proxy['username'] . ':' . $this->proxy['password'],
                CURLOPT_PROXYAUTH       => $this->proxy['authMethod'],
                CURLOPT_PROXYTYPE       => $this->proxy['type'],
                CURLOPT_HTTPPROXYTUNNEL => $this->proxy['tunnel'],
            ]);
        }
        else if (($proxy = env('HTTPS_PROXY', env('HTTP_PROXY'))) !== null) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy);
        }

        // set port

        if ($this->port !== null) {
            curl_setopt($curl, CURLOPT_PORT , $this->port);
        }

        // set cookies

        if ($this->cookies !== null) {
            curl_setopt($curl, CURLOPT_COOKIE , $this->cookies);
        }

        // set encoding

        if (isset($headers['Accept-Encoding'])) {
            curl_setopt($curl, CURLOPT_ENCODING, $headers['Accept-Encoding']);
        }

        // set other options

        curl_setopt_array($curl, [
            CURLOPT_URL            => (!empty($this->baseUrl) ? $this->baseUrl . '/' : '') . $url,
            CURLOPT_HTTPHEADER     => $this->convertHeaders($headers),
            CURLOPT_SSL_VERIFYPEER => $this->sslVerify,
            CURLOPT_REFERER        => request()->url(),
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR    => true,
        ]);

        // send request...

        $response    = curl_exec($curl);
        $this->error = $response === false ? curl_error($curl) : null;
        $this->info  = curl_getinfo($curl);
        curl_close($curl);

        if ($this->error !== null) {
            throw new RuntimeException('cURL request failed: ' . $this->error);
        }

        if ($method != 'HEAD') {
            if (isset($headers['Accept']) && $headers['Accept'] == 'application/json') {
                $response = json_decode($response);
            }
            else if (isset($headers['Accept']) && $headers['Accept'] == 'application/xhtml+xml') {
                $response = simplexml_load_string($response);
            }
            //else { // $headers['Accept'] == 'text/html'
            //}
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @inheritdoc
     */
    public function getLastError()
    {
        return $this->error;
    }

    /**
     * @inheritdoc
     */
    public function getStatusCode()
    {
        return isset($this->info['http_code']) ? $this->info['http_code'] : null;
    }

    /**
     * @inheritdoc
     */
    public function get($url, array $params = [])
    {
        return $this->send('GET', $url, $params);
    }

    /**
     * @inheritdoc
     */
    public function head($url, array $params = [])
    {
        return $this->send('HEAD', $url, $params);
    }

    /**
     * @inheritdoc
     */
    public function post($url, array $data = [])
    {
        return $this->send('POST', $url, $data);
    }

    /**
     * @inheritdoc
     */
    public function put($url, array $data = [])
    {
        return $this->send('PUT', $url, $data);
    }

    /**
     * @inheritdoc
     */
    public function patch($url, array $data = [])
    {
        return $this->send('PATCH', $url, $data);
    }

    /**
     * @inheritdoc
     */
    public function delete($url, array $data = [])
    {
        return $this->send('DELETE', $url, $data);
    }

    /**
     * @inheritdoc
     */
    public function options($url, array $params = [])
    {
        return $this->send('OPTIONS', $url, $params);
    }

    /**
     * @inheritdoc
     */
    public function submit($url, array $fields = [], array $files = [])
    {
        if (!empty($files)) {
            foreach ($files as $name => $file) {
                $fields[$name] = curl_file_create($file, mime_type($file));
            }
        }

        return $this->send('POST', $url, $fields, ['Content-Type' => 'multipart/form-data']);
    }

    /**
     * Convert headers from key/value pair to a index based array.
     *
     * @param array $headers
     * @return array
     */
    private function convertHeaders(array $headers)
    {
        $result = [];
        foreach ($headers as $key => $value) {
            $result[] = $key . ': ' . $value;
        }

        return $result;
    }

    /**
     * Get the default User-Agent
     *
     * @return string
     */
    private function defaultUserAgent()
    {
        static $defaultAgent;

        if ($defaultAgent === null) {
            $defaultAgent = sprintf('Pletfix/%s curl/%s PHP/%s', Application::VERSION, curl_version()['version'], PHP_VERSION);
        }

        return $defaultAgent;
    }
}