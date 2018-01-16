<?php

namespace Core\Tests\Bootstraps;

use Core\Bootstraps\HandleExceptions;
use Core\Handlers\Contracts\ExceptionHandler as ExceptionHandlerContract;
use Core\Services\DI;
use Core\Testing\TestCase;
use ErrorException;
use Exception;

class HandleExceptionsTest extends TestCase
{
    private static $origHandler;

    public static function setUpBeforeClass()
    {
        self::$origHandler = DI::getInstance()->get('exception-handler');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('exception-handler', self::$origHandler, true);
    }

    public function testHandleException()
    {
        $e = new Exception('dummy');

        $handler = $this->getMockBuilder(ExceptionHandler::class)->setMethods(['handle'])->getMock();
        $handler->expects($this->once())->method('handle')->with($e);
        DI::getInstance()->set('exception-handler', $handler, true);

        $bootstrap = new HandleExceptions();
        $bootstrap->handleException($e);
    }

    public function testHandleError()
    {
        $bootstrap = new HandleExceptions();

        $this->expectException(ErrorException::class);
        $bootstrap->handleError(E_WARNING, 'dummy warning');
    }

    public function testHandleShutdown()
    {
        $handler = $this->getMockBuilder(ExceptionHandler::class)->setMethods(['handle'])->getMock();
        $handler->expects($this->never())->method('handle');
        DI::getInstance()->set('exception-handler', $handler, true);

        $bootstrap = new HandleExceptions();
        $bootstrap->handleShutdown();
    }

    public function testIsFatal()
    {
        $bootstrap = new HandleExceptions();
        $this->assertTrue($this->invokePrivateMethod($bootstrap, 'isFatal', [E_ERROR]));
        $this->assertTrue($this->invokePrivateMethod($bootstrap, 'isFatal', [E_CORE_ERROR]));
        $this->assertTrue($this->invokePrivateMethod($bootstrap, 'isFatal', [E_COMPILE_ERROR]));
        $this->assertTrue($this->invokePrivateMethod($bootstrap, 'isFatal', [E_PARSE]));
        $this->assertFalse($this->invokePrivateMethod($bootstrap, 'isFatal', [E_WARNING]));
        $this->assertFalse($this->invokePrivateMethod($bootstrap, 'isFatal', [E_NOTICE]));
    }
}

class ExceptionHandler implements ExceptionHandlerContract
{
    public function handle(Exception $e)
    {
    }
}