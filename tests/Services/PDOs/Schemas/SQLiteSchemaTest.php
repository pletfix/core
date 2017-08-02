<?php

namespace Core\Tests\Services\PDOs\Schemas;

use Core\Services\PDOs\SQLite;
use Core\Services\PDOs\Schemas\SQLiteSchema;
use InvalidArgumentException;

require_once 'SchemaTestCase.php';

class SQLiteSchemaTest extends SchemaTestCase
{
    public static function setUpBeforeClass()
    {
        self::$fixturePath = __DIR__  . '/../../../_data/fixtures/sqlite';
    }

    protected function setUp()
    {
        $this->db = $this->getMockBuilder(SQLite::class)
            ->setConstructorArgs([['database' => ':memory:']])
            ->setMethods(['quote', 'version', 'exec', 'query', 'scalar', 'transaction'])
            ->getMockForAbstractClass();

        $this->db->expects($this->any())
            ->method('quote')
            ->willReturnCallback(function($value) {
                return "'$value'";
            });

        $this->db->expects($this->any())
            ->method('version')
            ->willReturn('3.7.17');

        $this->db->expects($this->any())
            ->method('transaction')
            ->willReturnCallback(function($callback) {
                $res = $callback($this->db);
                return $res;
            });

        $this->schema = new SQLiteSchema($this->db);
    }

    public function testTables()
    {
        $this->expectsQueryFile('show_tables', 0);
        $this->expectsQueryFile('show_table_comments', 1);

        $this->assertSame([
            'table1' => ['name' => 'table1', 'collation' => 'BINARY', 'comment' => 'A test table.'],
            'table2' => ['name' => 'table2', 'collation' => 'BINARY', 'comment' => null],
        ], $this->schema->tables());
    }

