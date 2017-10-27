<?php

namespace Core\Services\Contracts;

interface HttpClient
{
    /**
     * Add a header to the response.
     *
     * @param string|array $key Header key or array of key and values
     * @param string $value Header value
     * @return $this
     */
    public function header($key, $value = null);

    /**
     * Encode the request data as JSON.
     *
     * This is the default option.
     *
     * @return $this
     */
    public function jsonEncoded();

    /**
     * Encode the request data as application/x-www-form-urlencoded.
     *
     * @return $this
     */
    public function urlEncoded();

    /**
     * Encode the request data as multipart/form-data.
     *
     * @return $this
     */
    public function multipart();

    /**
     * Accept json as response.
     *
     * This is the default option.
     *
     * @return $this
     */
    public function acceptJson();

    /**
     * Accept xml and xhtml as response.
     *
     * The HTTP response is converted to an object of class SimpleXMLElement.
     *
     * @see http://php.net/manual/en/class.simplexmlelement.php SimpleXMLElement
     *
     * @return $this
     */
    public function acceptXml();

    /**
     * Accept text/html as response.
     *
     * @return $this
     */
    public function acceptTextHtml();

    /**
     * Set the Bearer Token.
     *
     * @see https://tools.ietf.org/html/rfc6750 RFC 6750
     *
     * @param string $token
     * @return $this
     */
    public function bearerToken($token);

    /**
     * Set the User-Agent.
     *
     * The user agent identifies the application type, operating system, software vendor or software version.
     *
     * @param string $userAgent
     * @return $this
     */
    public function userAgent($userAgent);

    /**
     * Set the base URL.
     *
     * @param string $url
     * @return $this
     */
    public function baseUrl($url);

    /**
     * Set a port.
     *
     * @param int $port
     * @return $this
     */
    public function port($port);

    /**
     * Set credentials for authentication.
     *
     * The default authentication method is basic authentication.
     *
     * @param string $username
     * @param string $password
     * @param int $method
     *          The HTTP authentication method(s) to use:<br/>
     *          CURLAUTH_BASIC, CURLAUTH_DIGEST, CURLAUTH_GSSNEGOTIATE, CURLAUTH_NTLM, CURLAUTH_ANY and/or CURLAUTH_ANYSAFE<br/>
     *          The bitwise | operator can be used to combine more than one method.
     *          @see http://php.net/manual/en/function.curl-setopt.php
     * @return $this
     */
    public function auth($username, $password, $method = CURLAUTH_BASIC);

    /**
     * Set credentials for digest access authentication.
     *
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function digestAuth($username, $password);

    /**
     * Set credentials for NTLM access authentication.
     *
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function ntlmAuth($username, $password);

    /**
     * Enable SSL verify.
     *
     * @param bool $enable
     * @return $this
     */
    public function sslVerify($enable = true);

    /**
     * Set the HTTP proxy.
     *
     * If this method is not used, the environment variable HTTPS_PROXY and HTTP_PROXY is read to determine the
     * HTTP proxy address.
     *
     * @param string $address The HTTP proxy to tunnel requests through. You may set the port like this: addr:port
     * @param string|null $username A username to use for the connection to the proxy.
     * @param string|null $password A password to use for the connection to the proxy.
     * @param int $authMethod
     *          HTTP authentication method(s): CURLAUTH_BASIC and/or CURLAUTH_NTLM.<br/>
     *          The bitwise | operator can be used to combine more than one method.
     * @param int $type Either CURLPROXY_HTTP, CURLPROXY_SOCKS4, CURLPROXY_SOCKS5, CURLPROXY_SOCKS4A or CURLPROXY_SOCKS5_HOSTNAME.
     * @param bool $tunnel TRUE to tunnel through a given HTTP proxy.
     * @return $this
     */
    public function proxy($address, $username = null, $password = null, $authMethod = CURLAUTH_BASIC, $type = CURLPROXY_HTTP, $tunnel = false);

    /**
     * Set cookies to be used in the HTTP request.
     *
     * @param array|string $cookies Cookies as array (key/value pairs) or string (e.g. "fruit=apple; colour=red").
     * @return $this
     */
    public function cookies($cookies);

    /**
     * Send a HTTP request to a URL and return its response.
     *
     * @param string $method HTTP method (GET, HEAD, POST, PUT, PATCH, DELETE, OPTIONS)
     * @param string $url HTTP URL to connect to
     * @param array|null $data Data to send
     * @param array $headers Headers as key/value pairs.
     * @return mixed
     */
    public function send($method, $url, $data = [], array $headers = []);

    /**
     * Get information regarding a specific transfer
     *
     * @link http://php.net/manual/en/function.curl-getinfo.php
     * @return array
     */
    public function getInfo();

    /**
     * Return a string containing the last error for the current session
     *
     * @link http://php.net/manual/en/function.curl-error.php
     * @return string|null
     */
    public function getLastError();

    /**
     * Get the HTTP status code of the last response.
     *
     * @return int
     */
    public function getStatusCode();

    /**
     * Send a HTTP GET request to a URL and return its response.
     *
     * @param string $url Full address of the HTTP Server
     * @param array $params Parameters to send in the query string
     * @return mixed
     */
    public function get($url, array $params = []);

    /**
     * Send a HTTP HEAD request to a URL and return its response.
     *
     * @param string $url Full address of the HTTP Server
     * @param array $params Parameters to send in the query string
     * @return mixed
     */
    public function head($url, array $params = []);

    /**
     * Send a HTTP POST request to a URL and return its response.
     *
     * @param string $url Full address of the HTTP Server
     * @param array $data Data to send
     * @return mixed
     */
    public function post($url, array $data = []);

    /**
     * Send a HTTP PUT request to a URL and return its response.
     *
     * @param string $url Full address of the HTTP Server
     * @param array $data Data to send
     * @return mixed
     */
    public function put($url, array $data = []);

    /**
     * Send a HTTP PATCH request to a URL and return its response.
     *
     * @param string $url Full address of the HTTP Server
     * @param array $data Data to send
     * @return mixed
     */
    public function patch($url, array $data = []);

    /**
     * Send a HTTP DELETE request to a URL and return its response.
     *
     * @param string $url Full address of the HTTP Server
     * @param array $data Data to send
     * @return mixed
     */
    public function delete($url, array $data = []);

    /**
     * Send a HTTP OPTIONS request to a URL and return its response.
     *
     * @param string $url Full address of the HTTP Server
     * @param array $params Parameters to send in the query string
     * @return mixed
     */
    public function options($url, array $params = []);

    /**
     * Submit multipart/form-data to a URL and return its response.
     *
     * @see https://www.w3.org/TR/html401/interact/forms.html#h-17.13.4 multipart/form-data
     *
     * @param string $url Full address of the HTTP Server
     * @param array $fields Fields of the form (as an array with the field name as key and field data as value).
     * @param array $files Attached files to upload (as an array with the field name as key and filename as value).
     * @return mixed
     */
    public function submit($url, array $fields = [], array $files = []);
}