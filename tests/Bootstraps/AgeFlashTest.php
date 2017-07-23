<?php

namespace Core\Tests\Bootstraps;

use Core\Bootstraps\AgeFlash;
use Core\Services\DI;
use Core\Services\Flash;
use Core\Testing\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class AgeFlashTest extends TestCase
{
    private static $origFlash;

    public static function setUpBeforeClass()
    {
        self::$origFlash = DI::getInstance()->get('flash');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('flash', self::$origFlash, true);
    }

    public function testBoot()
    {
        /** @var Flash|PHPUnit_Framework_MockObject_MockObject $flash */
        $flash = $this->getMockBuilder(Flash::class)->setMethods(['age'])->getMock();
        $flash->expects($this->once())->method('age')->willReturnSelf();
        DI::getInstance()->set('flash', $flash, true);
        $bootstrap = new AgeFlash;
        $bootstrap->boot();
    }
}