    public function testColumns()
    {
        $this->expectsQueryFile('show_table1_column_comments', 0);

        /** @noinspection PhpIncludeInspection */
        $fixture = include self::$fixturePath . '/get_table1_sql.php';
        $this->db->expects($this->once())
            ->method('scalar')
            ->with($fixture['query'], $fixture['bindings'])
            ->willReturn($fixture['result']);

        $this->expectsQueryFile('show_table1_columns', 3);

        $actual = $this->schema->columns('table1');

        $expected = [
            'id'        => ['name' => 'id',        'type' => 'identity', 'size' => null, 'scale' => null, 'nullable' => false, 'default' => null,     'collation' => null,            'comment' => null],
            'small1'    => ['name' => 'small1',    'type' => 'smallint', 'size' => null, 'scale' => null, 'nullable' => true,  'default' => 33,       'collation' => null,            'comment' => null],
            'integer1'  => ['name' => 'integer1',  'type' => 'integer',  'size' => null, 'scale' => null, 'nullable' => false, 'default' => -44,      'collation' => null,            'comment' => 'I am cool!'],
            'unsigned1' => ['name' => 'unsigned1', 'type' => 'unsigned', 'size' => null, 'scale' => null, 'nullable' => true,  'default' => 55,       'collation' => null,            'comment' => null],
            'bigint1'   => ['name' => 'bigint1',   'type' => 'bigint',   'size' => null, 'scale' => null, 'nullable' => true,  'default' => 66,       'collation' => null,            'comment' => null],
            'numeric1'  => ['name' => 'numeric1',  'type' => 'numeric',  'size' => 10,   'scale' => 0,    'nullable' => false, 'default' => '1234567890', 'collation' => null,        'comment' => null],
            'numeric2'  => ['name' => 'numeric2',  'type' => 'numeric',  'size' => 4,    'scale' => 1,    'nullable' => true,  'default' => '123.4',  'collation' => null,            'comment' => null],
            'float1'    => ['name' => 'float1',    'type' => 'float',    'size' => null, 'scale' => null, 'nullable' => false, 'default' => 3.14,     'collation' => null,            'comment' => 'a rose is a rose'],
            'string1'   => ['name' => 'string1',   'type' => 'string',   'size' => 255,  'scale' => null, 'nullable' => false, 'default' => 'Panama', 'collation' => 'NOCASE',        'comment' => 'lola'],
            'string2'   => ['name' => 'string2',   'type' => 'string',   'size' => 50,   'scale' => null, 'nullable' => true,  'default' => null,     'collation' => 'BINARY',        'comment' => null],
            'text1'     => ['name' => 'text1',     'type' => 'text',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => 'BINARY',        'comment' => null],
            'guid1'     => ['name' => 'guid1',     'type' => 'guid',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,            'comment' => null],
            'binary1'   => ['name' => 'binary1',   'type' => 'binary',   'size' => 2,    'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,            'comment' => null],
            'binary2'   => ['name' => 'binary2',   'type' => 'binary',   'size' => 3,    'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,            'comment' => null],
            'blob1'     => ['name' => 'blob1',     'type' => 'blob',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,            'comment' => null],
            'boolean1'  => ['name' => 'boolean1',  'type' => 'boolean',  'size' => null, 'scale' => null, 'nullable' => true,  'default' => true,     'collation' => null,            'comment' => null],
            'date1'     => ['name' => 'date1',     'type' => 'date',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,            'comment' => null],
            'datetime1' => ['name' => 'datetime1', 'type' => 'datetime', 'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,            'comment' => null],
            'timestamp1'=> ['name' => 'timestamp1','type' => 'timestamp','size' => null, 'scale' => null, 'nullable' => false, 'default' => 'CURRENT_TIMESTAMP', 'collation' => null, 'comment' => null],
            'time1'     => ['name' => 'time1',     'type' => 'time',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,            'comment' => null],
            'array1'    => ['name' => 'array1',    'type' => 'array',    'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,            'comment' => 'a rose is a rose'],
            'json1'     => ['name' => 'json1',     'type' => 'json',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,            'comment' => null],
            'object1'   => ['name' => 'object1',   'type' => 'object',   'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,            'comment' => null],
        ];
        $this->assertCount(count($expected), $actual);
        foreach ($actual as $i => $item) {
            $this->assertSame($expected[$i], $actual[$i]);
        }
    }

    public function testIndexes()
    {
        $this->expectsQueryFile('show_table1_pks', 1);
        $this->expectsQueryFile('show_table1_indexes', 3);
        $this->expectsQueryFile('show_table1_index1', 5);
        $this->expectsQueryFile('show_table1_index2', 7);

        $actual = $this->schema->indexes('table1');

        $expected = [
            'PRIMARY'                      => ['name' => 'PRIMARY',                      'columns' => ['id'],                 'unique' => true,  'primary' => true],
            'table1_integer1_unique'       => ['name' => 'table1_integer1_unique',       'columns' => ['integer1'],           'unique' => true,  'primary' => false],
            'table1_string1_string2_index' => ['name' => 'table1_string1_string2_index', 'columns' => ['string1', 'string2'], 'unique' => false, 'primary' => false],
        ];
        $this->assertCount(count($expected), $actual);
        foreach ($actual as $i => $item) {
            $this->assertSame($expected[$i], $actual[$i]);
        }
    }

    public function testGetPrimaryIndex()
    {
        $this->expectsQueryFile('show_table3_pks', 1);
        $this->expectsQueryFile('show_table3_indexes', 3);
        $this->expectsQueryFile('show_table3_index1', 5);

        $actual = $this->schema->indexes('table3');

        $expected = [
            'sqlite_autoindex_table3_1' => ['name' => 'sqlite_autoindex_table3_1', 'columns' => ['string1', 'string2'], 'unique' => true, 'primary' => true],
        ];
        $this->assertCount(count($expected), $actual);
        foreach ($actual as $i => $item) {
            $this->assertSame($expected[$i], $actual[$i]);
        }
    }

    public function testCreateTable()
    {
        $this->expectsExecFile('create_table1');

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->createTable('table1', [
            'id'          => ['type' => 'identity'],
            'small1'      => ['type' => 'smallint',  'nullable' => true, 'default' => 33],
            'integer1'    => ['type' => 'integer',   'default' => -44, 'comment' => 'I am cool!'],
            'unsigned1'   => ['type' => 'unsigned',  'nullable' => true, 'default' => 55],
            'bigint1'     => ['type' => 'bigint',    'nullable' => true, 'default' => 66],
            'numeric1'    => ['type' => 'numeric',   'default' => '1234567890'],
            'numeric2'    => ['type' => 'numeric',   'size' => 4, 'scale' => 1, 'nullable' => true, 'default' => '123.4'],
            'float1'      => ['type' => 'float',     'default' => 3.14, 'comment' => 'a rose is a rose'],
            'string1'     => ['type' => 'string',    'default' => 'Panama', 'comment' => 'lola'],
            'string2'     => ['type' => 'string',    'size' => 50, 'nullable' => true, 'collation' => 'BINARY'],
            'text1'       => ['type' => 'text',      'nullable' => true, 'collation' => 'BINARY'],
            'guid1'       => ['type' => 'guid',      'nullable' => true],
            'binary1'     => ['type' => 'binary',    'nullable' => true],
            'binary2'     => ['type' => 'binary',    'size' => 3, 'nullable' => true],
            'blob1'       => ['type' => 'blob',      'nullable' => true],
            'boolean1'    => ['type' => 'boolean',   'nullable' => true, 'default' => true],
            'date1'       => ['type' => 'date',      'nullable' => true],
            'datetime1'   => ['type' => 'datetime',  'nullable' => true],
            'timestamp1'  => ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP'],
            'time1'       => ['type' => 'time',      'nullable' => true],
            'array1'      => ['type' => 'array',     'nullable' => true, 'comment' => 'a rose is a rose'],
            'json1'       => ['type' => 'json',      'nullable' => true],
            'object1'     => ['type' => 'object',    'nullable' => true],
        ], ['comment' => 'A test table.']));
    }

    public function testCreateTemporaryTable()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            'CREATE TEMPORARY TABLE "temp1" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL)',
            'DELETE FROM _comments WHERE table_name = \'temp1\'',
        ]);

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->createTable('temp1', [
            'id' => ['type' => 'identity'],
        ], ['temporary' => true]));
    }

