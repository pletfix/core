<?php

use Core\Services\Collection;
use Core\Testing\TestCase;

/**
 * CollectionTest
 */
class CollectionTest extends TestCase
{
    /**
     * Tests that it is possible to convert an array into a collection
     *
     * @return void
     */
    public function testArrayIsWrapped()
    {
        $items = [1, 2, 3];
        $collection = new Collection($items);
        $this->assertEquals($items, iterator_to_array($collection));
    }
}