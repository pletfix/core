<?php

namespace Core\Tests\Services;

use Core\Services\Contracts\Request;
use Core\Services\DI;
use Core\Testing\TestCase;

class RequestTest extends TestCase
{
    /**
     * @var Request
     */
    private $r;

    protected function setUp()
    {
        $_SERVER['HTTP_HOST']    = 'myhost';
        $_SERVER['SERVER_PORT']  = 443;
        $_SERVER['HTTPS']        = 'on';
        $_SERVER['PHP_SELF']     = '/myapp/index.php';
        $_SERVER['REQUEST_URI']  = '/myapp/mypath?foo=bar';
        $_SERVER['QUERY_STRING'] = 'foo=bar';
        $_SERVER['SERVER_ADDR']  = '127.0.0.1';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        $_SERVER['HTTP_ACCEPT']  = 'text/html, */*; q=0.01';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['post1' => 'POST1', 'post2' => 'POST2'];
        $_GET  = ['foo' => 'bar'];

        $this->r = new \Core\Services\Request();
    }

    public function testFullUrl()
    {
        $this->assertSame('https://myhost/myapp/mypath?foo=bar', $this->r->fullUrl());
    }

    public function testFullUrlWithCustomPort()
    {
        $_SERVER['SERVER_PORT'] = 4430;
        $this->assertSame('https://myhost:4430/myapp/mypath?foo=bar', $this->r->fullUrl());
    }

    public function testUrl()
    {
        $this->assertSame('https://myhost/myapp/mypath', $this->r->url());
    }

    public function testBaseUrl()
    {
        $this->assertSame('https://myhost/myapp', $this->r->baseUrl());
    }

    public function testInvalidBaseUrl()
    {
        $_SERVER['HTTP_HOST'] = 'hack:pss@myhost';
        $this->expectException(\UnexpectedValueException::class);
        $this->r->baseUrl();
    }

    public function testCanonicalUrl()
    {
        DI::getInstance()->get('config')->set('app.url', 'http://mycanonical.com');
        $this->assertSame('http://mycanonical.com/mypath', $this->r->canonicalUrl());
    }

    public function testPath()
    {
        $this->assertSame('mypath', $this->r->path());
    }

    public function testFormInput()
    {
        $this->assertSame(['foo' => 'bar', 'post1' => 'POST1', 'post2' => 'POST2'], $this->r->input());
        $this->assertSame('bar', $this->r->input('foo'));
    }

    public function testJsonInput()
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $this->assertSame(['foo' => 'bar'], $this->r->input());
    }

    public function testCookie()
    {
        $_COOKIE = ['Steve Miller Band' => 'Fly Like an Eagle'];
        $this->assertSame(['Steve Miller Band' => 'Fly Like an Eagle'], $this->r->cookie());
        $this->assertSame('Fly Like an Eagle', $this->r->cookie('Steve Miller Band'));
        $this->assertSame('Bad Medicine', $this->r->cookie('Bon Jovi', 'Bad Medicine'));
    }

    public function testFile()
    {
        $_FILES = ['Queen' => 'Bohemian Rhapsody'];
        $this->assertSame(['Queen' => 'Bohemian Rhapsody'], $this->r->file());
        $this->assertSame('Bohemian Rhapsody', $this->r->file('Queen'));
        $this->assertSame('Bad Medicine', $this->r->file('Bon Jovi', 'Bad Medicine'));
    }

    public function testBody()
    {
        $this->assertSame('', $this->r->body());
    }

    public function testGetMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertSame('GET', $this->r->method());
    }

    public function testPostMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['_method'] = 'PUT';
        $_POST['_method'] = 'PATCH';
        $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'DELETE';
        $this->assertSame('DELETE', $this->r->method());

        unset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        $this->r = new \Core\Services\Request();
        $this->assertSame('PATCH', $this->r->method());

        unset($_POST['_method']);
        $this->r = new \Core\Services\Request();
        $this->assertSame('PUT', $this->r->method());

        unset($_GET['_method']);
        $this->r = new \Core\Services\Request();
        $this->assertSame('POST', $this->r->method());
    }

    public function testIp()
    {
        $this->assertSame('127.0.0.1', $this->r->ip());
    }

    public function testIsSecure()
    {
        unset($_SERVER['HTTPS']);
        $this->assertFalse($this->r->isSecure());
        $_SERVER['HTTPS'] = 'off';
        $this->assertFalse($this->r->isSecure());
        $_SERVER['HTTPS'] = '1';
        $this->assertTrue($this->r->isSecure());
        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue($this->r->isSecure());

    }

    public function testIsJson()
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $this->assertTrue($this->r->isJson());
        $_SERVER['CONTENT_TYPE'] = 'application+json';
        $this->assertTrue($this->r->isJson());
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        $this->assertFalse($this->r->isJson());
    }

    public function testWantsJson()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json, text/javascript, */*; q=0.01';
        $this->assertTrue($this->r->wantsJson());
        $_SERVER['HTTP_ACCEPT'] = 'application+json, text/javascript, */*; q=0.01';
        $this->assertTrue($this->r->wantsJson());
        $_SERVER['HTTP_ACCEPT'] = 'text/html, */*; q=0.01';
        $this->assertFalse($this->r->wantsJson());
    }
}
