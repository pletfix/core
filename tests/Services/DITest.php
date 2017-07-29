<?php

namespace Core\Tests\Services;

use Core\Services\DI;
use Core\Testing\TestCase;
use RuntimeException;

class DITest extends TestCase
{
    /**
     * @var DI
     */
    private $di;

    protected function setUp()
    {
        $this->di = DI::getInstance();
    }

    public function testBase()
    {
        // callable, not shared
        $i = 1;
        /** @noinspection PhpUnusedParameterInspection */
        $this->assertInstanceOf(DI::class, $this->di->set('callable-not-shared', function($a, $b) use (&$i) { return 2 * 3 * (++$i); }));
        $this->assertSame(12, $this->di->get('callable-not-shared', [2, 3]));
        $this->assertSame(18, $this->di->get('callable-not-shared', [2, 3]));

        // callable, shared
        $i = 1;
        $this->di->set('callable-shared', function() use (&$i) { return ++$i; }, true);
        $this->assertSame(2, $this->di->get('callable-shared'));
        $this->assertSame(2, $this->di->get('callable-shared'));

        // object, not shared
        $obj = new TestObject;
        $this->di->set('object-not-shared', $obj);
        $obj = $this->di->get('object-not-shared', [2, 3]);
        $this->assertInstanceOf(TestObject::class, $obj);
        $this->assertSame(1, $obj->i);
        $this->assertSame(2, $obj->a);
        $this->assertSame(3, $obj->b);
        $obj->inc();
        $obj = $this->di->get('object-not-shared', [2, 3]);
        $this->assertSame(1, $obj->i);

        // object, shared
        $obj = new TestObject;
        $this->di->set('object-shared', $obj, true);
        $obj = $this->di->get('object-shared');
        $this->assertInstanceOf(TestObject::class, $obj);
        $obj->inc();
        $obj = $this->di->get('object-shared');
        $this->assertSame(2, $obj->i);

        // string, not shared
        $this->di->set('string-not-shared', TestObject::class);
        $obj = $this->di->get('string-not-shared', [2, 3]);
        $this->assertInstanceOf(TestObject::class, $obj);
        $this->assertSame(1, $obj->i);
        $this->assertSame(2, $obj->a);
        $this->assertSame(3, $obj->b);
        $obj->inc();
        $obj = $this->di->get('string-not-shared', [2, 3]);
        $this->assertSame(1, $obj->i);

        // string, shared
        $this->di->set('string-shared', TestObject::class, true);
        $obj = $this->di->get('string-shared');
        $this->assertInstanceOf(TestObject::class, $obj);
        $obj->inc();
        $obj = $this->di->get('string-shared');
        $this->assertSame(2, $obj->i);
    }

    public function testGetUnknownService()
    {
        $this->expectException(RuntimeException::class);
        $this->di->get('~flsdjflsajf');
    }

    public function testInvalidService()
    {
        $this->di->set('invalid-service', 4711);
        $this->expectException(RuntimeException::class);
        $this->di->get('invalid-service');
    }
}

class TestObject
{
    public $a;
    public $b;
    public $i = 1;

    public function __construct($a = null, $b = null)
    {
        $this->a = $a;
        $this->b = $b;
    }

    public function inc()
    {
        $this->i++;
    }
}

