<?php

namespace Core\Tests\Services;

use Core\Services\CacheFactory;
use Core\Services\Contracts\Cache;
use Core\Services\DI;
use Core\Testing\TestCase;
use InvalidArgumentException;

class CacheFactoryTest extends TestCase
{
    /**
     * @var CacheFactory
     */
    private $factory;

    public static function setUpBeforeClass()
    {
        DI::getInstance()->get('config')->set('cache', [
            'default' => 'file',
            'stores' => [
                'apc' => [
                    'driver' => 'apc',
                ],
                'array' => [
                    'driver' => 'array',
                ],
                'file' => [
                    'driver' => 'file',
                    'path' => storage_path('cache/doctrine'),
                ],
                'memcached' => [
                    'driver' => 'memcached',
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 100,
                ],
                'redis' => [
                    'driver' => 'redis',
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'timeout' => 0.0,
                ],
                'foo1' => [
                ],
                'foo2' => [
                    'driver' => 'wrong',
                ],
            ],
        ]);
    }

    protected function setUp()
    {
        $this->factory = new CacheFactory;
    }

    public function testFileStore()
    {
        $this->assertInstanceOf(Cache::class, $this->factory->store());
        $this->assertInstanceOf(Cache::class, $this->factory->store('file'));
    }

    public function testApcStore()
    {
        $this->assertInstanceOf(Cache::class, $this->factory->store('apc'));
    }

    public function testArray()
    {
        $this->assertInstanceOf(Cache::class, $this->factory->store('array'));
    }

    public function testMemcachedStore()
    {
        require __DIR__  . '/../_data/fakes/Memcached.php.fake';
        $this->assertInstanceOf(Cache::class, $this->factory->store('memcached'));
    }

    public function testRedisStore()
    {
        require __DIR__  . '/../_data/fakes/Redis.php.fake';
        $this->assertInstanceOf(Cache::class, $this->factory->store('redis'));
    }

    public function testStoreNotDefined()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->store('foo');
    }

    public function testDriverNotSpecified()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->store('foo1');
    }

    public function testDriverNotSupported()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->store('foo2');
    }
}