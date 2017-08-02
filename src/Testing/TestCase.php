<?php

namespace Core\Testing;

use Core\Services\DatabaseFactory;
use Core\Services\DI;
use PHPUnit_Framework_Assert as PHPUnit;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

/**
 * The methods invokePrivateMethod, getPrivateProperty, and setPrivateProperty are based from "Web Tips", a Blog for
 * developers (http://www.webtipblog.com/unit-testing-private-methods-and-properties-with-phpunit)
 * by Joe Sexton <joe@webtipblog.com>.
 */
class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * This method is called before the first test of this test class is run.
     */
//    public static function setUpBeforeClass()
//    {
//    }

    /**
     * This method is called after the last test of this test class is run.
     */
//    public static function tearDownAfterClass()
//    {
//    }

    /**
     * Define a SQLite database as default which is be stored in memory.
     *
     * The database is created purely in memory. The database ceases to exist as soon as the database connection is
     * closed. Every :memory: database is distinct from every other. So, opening database connections will create two
     * independent in-memory databases.
     *
     * @see http://www.sqlite.org/inmemorydb.html
     */
    public static function defineMemoryAsDefaultDatabase()
    {
        DI::getInstance()->get('config')->set('database', [
            'default' => '~test',
            'stores' => [
                '~test' => [
                    'driver'   => 'SQLite',
                    'database' => ':memory:',
                ],
            ],
        ]);

        DI::getInstance()->set('database-factory', DatabaseFactory::class, true);
    }

    /**
     * Setup the test environment.
     *
     * This method is called before a test is executed.
     */
//    protected function setUp()
//    {
//    }

    /**
     * Tears down the fixture.
     *
     * This method is called after a test is executed.
     */
//    protected function tearDown()
//    {
//    }

    /**
     * Assert whether the client is redirected to the given URL.
     *
     * @param string $url
     */
    public function assertRedirectedTo($url)
    {
        $response = DI::getInstance()->get('response');
        PHPUnit::assertTrue(in_array($response->getStatusCode(), [301, 302, 303]));
        PHPUnit::assertEquals($url, $response->getHeader('location'));
    }

    /**
     * Invoke a private or protected method.
     *
     * @param object $object
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function invokePrivateMethod($object, $method, array $arguments = [])
    {
        $reflector = new ReflectionClass($object);
        $method = $reflector->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $arguments);
    }

    /**
     * Get a private or protected property.
     *
     * @param object $object
     * @param string $property
     * @return mixed
     */
    public function getPrivateProperty($object, $property)
    {
        $reflector = new ReflectionClass($object);
        $property = $reflector->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * Set a private or protected property.
     *
     * @param object $object
     * @param string $property
     * @param mixed $value
     * @return $this
     */
    public function setPrivateProperty($object, $property, $value)
    {
        $reflector = new ReflectionClass($object);
        $property = $reflector->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);

        return $this;
    }
}