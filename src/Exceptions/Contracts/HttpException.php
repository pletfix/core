<?php

namespace Core\Exceptions\Contracts;

/**
 * Interface for HTTP error exceptions.
 *
 * Based on Symphony's HttpException (see namespace Symfony\Component\HttpKernel\Exception)
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
interface HttpException
{
    /**
     * Returns the status code.
     *
     * @return int An HTTP response status code
     */
    public function getStatusCode();

    /**
     * Returns response headers.
     *
     * @return array Response headers
     */
    public function getHeaders();
}
