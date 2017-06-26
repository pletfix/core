<?php

namespace Core\Tests\Services;

use Core\Services\Contracts\Session;
use Core\Testing\TestCase;

class SessionTest extends TestCase
{
    public function testBase()
    {
        $session = new \Core\Services\Session;

        $this->assertFalse($session->has('foo'));
        $this->assertSame('baz', $session->get('foo', 'baz'));
        $this->assertNull($session->get('foo.bar'));
        $this->assertInstanceOf(Session::class, $session->set('foo', 'bar'));
        $this->assertTrue($session->has('foo'));
        $this->assertSame('bar', $session->get('foo', 'baz'));
        $this->assertInstanceOf(Session::class, $session->delete('foo'));
        $this->assertFalse($session->has('foo'));

        $session->set('foo1', 'bar1')->set('foo2', 'bar2');
        $this->assertArrayHasKey('foo1', $session->get());
        $this->assertArrayHasKey('foo2', $session->get());
        $this->assertInstanceOf(Session::class, $session->clear());
        $this->assertFalse($session->has('foo1'));
        $this->assertFalse($session->has('foo2'));

        $this->assertInstanceOf(Session::class, $session->set(null, ['a' => 'A', 'b' => 'B']));
        $this->assertSame('A', $session->get('a'));

        $session->set('foo3.a', 'A')->set('foo3.b', 'B');
        $this->assertSame(['a' => 'A', 'b' => 'B'], $session->get('foo3'));
        $this->assertTrue($session->has('foo3.a'));
        $this->assertInstanceOf(Session::class, $session->delete('foo3.a'));
        $this->assertFalse($session->has('foo3.a'));
        $this->assertSame('B', $session->get('foo3.b'));
        $this->assertInstanceOf(Session::class, $session->delete('foo4.a')); // delete a don't exist key
    }

    public function testKill()
    {
        ini_set('session.use_cookies', true);
        $session = new \Core\Services\Session;
        $session->set('foo', 'bar');
        $id = session_id();
        $this->assertInstanceOf(Session::class, $session->kill());
        $this->assertFalse($session->isStarted());
        $this->assertEmpty($session->get());
        $this->assertNotEquals($id, session_id());
    }

    public function testStartAndCommitAndAbort()
    {
        $session = new \Core\Services\Session;
        $session->kill();
        $this->assertFalse($session->isStarted());
        $session->set('foo1', 'bar1'); // automatic commit
        $this->assertFalse($session->isStarted());
        $this->assertInstanceOf(Session::class, $session->start());
        $this->assertTrue($session->isStarted());
        $session->set('foo2', 'bar2');
        $this->assertInstanceOf(Session::class, $session->commit());
        $this->assertFalse($session->isStarted());
        $session->start();
        $session->set('foo3', 'bar3');
        $this->assertInstanceOf(Session::class, $session->abort());
        $this->assertFalse($session->isStarted());

        $this->assertSame('bar1', $session->get('foo1'));
        $this->assertSame('bar2', $session->get('foo2'));
        $this->assertNull($session->get('foo3'));
    }

    public function testRegenerate()
    {
        $session = new \Core\Services\Session;
        $session->set('foo', 'bar');
        $id = session_id();
        $this->assertInstanceOf(Session::class, $session->regenerate());
        $this->assertNotEquals($id, session_id());
        $this->assertSame('bar', $session->get('foo'));
    }
}