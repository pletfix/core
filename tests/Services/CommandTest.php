<?php

namespace Core\Tests\Services;

use Core\Exceptions\StopException;
use Core\Services\Command;
use Core\Services\Stdio;
use Core\Testing\TestCase;
use InvalidArgumentException;
use LogicException;
use PHPUnit_Framework_MockObject_MockObject;

class CommandTest extends TestCase
{
    /**
     * @var Command|PHPUnit_Framework_MockObject_MockObject|
     */
    private $cmd;

    public static function setUpBeforeClass()
    {
        @mkdir(storage_path('~test'));
    }

    public static function tearDownAfterClass()
    {
        @unlink(storage_path('~test/stdout.txt'));
        @rmdir(storage_path('~test'));
    }

    protected function setUp()
    {
        $this->cmd = $this->getMockBuilder(Command::class)
            ->disableOriginalConstructor()
            ->setMethods(['handle'])
            ->getMockForAbstractClass();

        $this->setPrivateProperty($this->cmd, 'name', 'test:foo');
        $this->setPrivateProperty($this->cmd, 'description', 'Just a test command.');
        $this->setPrivateProperty($this->cmd, 'arguments', [
            'stringArg1' => ['type' => 'string', 'default' => 'bar', 'description' => 'Test Argument'],
        ]);

        $this->setPrivateProperty($this->cmd, 'options', [
            'switch1' => ['type' => 'bool', 'short' => 'b', 'description' => 'Test Option'],
        ]);

        @unlink(storage_path('~test/stdout.txt'));
    }

    public function testGetNameAndDescription()
    {
        $this->cmd->__construct([]);
        $this->assertSame('test:foo', $this->cmd->name());
        $this->assertSame('Just a test command.', $this->cmd->description());
    }

    public function testCommandWithoutName()
    {
        $this->setPrivateProperty($this->cmd, 'name', null);
        $this->expectException(LogicException::class);
        $this->cmd->__construct();
    }

    // should print the help screen
    public function testRunWithNullArgument()
    {
        $this->setPrivateProperty($this->cmd, 'arguments', [
            'stringArg1' => ['type' => 'string', 'description' => 'Test Argument1'], // without default
            'stringArg2' => ['type' => 'string', 'default' => null, 'description' => 'Test Argument2'], // with default!
            'stringArg3' => ['type' => 'string', 'default' => 'bar', 'description' => 'Test Argument3'], // with default!
        ]);
        $buffer = storage_path('~test/stdout.txt');
        $stdio = new Stdio(null, fopen($buffer, 'w'));
        $this->cmd->__construct(null, $stdio);
        $this->assertSame($stdio, $this->cmd->stdio());
        $this->assertSame(Command::EXIT_SUCCESS, $this->cmd->run());
        $out = file_get_contents($buffer);
        $this->assertTrue(strpos($out, "Help:\n  Just a test command.") !== false);
        $this->assertTrue(strpos($out, "Arguments:\n  stringArg1         Test Argument1\n  stringArg2         Test Argument2 [default: null]\n  stringArg3         Test Argument3 [default: \"bar\"]") !== false);
        $this->assertTrue(strpos($out, "Options:\n  --switch1,   -b    Test Option") !== false);
    }

    public function testHelpOption()
    {
        $buffer = storage_path('~test/stdout.txt');
        $stdio = new Stdio(null, fopen($buffer, 'w'));
        $this->cmd->__construct(['foo', '--help'], $stdio);
        $this->assertSame($stdio, $this->cmd->stdio());
        $this->assertSame(Command::EXIT_SUCCESS, $this->cmd->run());
        $out = file_get_contents($buffer);
        $this->assertTrue(strpos($out, "Help:\n  Just a test command.") !== false);
        $this->assertTrue(strpos($out, "Arguments:\n  stringArg1         Test Argument [default: \"bar\"]") !== false);
        $this->assertTrue(strpos($out, "Options:\n  --switch1,   -b    Test Option") !== false);
    }

    public function testHelpShortOption()
    {
        $buffer = storage_path('~test/stdout.txt');
        $stdio = new Stdio(null, fopen($buffer, 'w'));
        $this->cmd->__construct(['foo', '-h'], $stdio);
        $this->assertSame($stdio, $this->cmd->stdio());
        $this->assertSame(Command::EXIT_SUCCESS, $this->cmd->run());
        $out = file_get_contents($buffer);
        $this->assertTrue(strpos($out, "Help:\n  Just a test command.") !== false);
        $this->assertTrue(strpos($out, "Arguments:\n  stringArg1         Test Argument [default: \"bar\"]") !== false);
        $this->assertTrue(strpos($out, "Options:\n  --switch1,   -b    Test Option") !== false);
    }

