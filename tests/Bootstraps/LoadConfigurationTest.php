<?php

namespace Core\Tests\Bootstraps;

use Core\Bootstraps\LoadConfiguration;
use Core\Services\Contracts\Config;
use Core\Services\DI;
use Core\Testing\TestCase;

class LoadConfigurationTest extends TestCase
{
    private static $origConfig;
    private static $envFile;
    private static $configPath;
    private static $cache;

    private $bootstrap;

    public static function setUpBeforeClass()
    {
        self::$origConfig = DI::getInstance()->get('config')->get();
        self::$envFile = __DIR__ . '/../_data/env.ini';
        self::$configPath = __DIR__ . '/../_data/config';
        self::$cache = storage_path('~test/config.php');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->get('config')->set(null, self::$origConfig);
        @unlink(self::$cache);
        @rmdir(dirname(self::$cache));
    }

    protected function setUp()
    {
        @mkdir(dirname(self::$cache));
        @unlink(self::$cache);
        $this->bootstrap = new LoadConfiguration(self::$envFile, self::$configPath, self::$cache);
    }

    public function testLoadEnvironment()
    {
        $this->assertFalse(getenv('TEST_FOO'));
        $this->invokePrivateMethod($this->bootstrap, 'loadEnvironment');
        $this->assertSame('bar', getenv('TEST_FOO'));
    }

    public function testLoadConfigFromFiles()
    {
        $this->assertNull(config('test.foo'));
        $this->assertInstanceOf(Config::class, $this->invokePrivateMethod($this->bootstrap, 'loadConfigFromFiles'));
        $this->assertSame('bar', config('test.foo'));
        $this->assertNull(config('.ignore'));
    }

    public function testIsCacheUpToDate()
    {
        $this->assertFalse($this->invokePrivateMethod($this->bootstrap, 'isCacheUpToDate')); // cache file does not exist

        touch(self::$cache);
        $this->assertFalse($this->invokePrivateMethod($this->bootstrap, 'isCacheUpToDate')); // time of cache does not equal with config path and env file

        touch(self::$cache, max(filemtime(self::$configPath), filemtime(self::$envFile)));
        $this->assertTrue($this->invokePrivateMethod($this->bootstrap, 'isCacheUpToDate'));
    }

    public function testLoadConfigFromCache()
    {
        copy(self::$configPath . '/.ignore.php', self::$cache);
        $this->assertNull(config('baz'));
        $this->assertInstanceOf(Config::class, $this->invokePrivateMethod($this->bootstrap, 'loadConfigFromCache'));
        $this->assertSame('buz', config('baz'));
    }

    public function testSaveConfigToCache()
    {
        // create cache
        @rmdir(dirname(self::$cache));
        $config = new \Core\Services\Config();
        $config->set('foo', 'bar');
        $this->invokePrivateMethod($this->bootstrap, 'saveConfigToCache', [$config]);
        $this->assertFileExists(self::$cache);
        /** @noinspection PhpIncludeInspection */
        $this->assertSame(['foo' => 'bar'], include self::$cache, 'create cache');
        $this->assertSame(filemtime(self::$cache), max(filemtime(self::$configPath), filemtime(self::$envFile)));

        // overwrite cachae
        $config->set('foo', 'baz');
        $this->invokePrivateMethod($this->bootstrap, 'saveConfigToCache', [$config]);
        /** @noinspection PhpIncludeInspection */
        $this->assertSame(['foo' => 'baz'], include self::$cache, 'overwrite cache');
    }
}