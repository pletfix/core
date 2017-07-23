<?php

namespace Core\Tests\Exceptions;

use Core\Exceptions\QueryException;
use Core\Testing\TestCase;
use PDOException;

class QueryExceptionTest extends TestCase
{
    /**
     * @var QueryException
     */
    private $e;

    protected function setUp()
    {
        /** @noinspection SqlDialectInspection */
        $this->e = new QueryException(
            'SELECT * FROM table1 WHERE id = ?',
            [33],
            'SELECT * FROM table1 WHERE id = 33',
            new PDOException
        );
    }

    public function testGetStatement()
    {
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM table1 WHERE id = ?', $this->e->getStatement());
    }

    public function testGetBindings()
    {
        $this->assertSame([33], $this->e->getBindings());
    }

    public function testGetDump()
    {
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM table1 WHERE id = 33', $this->e->getDump());
    }
}