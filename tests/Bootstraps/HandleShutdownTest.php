<?php

namespace Core\Tests\Bootstraps;

use Core\Bootstraps\HandleShutdown;
use Core\Handlers\Contracts\Handler as HandlerContract;
use Core\Services\DI;
use Core\Testing\TestCase;

class HandleShutdownTest extends TestCase
{
    private static $origHandler;

    public static function setUpBeforeClass()
    {
        self::$origHandler = DI::getInstance()->get('shutdown-handler');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('shutdown-handler', self::$origHandler, true);
    }

    public function testHandle()
    {
        $handler = $this->getMockBuilder(DummyShutdownHandler::class)->setMethods(['handle'])->getMock();
        $handler->expects($this->once())->method('handle');
        DI::getInstance()->set('shutdown-handler', $handler, true);
        $bootstrap = new HandleShutdown();
        $bootstrap->handle();
    }
}

class DummyShutdownHandler implements HandlerContract
{
    public function handle()
    {
    }
}