    public function testCreateTableWithoutColumns()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->schema->createTable('table1', []);
    }

    public function testDropTable()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            'DROP TABLE "table1"',
            'DELETE FROM _comments WHERE table_name = \'table1\'',
        ]);

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->dropTable('table1'));
    }

    public function testRenameTable()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            'ALTER TABLE "table1" RENAME TO "table99"',
            'UPDATE _comments SET table_name = \'table99\' WHERE table_name = \'table1\'',
        ]);

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->renameTable('table1', 'table99'));
    }

    public function testTruncateTable()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            'DELETE FROM "table1"',
            'DELETE FROM sqlite_sequence WHERE name = \'table1\'',
        ]);

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->truncateTable('table1'));
    }

    private function expectGetColumnsAndIndexesOfTable3()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsQuery('SELECT column_name, content FROM _comments WHERE table_name = ? AND column_name IS NOT NULL', ['table3'], [], 0);

        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('scalar')
            ->with('SELECT sql FROM sqlite_master WHERE type = \'table\' AND name = ?', ['table3'])
            ->willReturn('CREATE TABLE "table3" ("string1" VARCHAR(255) NOT NULL COLLATE NOCASE, "string2" VARCHAR(255) NOT NULL COLLATE NOCASE)');

        /** @noinspection PhpParamsInspection */
        $this->expectsQuery("PRAGMA TABLE_INFO('table3')", [], [
            ['cid' => '0', 'name' => 'string1', 'type' => 'VARCHAR(255)', 'notnull' => '1', 'dflt_value' => NULL, 'pk' => '0'],
            ['cid' => '1', 'name' => 'string2', 'type' => 'VARCHAR(255)', 'notnull' => '1', 'dflt_value' => NULL, 'pk' => '0'],
        ], 3);

        /** @noinspection PhpParamsInspection */
        $this->expectsQuery("PRAGMA TABLE_INFO('table3')", [], [
            ['cid' => '0', 'name' => 'string1', 'type' => 'VARCHAR(255)', 'notnull' => '1', 'dflt_value' => NULL, 'pk' => '0'],
            ['cid' => '1', 'name' => 'string2', 'type' => 'VARCHAR(255)', 'notnull' => '1', 'dflt_value' => NULL, 'pk' => '0'],
        ], 5);

        /** @noinspection SqlDialectInspection */
        $this->expectsQuery("PRAGMA INDEX_LIST('table3')", [], [
            ['seq' => '0', 'name' => 'table3_string1_string2_unique', 'unique' => '1'],
        ], 7);

        /** @noinspection SqlDialectInspection */
        $this->expectsQuery("PRAGMA INDEX_INFO('table3_string1_string2_unique')", [], [
            ['seqno' => '0', 'cid' => '8', 'name' => 'string1'],
            ['seqno' => '0', 'cid' => '8', 'name' => 'string2'],
        ], 9);
    }

    public function testAddColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            "ALTER TABLE \"table1\" ADD COLUMN \"integer2\" INT",
            "DELETE FROM _comments WHERE table_name = 'table1' AND column_name = 'integer2'",
        ]);

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->addColumn('table1', 'integer2', [
            'type'     => 'integer',
            'nullable' => true,
        ]));
    }

    public function testAddNotNullableColumnWithoutDefault() // the table have to been recreated
    {
        $this->expectGetColumnsAndIndexesOfTable3();

        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            'ALTER TABLE "table3" RENAME TO t',
            'CREATE TABLE "table3" ("string1" VARCHAR(255) NOT NULL COLLATE NOCASE, "string2" VARCHAR(255) NOT NULL COLLATE NOCASE, "integer2" INT NOT NULL)',
            'DELETE FROM _comments WHERE table_name = \'table3\'',
            'INSERT INTO "table3" ("string1","string2","integer2") SELECT "string1","string2", \'0\' AS "integer2" FROM t',
            'DROP TABLE t',
            'CREATE UNIQUE INDEX "table3_string1_string2_unique" ON "table3" ("string1","string2")',
        ], false);

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->addColumn('table3', 'integer2', [
            'type' => 'integer',
        ]));
    }

    public function testDropColumn()
    {
        $this->expectGetColumnsAndIndexesOfTable3();

        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            'ALTER TABLE "table3" RENAME TO t',
            'CREATE TABLE "table3" ("string1" VARCHAR(255) NOT NULL COLLATE NOCASE)',
            'DELETE FROM _comments WHERE table_name = \'table3\'',
            'INSERT INTO "table3" ("string1") SELECT "string1" FROM t',
            'DROP TABLE t',
        ], false);

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->dropColumn('table3', 'string2'));
    }

    public function testRenameColumn()
    {
        $this->expectGetColumnsAndIndexesOfTable3();

        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            'ALTER TABLE "table3" RENAME TO t',
            'CREATE TABLE "table3" ("string1" VARCHAR(255) NOT NULL COLLATE NOCASE, "string99" VARCHAR(255) NOT NULL COLLATE NOCASE)',
            'DELETE FROM _comments WHERE table_name = \'table3\'',
            'INSERT INTO "table3" ("string1","string99") SELECT "string1","string2" FROM t',
            'DROP TABLE t',
            'CREATE UNIQUE INDEX "table3_string1_string99_unique" ON "table3" ("string1","string99")',
        ], false);

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->renameColumn('table3', 'string2', 'string99'));
    }

    public function testAddColumnWithTypeHint()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            "ALTER TABLE \"table1\" ADD COLUMN \"array2\" TEXT DEFAULT '[7]'",
            "INSERT OR REPLACE INTO _comments (table_name, column_name, content) VALUES ('table1', 'array2', '(DC2Type:array)')",
        ]);

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->addColumn('table1', 'array2', [
            'type'    => 'array',
            'nullable' => true,
            'default' => '[7]',
        ]));
    }

    public function testAddIndex()
    {
        $this->expectsExecFile('create_table1_index');

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->addIndex('table1', ['string1', 'string2']));
    }

    public function testAddPrimaryIndex()
    {
        $this->expectGetColumnsAndIndexesOfTable3();

        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            'ALTER TABLE "table3" RENAME TO t',
            'CREATE TABLE "table3" ("string1" VARCHAR(255) NOT NULL COLLATE NOCASE, "string2" VARCHAR(255) NOT NULL COLLATE NOCASE, PRIMARY KEY ("string1","string3"))',
            'INSERT INTO "table3" ("string1","string2") SELECT "string1","string2" FROM t',
            'DROP TABLE t',
            'CREATE UNIQUE INDEX "table3_string1_string2_unique" ON "table3" ("string1","string2")',
        ], false);

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->addIndex('table3', ['string1', 'string3'], ['primary' => true]));
    }

    public function testDropIndex()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec(['DROP INDEX table1_column1_column2_index']);

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->dropIndex('table1', ['column1', 'column2']));
    }

    public function testDropUniqueIndex()
    {
        $this->expectGetColumnsAndIndexesOfTable3();

        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            'ALTER TABLE "table3" RENAME TO t',
            'CREATE TABLE "table3" ("string1" VARCHAR(255) NOT NULL COLLATE NOCASE, "string2" VARCHAR(255) NOT NULL COLLATE NOCASE',
            'DELETE FROM _comments WHERE table_name = \'table3\'',
            'INSERT INTO "table3" ("string1","string2") SELECT "string1","string2" FROM t',
            'DROP TABLE t',
        ], false);

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->dropIndex('table3', ['string1', 'string2'], ['unique' => true]));
    }

    public function testDropPrimaryIndex()
    {
        $this->expectGetColumnsAndIndexesOfTable3();

        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            'ALTER TABLE "table3" RENAME TO t',
            'CREATE TABLE "table3" ("string1" VARCHAR(255) NOT NULL COLLATE NOCASE, "string2" VARCHAR(255) NOT NULL COLLATE NOCASE',
            'DELETE FROM _comments WHERE table_name = \'table3\'',
            'INSERT INTO "table3" ("string1","string2") SELECT "string1","string2" FROM t',
            'DROP TABLE t',
            'CREATE UNIQUE INDEX "table3_string1_string2_unique" ON "table3" ("string1","string2")',
        ], false);

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->dropIndex('table3', null, ['primary' => true]));
    }

    public function testDropIndexWithName()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec(['DROP INDEX index1']);

        $this->assertInstanceOf(SQLiteSchema::class, $this->schema->dropIndex('table1', null, ['name' => 'index1']));
    }

    public function testDropIndexWithoutColumnsAndWithoutName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->schema->dropIndex('table1', null);
    }

    public function testConvertFieldType()
    {
        $this->assertSame('string', $this->invokePrivateMethod($this->schema, 'convertFieldType', ['wrong'])); // fallback type
    }

    public function testExtractFieldType()
    {
        $this->assertSame([null, null, null, null], $this->invokePrivateMethod($this->schema, 'extractFieldType', ['']));
    }
}
