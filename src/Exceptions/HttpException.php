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
    /**
     * HTTP response status code
     *
     * @var int
     */
    private $statusCode;

    /**
     * Response headers.
     *
     * @var array
     */
    private $headers;

    /**
     * Construct the exception.
     *
     * @param int $statusCode
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
}
