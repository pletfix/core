<?php

namespace Core\Tests\Services;

use Core\Services\Contracts\Request;
use Core\Testing\TestCase;

class RequestTest extends TestCase
{
    /**
     * @var Request
     */
    private $r;

    protected function setUp()
    {
        $this->r = new \Core\Services\Request();
        di()->set('request', RequestFake::class, true);
    }

    public function testFullUrl()
    {
        $this->assertSame('', $this->r->fullUrl());
    }

    public function testUrl()
    {
//        $this->assertSame('', $this->r->url());
    }

    public function testBaseUrl()
    {
        $this->assertStringStartsWith('http', $this->r->baseUrl());
    }

    public function testCanonicalUrl()
    {
        // todo
    }

    public function testPath()
    {
        // todo
    }

    public function testInput()
    {
        // todo
    }

    public function testCookie()
    {
        // todo
    }

    public function testFile()
    {
        // todo
    }

    public function testBody()
    {
        // todo
    }

    public function testMethod()
    {
        // todo
    }

    public function testIp()
    {
        // todo
    }

    public function testIsSecure()
    {
        // todo
    }

    public function testIsJson()
    {
        // todo
    }

    public function testWantsJson()
    {
        // todo
    }
}

class RequestFake
{
    public function baseUrl()
    {
        return 'my_base_url';
    }
}