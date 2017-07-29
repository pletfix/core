<?php

namespace Core\Tests\Exceptions;

use Core\Exceptions\HttpException;
use Core\Services\Contracts\Response;
use Core\Testing\TestCase;

class HttpExceptionTest extends TestCase
{
    /**
     * @var HttpException
     */
    private $e;

    protected function setUp()
    {
        $this->e = new HttpException(Response::HTTP_NOT_FOUND, null, null, ['Pragma' => 'no-cache']);
    }

    public function testGetStatusCode()
    {
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->e->getStatusCode());
    }

    public function testGetHeaders()
    {
        $this->assertSame(['Pragma' => 'no-cache'], $this->e->getHeaders());
    }
}