<?php

namespace Core\Tests\Services;

use Core\Services\Contracts\Cookie;
use Core\Testing\TestCase;

class CookieTest extends TestCase
{
    public function testBase()
    {
        ini_set('session.use_cookies', true);

        $cookie = new \Core\Services\Cookie;
        $this->assertFalse($cookie->has('foo'));
        $this->assertSame('baz', $cookie->get('foo', 'baz'));

        $this->assertInstanceOf(Cookie::class, $cookie->set('foo', 'bar'));
        $this->assertTrue($cookie->has('foo'));
        $this->assertSame('bar', $cookie->get('foo', 'baz'));

        $this->assertInstanceOf(Cookie::class, $cookie->delete('foo'));
        $this->assertFalse($cookie->has('foo'));

        $this->assertInstanceOf(Cookie::class, $cookie->setForever('foo', 'bar'));
        $this->assertTrue($cookie->has('foo'));
    }
}