<?php

namespace Core\Services;

use Core\Services\Contracts\Response as ResponseContract;

/**
 * The Response class represents an HTTP response.
 *
 * The class based on Flight, an extensible micro-framework by Mike Cao <mike@mikecao.com> (http://flightphp.com/license MIT License)
 *
 * @see https://github.com/mikecao/flight/blob/master/flight/net/Response.php
 */
class Response implements ResponseContract
{
    /**
     * HTTP response body
     *
     * @var string
     */
    private $content;

    /**
     * HTTP status code
     *
     * @var int
     */
    private $status = ResponseContract::HTTP_OK;

    /**
     * HTTP headers
     *
     * @var array
     */
    private $headers = [];

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->status  = ResponseContract::HTTP_OK;
        $this->headers = [];
        $this->content = '';

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function output($content, $status = ResponseContract::HTTP_OK, $headers = [])
    {
        $this->content = $content;
        $this->status  = $status;
        if (!empty($headers)) {
            $this->header($headers);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function view($name, array $variables = [], $status = ResponseContract::HTTP_OK, $headers = [])
    {
        return $this->output(DI::getInstance()->get('view')->render($name, $variables), $status, $headers);
    }

    /**
     * @inheritdoc
     */
    public function redirect($url, $status = ResponseContract::HTTP_FOUND, $headers = [])
    {
        if (!empty($headers)) {
            $this->header($headers);
        }

        return $this->header('location', $url)->status($status);
    }

    /**
     * @inheritdoc
     */
    public function status($code)
    {
        $this->status = $code;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * @inheritdoc
     */
    public function getStatusText()
    {
        return http_status_text($this->status);
    }

    /**
     * @inheritdoc
     */
    public function header($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->headers[$k] = $v;
            }
        }
        else {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getHeader($name = null)
    {
        if ($name === null) {
            return $this->headers;
        }

        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    /**
     * @inheritdoc
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @inheritdoc
     */
    public function write($str)
    {
        $this->content .= $str;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function cache($expires)
    {
        if ($expires === false) {
            $this->headers['Expires'] = 'Mon, 26 Jul 1997 05:00:00 GMT';
            $this->headers['Cache-Control'] = [
                'no-store, no-cache, must-revalidate',
                'post-check=0, pre-check=0',
                'max-age=0'
            ];
            $this->headers['Pragma'] = 'no-cache';
        }
        else {
            $expires = is_int($expires) ? $expires : strtotime($expires);
            $this->headers['Expires'] = gmdate('D, d M Y H:i:s', $expires) . ' GMT';
            $this->headers['Cache-Control'] = 'max-age='.($expires - time());
            if (isset($this->headers['Pragma']) && $this->headers['Pragma'] == 'no-cache'){
                unset($this->headers['Pragma']);
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function send()
    {
//        if (ob_get_length() > 0) {
//            $echo = ob_get_clean();
//        }

        if (!headers_sent()) {
            $this->sendHeaders();
        }

//        if (isset($echo)) {
//            echo $echo;
//        }

        echo $this->content;
    }

    /**
     * Sends HTTP headers.
     *
     * @return $this
     */
    private function sendHeaders()
    {
        // Send status code header
        if (PHP_SAPI == 'cli') {
            header(
                sprintf(
                    'Status: %d %s',
                    $this->status,
                    $this->getStatusText()
                ),
                true
            );
        }
        else {
            // @codeCoverageIgnoreStart
            header(
                sprintf(
                    '%s %d %s',
                    (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1'),
                    $this->status,
                    $this->getStatusText()
                ),
                true,
                $this->status
            );
            // @codeCoverageIgnoreEnd
        }

        // Send other headers
        foreach ($this->headers as $field => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    header($field.': ' . $v, false);
                }
            }
            else {
                header($field.': ' . $value);
            }
        }

        // Send content length
        if (($length = strlen($this->content)) > 0) {
            header('Content-Length: ' . $length);
        }

        return $this;
    }
}