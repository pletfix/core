<?php

namespace Core\Tests\Services;

use Core\Services\Contracts\Request;
use Core\Services\DI;
use Core\Services\UploadedFile;
use Core\Testing\TestCase;

class RequestTest extends TestCase
{
    /**
     * @var Request
     */
    private $request;

    protected function setUp()
    {
        $_SERVER['HTTP_HOST']    = 'myhost';
        $_SERVER['SERVER_PORT']  = 443;
        $_SERVER['HTTPS']        = 'on';
        $_SERVER['SCRIPT_NAME']  = '/myapp/public/index.php';
        $_SERVER['REQUEST_URI']  = '/myapp/public/mypath?foo=bar';
        $_SERVER['QUERY_STRING'] = 'foo=bar';
        $_SERVER['SERVER_ADDR']  = '127.0.0.1';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        $_SERVER['HTTP_ACCEPT']  = 'text/html, */*; q=0.01';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['post1' => 'POST1', 'post2' => 'POST2'];
        $_GET  = ['foo' => 'bar'];

        $this->request = new \Core\Services\Request();
    }

    public function testFullUrl()
    {
        $this->assertSame('https://myhost/myapp/public/mypath?foo=bar', $this->request->fullUrl());
    }

    public function testFullUrlWithCustomPort()
    {
        $_SERVER['SERVER_PORT'] = 4430;
        $this->assertSame('https://myhost:4430/myapp/public/mypath?foo=bar', $this->request->fullUrl());
    }

    public function testUrl()
    {
        $this->assertSame('https://myhost/myapp/public/mypath', $this->request->url());
    }

    public function testBaseUrl()
    {
        $this->assertSame('https://myhost/myapp/public', $this->request->baseUrl());
    }

    public function testInvalidBaseUrl()
    {
        $_SERVER['HTTP_HOST'] = 'hack:pss@myhost';
        $this->expectException(\UnexpectedValueException::class);
        $this->request->baseUrl();
    }

    public function testPath()
    {
        $this->assertSame('mypath', $this->request->path());
    }

    public function testSegment()
    {
        $this->assertSame('mypath', $this->request->segment(0));
        $this->assertSame('def', $this->request->segment(1, 'def'));
    }

    public function testFormInput()
    {
        $this->assertSame(['foo' => 'bar', 'post1' => 'POST1', 'post2' => 'POST2'], $this->request->input());
        $this->assertSame('bar', $this->request->input('foo'));
    }

