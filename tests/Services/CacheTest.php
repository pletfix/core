<?php

namespace Core\Tests\Services;

use Core\Services\Contracts\Cache;
use Core\Testing\TestCase;
use Doctrine\Common\Cache\ArrayCache;

class CacheTest extends TestCase
{
    public function testBase()
    {
        $cache = new \Core\Services\Cache(new ArrayCache());

        $this->assertFalse($cache->has('foo'));
        $this->assertSame('baz', $cache->get('foo', 'baz'));
        $this->assertInstanceOf(Cache::class, $cache->set('foo', 'bar'));
        $this->assertTrue($cache->has('foo'));
        $this->assertSame('bar', $cache->get('foo', 'baz'));
        $this->assertInstanceOf(Cache::class, $cache->delete('foo'));
        $this->assertFalse($cache->has('foo'));

        $cache->set('foo1', 'bar1')->set('foo2', 'bar2');
        $this->assertTrue($cache->has('foo1'));
        $this->assertTrue($cache->has('foo2'));
        $this->assertInstanceOf(Cache::class, $cache->clear());
        $this->assertFalse($cache->has('foo1'));
        $this->assertFalse($cache->has('foo2'));
    }
}