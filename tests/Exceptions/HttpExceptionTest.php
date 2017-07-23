<?php

namespace Core\Tests\Exceptions;

use Core\Exceptions\HttpException;
use Core\Testing\TestCase;

class HttpExceptionTest extends TestCase
{
    /**
     * @var HttpException
     */
    private $e;

    protected function setUp()
    {
        $this->e = new HttpException(HTTP_STATUS_NOT_FOUND, null, null, ['Pragma' => 'no-cache']);
    }

    public function testGetStatusCode()
    {
        $this->assertSame(HTTP_STATUS_NOT_FOUND, $this->e->getStatusCode());
    }

    public function testGetHeaders()
    {
        $this->assertSame(['Pragma' => 'no-cache'], $this->e->getHeaders());
    }
}