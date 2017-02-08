<?php

namespace Core\Services;

use Core\Services\Contracts\Response as ResponseContract;

/**
 * The Response class represents an HTTP response.
 *
 * The class based on Flight: An extensible micro-framework.
 * Copyright (c) 2011, Mike Cao <mike@mikecao.com>, MIT, http://flightphp.com/license
 * https://github.com/mikecao/flight/blob/master/flight/net/Response.php
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
    private $status = 200;

    /**
     * HTTP headers
     *
     * @var array
     */
    private $headers = [];

//    /**
//     * Create a new Response instance.
//     */
//    public function __construct()
//    {
//    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->status  = 200;
        $this->headers = [];
        $this->content = '';

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function output($content, $status = 200, $headers = [])
    {
        $this->content = $content;
        $this->status  = $status;
        $this->headers = $headers;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function view($name, $variables = [], $status = 200, $headers = [])
    {
        return $this->output(DI::getInstance()->get('view')->render($name, $variables), $status, $headers);
    }

    /**
     * @inheritdoc
     */
    public function status($code = 200)
    {
//        if (!array_key_exists($code, static::$statusTexts)) {
//            throw new \Exception('Invalid status code.');
//        }
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
//        return isset(static::$statusTexts[$this->status]) ?  static::$statusTexts[$this->status] : '';
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
    public function getHeaders()
    {
        return $this->headers;
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
            $this->headers['Cache-Control'] = array(
                'no-store, no-cache, must-revalidate',
                'post-check=0, pre-check=0',
                'max-age=0'
            );
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