    public function testVerboseOption()
    {
        $this->cmd->__construct(['foo', '--verbose=2']);
        $this->assertSame(Command::EXIT_SUCCESS, $this->cmd->run());
        $this->assertTrue($this->cmd->isVerbose());
    }

    public function testVerboseShortOption()
    {
        $this->cmd->__construct(['foo', '-v=2']);
        $this->assertSame(Command::EXIT_SUCCESS, $this->cmd->run());
        $this->assertTrue($this->cmd->isVerbose());
    }

    public function testOmittedVerboseOptionValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cmd->__construct(['foo', '--verbose']);
    }

    public function testInvalidVerboseOptionValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cmd->__construct(['foo', '--verbose=4']);
    }

    public function testQuietMode()
    {
        $this->cmd->__construct(['foo', '--verbose=0']);
        $this->assertSame(Command::EXIT_SUCCESS, $this->cmd->run());
        $this->assertTrue($this->cmd->isQuiet());
        $this->assertFalse($this->cmd->isVerbose());
        $this->assertFalse($this->cmd->isDebug());
    }

    public function testNormalMode()
    {
        $this->cmd->__construct(['foo', '--verbose=1']);
        $this->assertSame(Command::EXIT_SUCCESS, $this->cmd->run());
        $this->assertFalse($this->cmd->isQuiet());
        $this->assertFalse($this->cmd->isVerbose());
        $this->assertFalse($this->cmd->isDebug());
    }

    public function testVerboseMode()
    {
        $this->cmd->__construct(['foo', '--verbose=2']);
        $this->assertSame(Command::EXIT_SUCCESS, $this->cmd->run());
        $this->assertFalse($this->cmd->isQuiet());
        $this->assertTrue($this->cmd->isVerbose());
        $this->assertFalse($this->cmd->isDebug());
    }

    public function testDebugMode()
    {
        $this->cmd->__construct(['foo', '--verbose=3']);
        $this->assertSame(Command::EXIT_SUCCESS, $this->cmd->run());
        $this->assertFalse($this->cmd->isQuiet());
        $this->assertTrue($this->cmd->isVerbose());
        $this->assertTrue($this->cmd->isDebug());
    }

    public function testHandleReturnsNull()
    {
        $this->cmd->expects($this->once())->method('handle')->willReturn(null);
        $this->cmd->__construct([]);
        $this->assertSame(Command::EXIT_SUCCESS, $this->cmd->run());
    }

    public function testHandleReturnsTrue()
    {
        $this->cmd->expects($this->once())->method('handle')->willReturn(true);
        $this->cmd->__construct([]);
        $this->assertSame(Command::EXIT_SUCCESS, $this->cmd->run());
    }

    public function testHandleReturnsFalse()
    {
        $this->cmd->expects($this->once())->method('handle')->willReturn(false);
        $this->cmd->__construct([]);
        $this->assertSame(Command::EXIT_FAILURE, $this->cmd->run());
    }

    public function testHandleReturnsExitCode()
    {
        $this->cmd->expects($this->once())->method('handle')->willReturn(4711);
        $this->cmd->__construct([]);
        $this->assertSame(4711, $this->cmd->run());
    }

    public function testRunWithArguments()
    {
        $arguments = [
            'stringArg1' => ['type' => 'string', 'default' => 'bar', 'description' => 'String Argument'],
            'boolArg1'   => ['type' => 'bool',   'default' => true,  'description' => 'Boolean Argument'],
            'floatArg1'  => ['type' => 'float',  'default' => 3.14,  'description' => 'Float Argument'],
            'intArg1'    => ['type' => 'int',    'default' => 1234,  'description' => 'Integer Argument'],
        ];
        $this->setPrivateProperty($this->cmd, 'arguments', $arguments);
        $this->cmd->__construct(['rab', false, 41.3, 4321]);
        $this->assertSame(Command::EXIT_SUCCESS, $this->cmd->run());
        $this->assertSame($arguments, $this->cmd->arguments());
        $this->assertSame(['switch1' => ['type' => 'bool', 'short' => 'b', 'description' => 'Test Option']], $this->cmd->options());
        $this->assertSame('rab', $this->cmd->input('stringArg1'));
        $this->assertSame(false, $this->cmd->input('boolArg1'));
        $this->assertSame(41.3, $this->cmd->input('floatArg1'));
        $this->assertSame(4321, $this->cmd->input('intArg1'));
        $this->assertSame(null, $this->cmd->input('wrong'));
    }

    public function testRunWithoutArguments()
    {
        $arguments = [
            'stringArg1' => ['type' => 'string', 'default' => 'bar', 'description' => 'String Argument'],
            'boolArg1'   => ['type' => 'bool',   'default' => true,  'description' => 'Boolean Argument'],
            'floatArg1'  => ['type' => 'float',  'default' => 3.14,  'description' => 'Float Argument'],
            'intArg1'    => ['type' => 'int',    'default' => 1234,  'description' => 'Integer Argument'],
        ];
        $this->setPrivateProperty($this->cmd, 'arguments', $arguments);
        $this->cmd->__construct([]);
        $this->assertSame(Command::EXIT_SUCCESS, $this->cmd->run());
        $this->assertSame('bar', $this->cmd->input('stringArg1'));
        $this->assertSame(true, $this->cmd->input('boolArg1'));
        $this->assertSame(3.14, $this->cmd->input('floatArg1'));
        $this->assertSame(1234, $this->cmd->input('intArg1'));
    }

    public function testRunWithOmittingRequiredArgument()
    {
        $this->setPrivateProperty($this->cmd, 'arguments', [
            'stringArg1' => ['type' => 'string', 'description' => 'Test Argument'], // no default!
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->cmd->__construct([]);
    }

    public function testRunWithUndefinedArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cmd->__construct(['foo', 'wrong']);
    }

    public function testRunWithOption()
    {
        $options = [
            'switch1'  => ['type' => 'bool',   'short' => 'b', 'description' => 'Bolean Option'],
            'float1'   => ['type' => 'float',  'short' => 'f', 'default' => 2.72,  'description' => 'Float Option'],
            'integer1' => ['type' => 'int',    'short' => 'i', 'default' => 4567,  'description' => 'Integer Option'],
            'string1'  => ['type' => 'string', 'short' => 's', 'default' => 'baz', 'description' => 'String Option'],
        ];
        $this->setPrivateProperty($this->cmd, 'options', $options);
        $this->cmd->__construct(['--switch1', '--float1=27.2', '--integer1=7654', '--string1=zab']);
        $this->assertSame(Command::EXIT_SUCCESS, $this->cmd->run());
        $this->assertSame(['stringArg1' => ['type' => 'string', 'default' => 'bar', 'description' => 'Test Argument']], $this->cmd->arguments());
        $this->assertSame($options, $this->cmd->options());
        $this->assertSame(true, $this->cmd->input('switch1'));
        $this->assertSame(27.2, $this->cmd->input('float1'));
        $this->assertSame(7654, $this->cmd->input('integer1'));
        $this->assertSame('zab', $this->cmd->input('string1'));
        $this->assertSame(null, $this->cmd->input('wrong'));
    }

    public function testBoolOptionWithValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cmd->__construct(['--switch1=true']);
    }

    public function testFloatOptionWithoutValue()
    {
        $options = [
            'float1' => ['type' => 'float', 'short' => 'f', 'default' => 2.72, 'description' => 'Float Option'],
        ];
        $this->setPrivateProperty($this->cmd, 'options', $options);
        $this->expectException(InvalidArgumentException::class);
        $this->cmd->__construct(['--float1']);
    }

    public function testIntegerOptionWithoutValue()
    {
        $options = [
            'integer1' => ['type' => 'int', 'short' => 'i', 'default' => 4567, 'description' => 'Integer Option'],
        ];
        $this->setPrivateProperty($this->cmd, 'options', $options);
        $this->expectException(InvalidArgumentException::class);
        $this->cmd->__construct(['--integer1']);
    }

    public function testStringOptionWithoutValue()
    {
        $options = [
            'string1' => ['type' => 'string', 'short' => 's', 'default' => 'baz', 'description' => 'String Option'],
        ];
        $this->setPrivateProperty($this->cmd, 'options', $options);
        $this->expectException(InvalidArgumentException::class);
        $this->cmd->__construct(['--string1']);
    }

    public function testBoolOptionWithEmptyValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cmd->__construct(['--switch1=']);
    }

    public function testFloatOptionWithEmptyValue()
    {
        $options = [
            'float1' => ['type' => 'float', 'short' => 'f', 'default' => 2.72, 'description' => 'Float Option'],
        ];
        $this->setPrivateProperty($this->cmd, 'options', $options);
        $this->cmd->__construct(['--float1=']);
        $this->assertSame(2.72, $this->cmd->input('float1'));
    }

    public function testIntegerOptionWithEmptyValue()
    {
        $options = [
            'integer1' => ['type' => 'int', 'short' => 'i', 'default' => 4567, 'description' => 'Integer Option'],
        ];
        $this->setPrivateProperty($this->cmd, 'options', $options);
        $this->cmd->__construct(['--integer1=']);
        $this->assertSame(4567, $this->cmd->input('integer1'));
    }

    public function testStringOptionWithEmptyValue()
    {
        $options = [
            'string1' => ['type' => 'string', 'short' => 's', 'default' => 'baz', 'description' => 'String Option'],
        ];
        $this->setPrivateProperty($this->cmd, 'options', $options);
        $this->cmd->__construct(['--string1=']);
        $this->assertSame('baz', $this->cmd->input('string1'));
    }

    public function testAbort()
    {
        $this->cmd->__construct([]);
        $this->expectException(StopException::class);
        $this->cmd->abort('oweh');
    }

    public function testClear()
    {
        require __DIR__ . '/../_data/fakes/passthru.php.fake';

        ob_start();
        try {
            $this->assertInstanceOf(\Core\Services\Contracts\Command::class, $this->cmd->clearTerminal());
        }
        finally {
            $out = ob_get_clean();
        }
        $this->assertSame('clear', $out);
    }

    public function testTerminalWidthAndHeight()
    {
        $this->cmd->__construct([]);
        $this->assertTrue(is_int($this->cmd->terminalWidth()), 'terminalWidth is int');
        $this->assertTrue(is_int($this->cmd->terminalHeight()), 'terminalHeight is int');
    }

    public function testConsoleMode()
    {
        $this->cmd->__construct([]);
        $this->invokePrivateMethod($this->cmd, 'getConsoleMode');
    }

    public function testImputStream()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['read', 'ask', 'confirm', 'choice', 'secret'])->getMock();
        $stdio->expects($this->once())->method('read')->with('prompt')->willReturn('x');
        $stdio->expects($this->once())->method('ask')->with('prompt')->willReturn('y');
        $stdio->expects($this->once())->method('confirm')->with('prompt')->willReturn(true);
        $stdio->expects($this->once())->method('choice')->with('prompt', ['a', 'b'])->willReturn('a');
        $stdio->expects($this->once())->method('secret')->with('prompt')->willReturn('psss');
        $this->cmd->__construct([], $stdio);
        $this->assertSame('x', $this->cmd->read('prompt'));
        $this->assertSame('y', $this->cmd->ask('prompt'));
        $this->assertSame(true, $this->cmd->confirm('prompt'));
        $this->assertSame('a', $this->cmd->choice('prompt', ['a', 'b']));
        $this->assertSame('psss', $this->cmd->secret('prompt'));
    }

    public function testOutputStream()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods([
            'format', 'write', 'line', 'info', 'notice', 'question', 'warn', 'error', 'quiet', 'verbose', 'debug', 'hr', 'table'
        ])->getMock();
        $stdio->expects($this->once())->method('format')->with('text')->willReturn('foo');
        $stdio->expects($this->once())->method('write')->with('text', true)->willReturnSelf();
        $stdio->expects($this->once())->method('line')->with('text')->willReturnSelf();
        $stdio->expects($this->once())->method('info')->with('text')->willReturnSelf();
        $stdio->expects($this->once())->method('notice')->with('text')->willReturnSelf();
        $stdio->expects($this->once())->method('question')->with('text')->willReturnSelf();
        $stdio->expects($this->once())->method('warn')->with('text')->willReturnSelf();
        $stdio->expects($this->once())->method('error')->with('text')->willReturnSelf();
        $stdio->expects($this->once())->method('quiet')->with('text')->willReturnSelf();
        $stdio->expects($this->once())->method('verbose')->with('text')->willReturnSelf();
        $stdio->expects($this->once())->method('debug')->with('text')->willReturnSelf();
        $stdio->expects($this->once())->method('hr')->with(30)->willReturnSelf();
        $stdio->expects($this->once())->method('table')->with(['a', 'b'], ['A', 'B'])->willReturnSelf();
        $this->cmd->__construct([], $stdio);
        $this->assertSame('foo', $this->cmd->format('text'));
        $this->assertInstanceOf(Command::class, $this->cmd->write('text', true));
        $this->assertInstanceOf(Command::class, $this->cmd->line('text'));
        $this->assertInstanceOf(Command::class, $this->cmd->info('text'));
        $this->assertInstanceOf(Command::class, $this->cmd->notice('text'));
        $this->assertInstanceOf(Command::class, $this->cmd->question('text'));
        $this->assertInstanceOf(Command::class, $this->cmd->warn('text'));
        $this->assertInstanceOf(Command::class, $this->cmd->error('text'));
        $this->assertInstanceOf(Command::class, $this->cmd->quiet('text'));
        $this->assertInstanceOf(Command::class, $this->cmd->verbose('text'));
        $this->assertInstanceOf(Command::class, $this->cmd->debug('text'));
        $this->assertInstanceOf(Command::class, $this->cmd->hr(30));
        $this->assertInstanceOf(Command::class, $this->cmd->table(['a', 'b'], ['A', 'B']));
    }

}