<?php

namespace Core\Tests\Services;

use Core\Commands\HelpCommand;
use Core\Services\CommandFactory;
use Core\Testing\TestCase;

class CommandFactoryTest extends TestCase
{
    /**
     * @var string
     */
    private $cache;

    protected function setUp()
    {
        $this->cache = storage_path('cache/~test/commandtest1.php');
    }

    protected function tearDown()
    {
        @unlink($this->cache);
        @rmdir(dirname($this->cache));
    }

    public function testCommand()
    {
        $cf = new CommandFactory($this->cache);
        $this->assertInstanceOf(HelpCommand::class, $cf->command([])); // invoke the Help command without arguments
        $this->assertInstanceOf(HelpCommand::class, $cf->command(['help'])); // invoke the Help command without arguments
        $this->assertInstanceOf(HelpCommand::class, $cf->command(['fldjksfökajsf'])); // command not exist, invoke Help command to finds an alternative of the command name
    }

    public function testCommandList()
    {
        // cache file not exist
        @unlink($this->cache);
        @rmdir(dirname($this->cache));
        $cf = new CommandFactory($this->cache);
        $list = $cf->commandList();
        $this->assertArrayHasKey('help', $list);
        $this->assertSame(['class' => HelpCommand::class, 'name' => 'help', 'description' => 'Display help for a command.' ], $list['help']);

        // cache not up to date
        @unlink($this->cache);
        @touch($this->cache);
        $list = $cf->commandList();
        $this->assertArrayHasKey('help', $list);
        $this->assertSame(['class' => HelpCommand::class, 'name' => 'help', 'description' => 'Display help for a command.' ], $list['help']);

        // load from cache
        $list = $cf->commandList();
        $this->assertArrayHasKey('help', $list);
        $this->assertSame(['class' => HelpCommand::class, 'name' => 'help', 'description' => 'Display help for a command.' ], $list['help']);
    }
}