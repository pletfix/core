<?php

namespace Core\Tests\Commands;

use Core\Commands\AssetCommand;
use Core\Services\AssetManager;
use Core\Services\Contracts\Command;
use Core\Services\DI;
use Core\Services\Stdio;
use Core\Testing\TestCase;

class AssetCommandTest extends TestCase
{
    private static $origManager;

    public static function setUpBeforeClass()
    {
        self::$origManager = DI::getInstance()->get('asset-manager');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('asset-manager', self::$origManager, true);
    }

    public function testPublish()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['line'])->getMock();
        $stdio->expects($this->once())->method('line')->with('Assets are successfully published.')->willReturnSelf();

        // mock asset_manager()->publish('foo.js', true)
        $manager = $this->getMockBuilder(AssetManager::class)->setMethods(['publish', 'remove'])->getMock();
        $manager->expects($this->once())->method('publish')->with('foo.js', true, null)->willReturnSelf();
        $manager->expects($this->never())->method('remove')->willReturnSelf();
        DI::getInstance()->set('asset-manager', $manager, true);

        $command = new AssetCommand(['foo.js'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_SUCCESS, $exitCode);
    }

    public function testPublishWithoutMinify()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['line'])->getMock();
        $stdio->expects($this->once())->method('line')->with('Assets are successfully published.')->willReturnSelf();

        // mock asset_manager()->publish('foo.js', false)
        $manager = $this->getMockBuilder(AssetManager::class)->setMethods(['publish', 'remove'])->getMock();
        $manager->expects($this->once())->method('publish')->with('foo.js', false, null)->willReturnSelf();
        $manager->expects($this->never())->method('remove')->willReturnSelf();
        DI::getInstance()->set('asset-manager', $manager, true);

        $command = new AssetCommand(['foo.js', '--no-minify'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_SUCCESS, $exitCode);
    }

    public function testPublishPlugin()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['line'])->getMock();
        $stdio->expects($this->once())->method('line')->with('Assets are successfully published.')->willReturnSelf();

        // mock asset_manager()->publish('foo.js', true, 'ldap')
        $manager = $this->getMockBuilder(AssetManager::class)->setMethods(['publish', 'remove'])->getMock();
        $manager->expects($this->once())->method('publish')->with('foo.js', true, 'ldap')->willReturnSelf();
        $manager->expects($this->never())->method('remove')->willReturnSelf();
        DI::getInstance()->set('asset-manager', $manager, true);

        $command = new AssetCommand(['foo.js', '--plugin=ldap'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_SUCCESS, $exitCode);
    }

    public function testRemove()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['line'])->getMock();
        $stdio->expects($this->once())->method('line')->with('Assets are successfully removed.')->willReturnSelf();

        // mock asset_manager()->remove('foo.js')
        $manager = $this->getMockBuilder(AssetManager::class)->setMethods(['publish', 'remove'])->getMock();
        $manager->expects($this->never())->method('publish')->willReturnSelf();
        $manager->expects($this->once())->method('remove')->with('foo.js', null)->willReturnSelf();
        DI::getInstance()->set('asset-manager', $manager, true);

        $command = new AssetCommand(['foo.js', '--remove'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_SUCCESS, $exitCode);
    }

    public function testRemovePlugin()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['line'])->getMock();
        $stdio->expects($this->once())->method('line')->with('Assets are successfully removed.')->willReturnSelf();

        // mock asset_manager()->remove('foo.js', 'ldap')
        $manager = $this->getMockBuilder(AssetManager::class)->setMethods(['publish', 'remove'])->getMock();
        $manager->expects($this->never())->method('publish')->willReturnSelf();
        $manager->expects($this->once())->method('remove')->with('foo.js', 'ldap')->willReturnSelf();
        DI::getInstance()->set('asset-manager', $manager, true);

        $command = new AssetCommand(['foo.js', '--plugin=ldap', '--remove'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_SUCCESS, $exitCode);
    }
}