<?php

namespace Core\Tests\Services;

use Core\Services\DI;
use Core\Services\Logger;
use Core\Testing\TestCase;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

class LoggerTest extends TestCase
{
    private $dailyFile;
    private $singleFile;

    protected function setUp()
    {
        $today = strftime('%Y-%m-%d', time());
        $this->dailyFile = storage_path("logs/~test-$today.log");
        $this->singleFile = storage_path("logs/~test.log");
        DI::getInstance()->get('config')->set('logger', [
            'type' => 'daily',
            'level' => 'debug',
            'max_files' => 5,
            'app_file' => '~test.log',
            'cli_file' => '~test.log',
            'permission' => 0664,
        ]);
    }

    protected function tearDown()
    {
        @unlink($this->dailyFile);
        @unlink($this->singleFile);
    }

    private function getDailyLog()
    {
        if (!file_exists($this->dailyFile)) {
            return null;
        }

        $log = file($this->dailyFile);
        $n = count($log);

        return $n > 0 ? trim($log[$n - 1]) : null;
    }

    private function getSingleLog()
    {
        if (!file_exists($this->singleFile)) {
            return null;
        }
        $log = file($this->singleFile);
        $n = count($log);

        return $n > 0 ? trim($log[$n - 1]) : null;
    }

    public function testDaily()
    {
        DI::getInstance()->get('config')->set('logger.type', 'daily');

        $l = new Logger;
        $this->assertInstanceOf(LoggerInterface::class, $l);

        $l->emergency('foo {a} and {b}!', ['a' => 'A', 'b' => 'B']);
        $this->assertStringEndsWith('EMERGENCY: foo A and B! {"a":"A","b":"B"}', $this->getDailyLog());

        $l->alert('foo {a} and {b}!', ['a' => 'A', 'b' => 'B']);
        $this->assertStringEndsWith('ALERT: foo A and B! {"a":"A","b":"B"}', $this->getDailyLog());

        $l->critical('foo {a} and {b}!', ['a' => 'A', 'b' => 'B']);
        $this->assertStringEndsWith('CRITICAL: foo A and B! {"a":"A","b":"B"}', $this->getDailyLog());

        $l->error('foo {a} and {b}!', ['a' => 'A', 'b' => 'B']);
        $this->assertStringEndsWith('ERROR: foo A and B! {"a":"A","b":"B"}', $this->getDailyLog());

        $l->warning('foo {a} and {b}!', ['a' => 'A', 'b' => 'B']);
        $this->assertStringEndsWith('WARNING: foo A and B! {"a":"A","b":"B"}', $this->getDailyLog());

        $l->notice('foo {a} and {b}!', ['a' => 'A', 'b' => 'B']);
        $this->assertStringEndsWith('NOTICE: foo A and B! {"a":"A","b":"B"}', $this->getDailyLog());

        $l->info('foo {a} and {b}!', ['a' => 'A', 'b' => 'B']);
        $this->assertStringEndsWith('INFO: foo A and B! {"a":"A","b":"B"}', $this->getDailyLog());

        $l->debug('foo {a} and {b}!', ['a' => 'A', 'b' => 'B']);
        $this->assertStringEndsWith('DEBUG: foo A and B! {"a":"A","b":"B"}', $this->getDailyLog());

        $l->log('alert', 'foo {a} and {b}!', ['a' => 'A', 'b' => 'B']);
        $this->assertStringEndsWith('ALERT: foo A and B! {"a":"A","b":"B"}', $this->getDailyLog());

        $l->log('ERROR', 'foo {a} and {b}!', ['a' => 'A', 'b' => 'B']);
        $this->assertStringEndsWith('ERROR: foo A and B! {"a":"A","b":"B"}', $this->getDailyLog());

        $this->expectException(InvalidArgumentException::class);
        $l->log('wrong', 'foo {a} and {b}!', ['a' => 'A', 'b' => 'B']);
    }

    public function testSingle()
    {
        DI::getInstance()->get('config')->set('logger.type', 'single');

        $l = new Logger;
        $this->assertInstanceOf(LoggerInterface::class, $l);

        $l->log('alert', 'foo {a} and {b}!', ['a' => 'A', 'b' => 'B']);
        $this->assertStringEndsWith('ALERT: foo A and B! {"a":"A","b":"B"}', $this->getSingleLog());
    }

    public function testSyslog()
    {
        DI::getInstance()->get('config')->set('logger.type', 'syslog');

        $l = new Logger;
        $this->assertInstanceOf(LoggerInterface::class, $l);
    }

    public function testErrorlog()
    {
        DI::getInstance()->get('config')->set('logger.type', 'errorlog');

        $l = new Logger;
        $this->assertInstanceOf(LoggerInterface::class, $l);
    }

    public function testWronglog()
    {
        DI::getInstance()->get('config')->set('logger.type', 'wrong');

        $this->expectException(RuntimeException::class);
        new Logger;
    }
}