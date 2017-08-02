<?php

namespace Core\Tests\Services;

use Core\Exceptions\QueryException;
use Core\Services\Database;
use Core\Services\PDOs\Builders\Contracts\Builder;
use Core\Services\PDOs\Schemas\Contracts\Schema;
use Core\Services\PDOs\SQLite;
use Core\Testing\TestCase;
use DateTime;
use Generator;
use stdClass;

class DatabaseTest extends TestCase
{
    /**
     * @var Database
     */
    private $db;

    protected function setUp()
    {
        $this->db = new SQLite(['driver' => 'SQLite', 'database' => ':memory:']);
    }

    protected function tearDown()
    {
        $this->db->disconnect();
    }

    private function createFooTable(array $data = [])
    {
        /** @noinspection SqlDialectInspection */
        $this->db->exec('
            CREATE TABLE foo (
                id INTEGER PRIMARY KEY NOT NULL,
                name VARCHAR(255) NOT NULL
            );
        ');

        foreach ($data as $name) {
            /** @noinspection SqlDialectInspection */
            $this->db->exec('INSERT INTO foo (name) VALUES (?);', [$name]);
        }
    }

    public function testConstruct()
    {
        $this->db = new SQLite(['driver' => 'SQLite', 'database' => ':memory:', 'dateformat' => 'Y-m-d H:i:s']);
        $this->assertInstanceOf(Database::class, $this->db);
    }

    public function testConfig()
    {
        $this->assertSame(['database' => ':memory:', 'driver'   => 'SQLite'], $this->db->config());
        $this->assertSame('SQLite', $this->db->config('driver'));
    }

    public function testSchema()
    {
        $this->assertInstanceOf(Schema::class, $this->db->schema());
    }

    public function testBuilder()
    {
        $this->assertInstanceOf(Builder::class, $this->db->builder());
    }

    public function testTable()
    {
        $t = $this->db->table('foo');
        $this->assertInstanceOf(Builder::class, $t);
        $this->assertSame('"foo"', $t->getTable());
    }

    public function testVersion()
    {
        $this->assertTrue(is_string($this->db->version()), 'get version');
    }

    public function testQuote()
    {
        $this->assertSame("'foo'", $this->db->quote('foo'));
    }

    public function testQuoteName()
    {
        $this->assertSame('"foo""bar"', $this->db->quoteName('foo"bar'));
    }

    public function testConnectAndReconnectAndDisconnect()
    {
        $this->assertInstanceOf(Database::class, $this->db->connect());
        $this->assertInstanceOf(Database::class, $this->db->connect());
        $this->assertInstanceOf(Database::class, $this->db->reconnect());
        $this->assertInstanceOf(Database::class, $this->db->disconnect());
        $this->assertInstanceOf(Database::class, $this->db->disconnect());

        $this->db = new SQLite(['driver' => 'SQLite', 'database' => ':memory:', 'persistent' => true]);
        $this->assertInstanceOf(Database::class, $this->db->connect());
        $this->assertInstanceOf(Database::class, $this->db->disconnect());
    }

//    public function testErrorCode()
//    {
//        $this->assertSame('00000', $this->db->errorCode());
//    }
//
//    public function testErrorInfo()
//    {
//        $this->assertSame([0 => '00000', 1 => null, 2 => null], $this->db->errorInfo());
//    }

    public function testDump()
    {
        $sql1 = 'SQL * FROM foo WHERE id=? OR name LIKE ?';
        $sql2 = 'SQL * FROM foo WHERE id=:id OR name LIKE :name';
        $expected = "SQL * FROM foo WHERE id=5 OR name LIKE '%bar%'";

        $this->assertSame($expected, $this->db->dump($sql1, [5, '%bar%'], true));
        $this->assertSame($expected, $this->db->dump($sql2, [':id' => 5, ':name' => '%bar%'], true));

        ob_start();
        try {
            $this->db->dump($sql1, [5, '%bar%']);
        }
        finally {
            $out = ob_get_clean();
        }
        $this->assertSame($expected, $out);
    }

    public function testSupportsSavepoints()
    {
        $this->assertTrue($this->db->supportsSavepoints());
    }

    public function testTransactionAndCommit()
    {
        $this->createFooTable();

        $this->assertSame(0, $this->db->transactionLevel());

        $result = $this->db->transaction(function (Database $db) {
            $this->assertSame(1, $db->transactionLevel());

            /** @noinspection SqlDialectInspection */
            $db->exec('INSERT INTO foo (name) VALUES (\'Tiger\');');

            $db->transaction(function (Database $db) {
                $this->assertSame(2, $db->transactionLevel());

                /** @noinspection SqlDialectInspection */
                $db->exec('INSERT INTO foo (name) VALUES (\'Monkey\');');

                /** @noinspection SqlDialectInspection */
                $this->assertCount(2, $db->query('SELECT * FROM foo'));

                /** @noinspection SqlDialectInspection */
                $db->exec('INSERT INTO foo (name) VALUES (\'Dog\');');
            });

            $this->assertCount(3, $db->query('SELECT * FROM foo'));

            /** @noinspection SqlDialectInspection */
            return $db->scalar('SELECT name FROM foo WHERE id=?', [1]);
        });

        $this->assertSame(0, $this->db->transactionLevel());
        $this->assertSame('Tiger', $result);
    }

    public function testTransactionAndRollback()
    {
        $this->createFooTable();
        try {
            $this->db->transaction(function (Database $db) {
                /** @noinspection SqlDialectInspection */
                $db->exec('INSERT INTO foo (name) VALUES (\'Tiger\');');
                try {
                    $db->transaction(function (Database $db) {
                        /** @noinspection SqlDialectInspection */
                        $db->exec('INSERT INTO foo (name) VALUES (\'Monkey\');');
                        /** @noinspection SqlDialectInspection */
                        $db->exec('INSERT INTO foo (name) VALUES (\'Dog\');');

                        /** @noinspection SqlDialectInspection */
                        $this->assertCount(3, $db->query('SELECT * FROM foo'));

                        $this->expectException(QueryException::class);
                        /** @noinspection SqlDialectInspection */
                        $db->exec('INSERT INTO foo (wrong) VALUES (\'Spider\');'); // throw an exception -> rollback
                    });
                }
                finally {
                    $this->assertCount(1, $db->query('SELECT * FROM foo'));
                }
            });
        }
        finally {
            $this->assertCount(0, $this->db->query('SELECT * FROM foo'));
        }
    }

    public function testQueryWithoutBindings()
    {
        $this->createFooTable(['Tiger', 'Monkey']);

        /** @noinspection SqlDialectInspection */
        $this->assertSame([
            ['id' => '1', 'name' => 'Tiger'],
            ['id' => '2', 'name' => 'Monkey'],
        ], $this->db->query('SELECT * FROM foo'));
    }

    public function testQueryWithBindings()
    {
        $this->createFooTable(['Tiger', 'Monkey']);

        /** @noinspection SqlDialectInspection */
        $this->assertSame([
            ['id' => '1', 'name' => 'Tiger'],
        ], $this->db->query('SELECT * FROM foo WHERE name=? OR name=? OR name=?', [new DateTime(), false, 'Tiger']));
    }

    public function testInvalidQueryWithoutBindings()
    {
        $this->expectException(QueryException::class);

        /** @noinspection SqlDialectInspection */
        $this->db->query('SELECT * FROM foo'); // table not exists
    }

    public function testInvalidQueryWithBindings()
    {
        $this->expectException(QueryException::class);

        /** @noinspection SqlDialectInspection */
        $this->db->query('SELECT * FROM foo WHERE id=?', [1]); // table not exists
    }

    public function testNullAsQueryWithoutBindings()
    {
        $this->expectException(QueryException::class);

        /** @noinspection SqlDialectInspection */
        $this->db->query(null); // $this->pdo->query() returns false
    }

    public function testNullAsQueryWithBindings()
    {
        $this->expectException(QueryException::class);

        /** @noinspection SqlDialectInspection */
        $this->db->query(null, [1]); // $this->pdo->query() returns false
    }

    public function testQueryFetchObjects()
    {
        $this->createFooTable(['Tiger', 'Monkey']);

        /** @noinspection SqlDialectInspection */
        $this->assertEquals([
            (object)['id' => '1', 'name' => 'Tiger'],
            (object)['id' => '2', 'name' => 'Monkey'],
        ], $this->db->query('SELECT * FROM foo', [], stdClass::class));
    }

    public function testSingle()
    {
        $this->createFooTable(['Tiger', 'Monkey']);

        /** @noinspection SqlDialectInspection */
        $this->assertSame(['id' => '1', 'name' => 'Tiger'], $this->db->single('SELECT * FROM foo WHERE id=?', [1]));
    }

    public function testSingleFetchObjects()
    {
        $this->createFooTable(['Tiger', 'Monkey']);

        /** @noinspection SqlDialectInspection */
        $this->assertEquals((object)['id' => '1', 'name' => 'Tiger'], $this->db->single('SELECT * FROM foo WHERE id=?', [1], stdClass::class));
    }

    public function testScalar()
    {
        $this->createFooTable(['Tiger', 'Monkey']);

        /** @noinspection SqlDialectInspection */
        $this->assertSame('Tiger', $this->db->scalar('SELECT name FROM foo WHERE id=?', [1]));
    }

    public function testCursor()
    {
        $this->createFooTable(['Tiger', 'Monkey']);

        /** @noinspection SqlDialectInspection */
        $cursor = $this->db->cursor('SELECT * FROM foo');
        $this->assertInstanceOf(Generator::class, $cursor);

        $result = [];
        foreach ($cursor as $row) {
            $result[] = $row;
        }

        $this->assertSame([
            ['id' => '1', 'name' => 'Tiger'],
            ['id' => '2', 'name' => 'Monkey'],
        ], $result);
    }

    public function testCursorFetchObjects()
    {
        $this->createFooTable(['Tiger', 'Monkey']);

        /** @noinspection SqlDialectInspection */
        $cursor = $this->db->cursor('SELECT * FROM foo', [], stdClass::class);
        $this->assertInstanceOf(Generator::class, $cursor);

        $result = [];
        foreach ($cursor as $row) {
            $result[] = $row;
        }

        $this->assertEquals([
            (object)['id' => '1', 'name' => 'Tiger'],
            (object)['id' => '2', 'name' => 'Monkey'],
        ], $result);
    }

    public function testExec()
    {
        $this->createFooTable(['Tiger', 'Monkey']);

        /** @noinspection SqlDialectInspection */
        $this->assertSame(2, $this->db->exec('UPDATE foo SET name=?', ['Dog']), 'execute query with bindungs');

        /** @noinspection SqlDialectInspection */
        $this->assertSame(2, $this->db->exec('DELETE FROM foo'), 'execute query without bindungs');
    }

    public function testLastInsertId()
    {
        $this->createFooTable(['Tiger', 'Monkey']);

        /** @noinspection SqlDialectInspection */
        $this->assertSame(2, $this->db->lastInsertId());
    }
}
