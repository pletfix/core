<?php

namespace Core\Tests\Testing;

use Core\Testing\TestCase;

class TestCaseTest extends TestCase
{
    public function testGetAndSetPrivateMethod()
    {
        $foo = new Foo();

        $value = $this->invokePrivateMethod($foo, 'privateMethod', [66]);
        $this->assertSame(66, $value);

        $value = $this->invokePrivateMethod($foo, 'protectedMethod', [77]);
        $this->assertSame(77, $value);
    }

    public function testGetAndSetPrivateProperty()
    {
        $foo = new Foo();

        $this->setPrivateProperty($foo, 'privateProperty', 88);
        $value = $this->getPrivateProperty($foo, 'privateProperty');
        $this->assertSame(88, $value);

        $this->setPrivateProperty($foo, 'protectedProperty', 99);
        $value = $this->getPrivateProperty($foo, 'protectedProperty');
        $this->assertSame(99, $value);
    }
}

class Foo
{
    private /** @noinspection PhpUnusedPrivateFieldInspection */ $privateProperty;

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function privateMethod($param)
    {
        return $param;
    }

    protected $protectedProperty;

    protected function protectedMethod($param)
    {
        return $param;
    }
}