    public function testJsonInput()
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $this->assertSame(['foo' => 'bar'], $this->request->input());
    }

    public function testCookie()
    {
        $_COOKIE = ['Steve Miller Band' => 'Fly Like an Eagle'];
        $this->assertSame(['Steve Miller Band' => 'Fly Like an Eagle'], $this->request->cookie());
        $this->assertSame('Fly Like an Eagle', $this->request->cookie('Steve Miller Band'));
        $this->assertSame('Bad Medicine', $this->request->cookie('Bon Jovi', 'Bad Medicine'));
    }

    public function testUploadedInvalidFiles()
    {
        // <input name="image" type="file"/>
        $_FILES = [
            'image' => [ // type and size does not specify
                'name'     => 'avatar.png',
                'tmp_name' => 'temp/xyz',
                'error'    => 0,
            ],
        ];

        $this->assertNull($this->request->file('image'));
    }

    public function testUploadedFiles()
    {
        // <input name="image" type="file"/>
        $_FILES = [
            'image' => [
                'name'     => 'avatar.png',
                'type'     => 'image/png',
                'tmp_name' => 'temp/xyz',
                'error'    => 0,
                'size'     => 885,
            ],
        ];

        /** @var UploadedFile[] $files */
        $file = $this->request->file('image');
        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertSame('avatar.png', $file->originalName());
        $this->assertSame(0, $file->errorCode());
        $this->assertSame('temp/xyz', $this->getPrivateProperty($file, 'path'));
    }

    public function testNestedUploadedFiles()
    {
        // <input name="image[a][b]" type="file"/>
        $_FILES = [
            'image' => [
                'name'     => ['a' => ['b' => 'avatar.png']],
                'type'     => ['a' => ['b' => 'image/png']],
                'tmp_name' => ['a' => ['b' => 'temp/xyz']],
                'error'    => ['a' => ['b' => 0]],
                'size'     => ['a' => ['b' => 885]],
            ],
        ];

        $arr = $this->request->file('image');
        $this->assertCount(1, $arr);
        $this->assertArrayHasKey('a', $arr);
        $this->assertArrayHasKey('b', $arr['a']);
        $this->assertInstanceOf(UploadedFile::class, $arr['a']['b']);

        // "dot"-notation
        /** @var UploadedFile $file */
        $file = $this->request->file('image.a.b');
        $this->assertSame($arr['a']['b'],  $file);
        $this->assertSame('avatar.png', $file->originalName());
        $this->assertSame(0, $file->errorCode());
        $this->assertSame('temp/xyz', $this->getPrivateProperty($file, 'path'));

        // key does not exist
        $this->assertNull($this->request->file('image.wrong'));
    }

    public function tesMultipleUploadedFiles()
    {
        // <input name="images[]" type="file"/>
        // <input name="images[]" type="file"/>
        $_FILES = [
            'images' => [
                'name'     => ['avatar.png', 'avatar.gif'],
                'type'     => ['image/png',  'image/gif'],
                'tmp_name' => ['temp/xyz',   'temp.abc'],
                'error'    => [0,            0],
                'size'     => [885,          1553],
            ],
        ];

        /** @var UploadedFile[] $files */
        $files = $this->request->file('image');
        $this->assertCount(2, $files);
        $file = $files[0];
        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertSame('avatar.png', $file->originalName());
        $this->assertSame(0, $file->errorCode());
        $this->assertSame('temp/xyz', $this->getPrivateProperty($file, 'path'));
        $file = $files[1];
        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertSame('avatar.gif', $file->originalName());
        $this->assertSame(0, $file->errorCode());
        $this->assertSame('temp/abc', $this->getPrivateProperty($file, 'path'));
    }

    public function testBody()
    {
        $this->assertSame('', $this->request->body());
    }

    public function testGetMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertSame('GET', $this->request->method());
    }

    public function testPostMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['_method'] = 'PUT';
        $_POST['_method'] = 'PATCH';
        $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'DELETE';
        $this->assertSame('DELETE', $this->request->method());

        unset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        $this->request = new \Core\Services\Request();
        $this->assertSame('PATCH', $this->request->method());

        unset($_POST['_method']);
        $this->request = new \Core\Services\Request();
        $this->assertSame('PUT', $this->request->method());

        unset($_GET['_method']);
        $this->request = new \Core\Services\Request();
        $this->assertSame('POST', $this->request->method());
    }

    public function testIp()
    {
        $this->assertSame('127.0.0.1', $this->request->ip());
    }

    public function testIsSecure()
    {
        unset($_SERVER['HTTPS']);
        $this->assertFalse($this->request->isSecure());
        $_SERVER['HTTPS'] = 'off';
        $this->assertFalse($this->request->isSecure());
        $_SERVER['HTTPS'] = '1';
        $this->assertTrue($this->request->isSecure());
        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue($this->request->isSecure());
    }

    public function testIsAjax()
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->assertTrue($this->request->isAjax());
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        $this->assertFalse($this->request->isAjax());
    }

    public function testIsJson()
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $this->assertTrue($this->request->isJson());
        $_SERVER['CONTENT_TYPE'] = 'application+json';
        $this->assertTrue($this->request->isJson());
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        $this->assertFalse($this->request->isJson());
    }

    public function testWantsJson()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json, text/javascript, */*; q=0.01';
        $this->assertTrue($this->request->wantsJson());
        $_SERVER['HTTP_ACCEPT'] = 'application+json, text/javascript, */*; q=0.01';
        $this->assertTrue($this->request->wantsJson());
        $_SERVER['HTTP_ACCEPT'] = 'text/html, */*; q=0.01';
        $this->assertFalse($this->request->wantsJson());
    }
}
