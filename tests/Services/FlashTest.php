<?php

namespace Core\Tests\Services;

use Core\Services\Contracts\Flash;
use Core\Testing\TestCase;

class FlashTest extends TestCase
{
    public function testBase()
    {
        $flash = new \Core\Services\Flash;
        
        $this->assertFalse($flash->has('foo'));
        $this->assertSame('baz', $flash->get('foo', 'baz'));
        $this->assertInstanceOf(Flash::class, $flash->set('foo', 'bar'));
        $this->assertFalse($flash->has('foo'));
        $this->assertInstanceOf(Flash::class, $flash->age());
        $this->assertTrue($flash->has('foo'));
        $this->assertSame('bar', $flash->get('foo', 'baz'));
        $flash->age();
        $this->assertFalse($flash->has('foo'));
        $this->assertSame('baz', $flash->get('foo', 'baz'));

        $flash->set('foo', 'bar')->age();
        $this->assertTrue($flash->has('foo'));
        $this->assertInstanceOf(Flash::class, $flash->delete('foo'));
        $this->assertFalse($flash->has('foo'));

        $flash->set('foo1', 'bar1')->set('foo2', 'bar2')->age();
        $this->assertSame(['foo1' => 'bar1', 'foo2' => 'bar2'], $flash->get());
        $this->assertInstanceOf(Flash::class, $flash->clear());
        $this->assertFalse($flash->has('foo1'));
        $this->assertFalse($flash->has('foo2'));

        $this->assertInstanceOf(Flash::class, $flash->set(null, ['a' => 'A', 'b' => 'B']));
        $flash->age();
        $this->assertSame('A', $flash->get('a'));
    }

    public function testMerge()
    {
        $flash = new \Core\Services\Flash;
        $flash->set(null, ['a' => 'A', 'b' => 'B']);
        $this->assertInstanceOf(Flash::class, $flash->merge(null, ['b' => ['b1' => 'B1', 'b2' => 'B2'], 'c' => 'C']));
        $this->assertInstanceOf(Flash::class, $flash->merge('b', ['b2' => 'B2!', 'b3' => 'B3']));
        $flash->age();
        $this->assertSame(['a' => 'A', 'b' => ['b1' => 'B1', 'b2' => 'B2!', 'b3' => 'B3'], 'c' => 'C'], $flash->get());
        $this->assertFalse($flash->has('foo'));
        $flash->clear();
    }

    public function testSetNow()
    {
        $flash = new \Core\Services\Flash;
        $this->assertFalse($flash->has('foo1'));
        $this->assertInstanceOf(Flash::class, $flash->setNow(null, ['foo1' => 'bar1']));
        $this->assertTrue($flash->has('foo1'));
        $this->assertInstanceOf(Flash::class, $flash->setNow('foo2', 'bar2'));
        $this->assertTrue($flash->has('foo2'));
        $flash->age();
        $this->assertFalse($flash->has('foo1'));
        $this->assertFalse($flash->has('foo2'));
    }

    public function testSeReflash()
    {
        $flash = new \Core\Services\Flash;
        $flash->set(null, ['foo1' => 'bar1', 'foo2' => 'bar2'])->age();
        $this->assertTrue($flash->has('foo1'));
        $this->assertTrue($flash->has('foo2'));
        $this->assertInstanceOf(Flash::class, $flash->reflash());
        $flash->age();
        $this->assertTrue($flash->has('foo1'));
        $this->assertTrue($flash->has('foo2'));
        $this->assertInstanceOf(Flash::class, $flash->reflash(['foo1', 'foo2']));
        $flash->age();
        $this->assertTrue($flash->has('foo1'));
        $this->assertTrue($flash->has('foo2'));
        $this->assertInstanceOf(Flash::class, $flash->reflash('foo1'));
        $flash->age();
        $this->assertTrue($flash->has('foo1'));
        $this->assertFalse($flash->has('foo2'));
        $flash->age();
        $this->assertFalse($flash->has('foo1'));
    }
}