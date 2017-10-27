<?php

namespace Core\Tests\Testing;

use Core\Services\DI;
use Core\Services\PDOs\SQLite;
use Core\Services\Response;
use Core\Testing\TestCase;

class TestCaseTest extends TestCase
{
    private static $origResponse;
    private static $origDBFactory;

    public static function setUpBeforeClass()
    {
        self::$origResponse = DI::getInstance()->get('response');
        self::$origDBFactory = DI::getInstance()->get('database-factory');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('response', self::$origResponse, true);
        DI::getInstance()->set('database-factory', self::$origDBFactory, true);
    }

    public function testDefineMemoryAsDefaultDatabase()
    {
        $this->defineMemoryAsDefaultDatabase();
        $db = database();
        $this->assertInstanceOf(SQLite::class, $db);
        $this->assertSame(':memory:', $db->config('database'));
    }

    public function testAssertRedirectedTo()
    {
        // positive case
        $response = $this->getMockBuilder(Response::class)->setMethods(['getStatusCode', 'getHeader'])->getMock();
        $response->expects($this->any())->method('getStatusCode')->willReturn(302);
        $response->expects($this->any())->method('getHeader')->with('Location')->willReturn('https://example.com');
        DI::getInstance()->set('response', $response, true);
        $this->assertRedirectedTo('https://example.com');

        // negative case
        $response = $this->getMockBuilder(Response::class)->setMethods(['getStatusCode', 'getHeader'])->getMock();
        $response->expects($this->any())->method('getStatusCode')->willReturn(200);
        $response->expects($this->any())->method('getHeader')->with('Location')->willReturn(null);
        DI::getInstance()->set('response', $response, true);
        try {
            $this->assertRedirectedTo('https://example.com');
            $redirectTo = true;
        }
        catch(\PHPUnit_Framework_ExpectationFailedException $e) {
            $redirectTo = false;
        }
        $this->assertFalse($redirectTo, 'should not redirect');
    }

    public function testGetAndSetPrivateMethod()
    {
        $foo = new Foo();

        $value = $this->invokePrivateMethod($foo, 'privateMethod', [66]);
        $this->assertSame(66, $value);

        $value = $this->invokePrivateMethod($foo, 'protectedMethod', [77]);
        $this->assertSame(77, $value);
    }

    public function testGetAndSetPrivateProperty()
    {
        $foo = new Foo();

        $this->setPrivateProperty($foo, 'privateProperty', 88);
        $value = $this->getPrivateProperty($foo, 'privateProperty');
        $this->assertSame(88, $value);

        $this->setPrivateProperty($foo, 'protectedProperty', 99);
        $value = $this->getPrivateProperty($foo, 'protectedProperty');
        $this->assertSame(99, $value);
    }
}

class Foo
{
    private /** @noinspection PhpUnusedPrivateFieldInspection */ $privateProperty;

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function privateMethod($param)
    {
        return $param;
    }

    protected $protectedProperty;

    protected function protectedMethod($param)
    {
        return $param;
    }
}