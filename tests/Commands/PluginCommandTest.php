<?php

namespace Core\Tests\Commands;

use Core\Commands\PluginCommand;
use Core\Services\Contracts\Command;
use Core\Services\PluginManager;
use Core\Services\DI;
use Core\Services\Stdio;
use Core\Testing\TestCase;

class PluginCommandTest extends TestCase
{

    private static $origManager;

    public static function setUpBeforeClass()
    {
        //self::$origManager = DI::getInstance()->definition('plugin-manager'); // todo definition() hinzufügen
        self::$origManager = PluginManager::class;
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('plugin-manager', self::$origManager, false);
    }

    public function testRegister()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['line'])->getMock();
        $stdio->expects($this->once())->method('line')->with('Plugin successfully registered.')->willReturnSelf();

        // mock plugin_manager($package)->register();
        $manager = $this->getMockBuilder(PluginManager::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs(['pletfix/ldap'])
            ->setMethods(['register', 'update', 'unregister'])
            ->getMock();
        $manager->expects($this->once())->method('register')->with()->willReturnSelf();
        $manager->expects($this->never())->method('update')->willReturnSelf();
        $manager->expects($this->never())->method('unregister')->willReturnSelf();
        DI::getInstance()->set('plugin-manager', $manager, true);

        $command = new PluginCommand(['pletfix/ldap'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_SUCCESS, $exitCode);
    }

    public function testUpdate()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['line'])->getMock();
        $stdio->expects($this->once())->method('line')->with('Plugin successfully updated.')->willReturnSelf();

        // mock plugin_manager($package)->register();
        $manager = $this->getMockBuilder(PluginManager::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs(['pletfix/ldap'])
            ->setMethods(['register', 'update', 'unregister'])
            ->getMock();
        $manager->expects($this->never())->method('register')->willReturnSelf();
        $manager->expects($this->once())->method('update')->with()->willReturnSelf();
        $manager->expects($this->never())->method('unregister')->willReturnSelf();
        DI::getInstance()->set('plugin-manager', $manager, true);

        $command = new PluginCommand(['pletfix/ldap', '--update'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_SUCCESS, $exitCode);
    }

    public function testUnregister()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['line'])->getMock();
        $stdio->expects($this->once())->method('line')->with('Plugin successfully unregistered.')->willReturnSelf();

        // mock plugin_manager($package)->register();
        $manager = $this->getMockBuilder(PluginManager::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs(['pletfix/ldap'])
            ->setMethods(['register', 'update', 'unregister'])
            ->getMock();
        $manager->expects($this->never())->method('register')->willReturnSelf();
        $manager->expects($this->never())->method('update')->willReturnSelf();
        $manager->expects($this->once())->method('unregister')->with()->willReturnSelf();
        DI::getInstance()->set('plugin-manager', $manager, true);

        $command = new PluginCommand(['pletfix/ldap', '--remove'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_SUCCESS, $exitCode);
    }

    public function testPrintHelp()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['notice', 'write'])->getMock();
        $stdio->expects($this->any())->method('notice')->willReturnSelf();
        $stdio->expects($this->any())->method('write')->willReturnSelf();

        $command = new PluginCommand(null, $stdio);
        $command->printHelp(__DIR__ . '/../_data/plugin_manifest/packages.php');
    }
}