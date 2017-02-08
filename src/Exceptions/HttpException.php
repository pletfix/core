<?php

namespace Core\Exceptions;

use Core\Exceptions\Contracts\HttpException as HttpExceptionContract;

/**
 * HttpException.
 *
 * Based on Symphony's HttpException (see namespace Symfony\Component\HttpKernel\Exception)
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class HttpException extends \RuntimeException implements HttpExceptionContract
{
    private $statusCode;
    private $headers;

    /**
     * Construct the exception.
     * @param string $statusCode
     * @param string $message
     * @param \Exception $previous
     * @param array $headers
     * @param int $code [optional] The Exception code.
     */
    public function __construct($statusCode, $message = null, \Exception $previous = null, array $headers = [], $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @inheritdoc
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @inheritdoc
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Set response headers.
     *
     * @param array $headers Response headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }
}
