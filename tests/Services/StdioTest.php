<?php

namespace Core\Tests\Services;

use Core\Services\Stdio;
use Core\Testing\TestCase;
use RuntimeException;

class StdioTest extends TestCase
{
    private $input;
    private $stdin;
    private $stdout;
    private $stderr;

    /**
     * @var Stdio
     */
    private $io;

    private function input($s)
    {
        fwrite($this->input, "$s\n");
    }

    protected function setUp()
    {
        $path = storage_path('~test');
        @mkdir($path);
        $this->input = fopen($path . '/stdin.txt', 'w');
        $this->stdin = fopen($path . '/stdin.txt', 'r');
        $this->stdout = fopen($path . '/stdout.txt', 'w');
        $this->stderr = fopen($path . '/stderr.txt', 'w');
        $this->io = new Stdio($this->stdin, $this->stdout, $this->stderr);
    }

    protected function tearDown()
    {
        @fclose($this->input);
        @fclose($this->stdin);
        @fclose($this->stdout);
        @fclose($this->stderr);
        $path = storage_path('~test');
        @unlink($path . '/stdin.txt');
        @unlink($path . '/stdout.txt');
        @unlink($path . '/stderr.txt');
        @rmdir($path);
    }

    public function testInput()
    {
        $this->assertTrue($this->io->canRead(1)); // todo müsste jetzt noch false sein!

        $this->input('y');
        $this->assertTrue($this->io->canRead(1));
        $this->assertSame('yes', $this->io->read('your answer:', ['y' => 'yes', 'n' => 'no'], 'bar'));

        $this->input('no');
        $this->assertSame('no', $this->io->read('your answer:', ['y' => 'yes', 'n' => 'no'], 'bar'));

        $this->input('');
        $this->assertSame('bar', $this->io->read('your answer:', ['y' => 'yes', 'n' => 'no'], 'bar'));

        $this->input('x');
        $this->input('yes');
        $this->assertSame('yes', $this->io->read('your answer:', ['y' => 'yes', 'n' => 'no'], 'bar'));

        $this->input('');
        $this->input('foo');
        $this->assertSame('foo', $this->io->read('your answer:'));

        $this->input('baz');
        $this->assertSame('baz', $this->io->read('your answer:', null, ''));

        $this->input('bax');
        $this->assertSame('bax', $this->io->read('your answer:', null, true));

        $this->input('');
        $this->assertSame(false, $this->io->read('your answer:', null, false));
    }

    public function testAsk()
    {
        $this->input('ask');
        $this->assertSame('ask', $this->io->ask('your answer:'));
    }

    public function testConfirm()
    {
        $this->input('wrong');
        $this->input('y');
        $this->assertTrue($this->io->confirm('your answer:'));
        $this->input('ye');
        $this->assertTrue($this->io->confirm('your answer:'));
        $this->input('yes');
        $this->assertTrue($this->io->confirm('your answer:'));
        $this->input('n');
        $this->assertFalse($this->io->confirm('your answer:'));
        $this->input('no');
        $this->assertFalse($this->io->confirm('your answer:'));
    }

    public function testChoice()
    {
        $this->input('y');
        $this->assertSame('yes', $this->io->choice('your answer:', ['y' => 'yes', 'n' => 'no']));
    }

    public function testSecret()
    {
        $this->input('');
        $this->input('psss');
        $this->assertSame('psss', $this->io->secret('your password:'));

        $this->input('');
        $this->assertSame('psss..', $this->io->secret('your password:', 'psss..'));

        $this->input('foo');
        $this->assertSame('foo', $this->io->secret('your password:', 'psss..', false));
    }

    public function testClear()
    {
        require 'fakes/passthru.php.fake';

        ob_start();
        try {
            $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->clear());
        }
        finally {
            $out = ob_get_clean();
        }
        $this->assertSame('clear', $out);
    }

    public function testFormat()
    {
        $this->assertSame('foo', $this->io->format('foo'));

        $io = new Stdio($this->stdin); // stdout supports colorization, file stream doesn't
        $this->assertSame("\e[mfoo\e[0m", $io->format('foo'));
        $this->assertSame("\e[mfoo\e[0m\n\e[mbar\e[0m", $io->format("foo\nbar"));
        $this->assertSame("\e[33;1mfoo\e[0m", $io->format("foo", [Stdio::STYLE_YELLOW, Stdio::STYLE_BOLD]));
    }

    public function testWrite()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->write('foo'));
        $io = new Stdio($this->stdin, $this->stdin); // not writable output stream
        $this->expectException(RuntimeException::class);
        $io->write('foo');
    }

    public function testLine()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->line('foo'));
    }

    public function testInfo()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->info('foo'));
    }

    public function testNotice()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->notice('foo'));
    }

    public function testQuestion()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->question('foo'));
    }

    public function testWarn()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->warn('foo'));
    }

    public function testError()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->error('foo'));
    }

    public function testQuite()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->quiet('foo'));
    }

    public function testVerbose()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->verbose('foo'));
    }

    public function testDebug()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->debug('foo'));
    }

    public function testHr()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->hr(10));
    }

    public function testTable()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->table(['a', 'b'], ['A', 'B']));
    }

    public function testErr()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->err('foo'));
    }

    public function testSetterAndGetter()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->setStdin($this->stdin));
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->setStdout($this->stdout));
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->setStderr($this->stderr));
        $this->assertSame($this->stdin, $this->io->getStdin());
        $this->assertSame($this->stdout, $this->io->getStdout());
        $this->assertSame($this->stderr, $this->io->getStderr());
    }

    public function testSetAndGetVerbosity()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, $this->io->setVerbosity(Stdio::VERBOSITY_QUIET));
        $this->assertSame(Stdio::VERBOSITY_QUIET, $this->io->getVerbosity());
        $this->assertTrue($this->io->isQuiet());
        $this->assertFalse($this->io->isVerbose());
        $this->assertFalse($this->io->isDebug());

        $this->io->setVerbosity(Stdio::VERBOSITY_VERBOSE);
        $this->assertFalse($this->io->isQuiet());
        $this->assertTrue($this->io->isVerbose());
        $this->assertFalse($this->io->isDebug());

        $this->io->setVerbosity(Stdio::VERBOSITY_DEBUG);
        $this->assertFalse($this->io->isQuiet());
        $this->assertTrue($this->io->isVerbose());
        $this->assertTrue($this->io->isDebug());

        $this->io->setVerbosity(Stdio::VERBOSITY_NORMAL);
        $this->assertFalse($this->io->isQuiet());
        $this->assertFalse($this->io->isVerbose());
        $this->assertFalse($this->io->isDebug());

        $this->expectException(\InvalidArgumentException::class);
        $this->io->setVerbosity(4);
    }
}