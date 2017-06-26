<?php

namespace Core\Tests\Services;

use Core\Services\Contracts\Config;
use Core\Testing\TestCase;

class ConfigTest extends TestCase
{
    public function testBase()
    {
        $config = new \Core\Services\Config;
        $this->assertFalse($config->has('foo'));
        $this->assertSame('baz', $config->get('foo', 'baz'));
        $this->assertInstanceOf(Config::class, $config->set(null, ['foo' => 'bar']));
        $this->assertTrue($config->has('foo'));
        $this->assertSame('bar', $config->get('foo', 'baz'));
        $this->assertSame(['foo' => 'bar'], $config->get());
        $this->assertInstanceOf(Config::class, $config->set('foo2', 'bar2'));
        $this->assertSame('bar2', $config->get('foo2'));
        $config->set('foo3.a', 'A')->set('foo3.b', 'B');
        $this->assertSame(['a' => 'A', 'b' => 'B'], $config->get('foo3'));
        $this->assertSame('B', $config->get('foo3.b'));
    }
}