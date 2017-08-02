<?php

namespace Core\Tests\Services\PDOs\Schemas;

use Core\Services\PDOs\Postgres;
use Core\Services\PDOs\Schemas\PostgresSchema;
use InvalidArgumentException;

require_once 'SchemaTestCase.php';

class PostgresSchemaTest extends SchemaTestCase
{
    public static function setUpBeforeClass()
    {
        self::$fixturePath = __DIR__  . '/../../../_data/fixtures/postgres';
    }

    protected function setUp()
    {
        $this->db = $this->getMockBuilder(Postgres::class)
            ->setConstructorArgs([['database' => '~test', 'username' => 'pguser', 'password' => 'psss']])
            ->setMethods(['quote', 'version', 'exec', 'query', 'scalar', 'transaction'])
            ->getMockForAbstractClass();

        $this->db->expects($this->any())
            ->method('quote')
            ->willReturnCallback(function($value) {
                return "'$value'";
            });

        $this->db->expects($this->any())
            ->method('version')
            ->willReturn('9.6.3');

        $this->db->expects($this->any())
            ->method('transaction')
            ->willReturnCallback(function($callback) {
                $res = $callback($this->db);
                return $res;
            });

        $this->schema = new PostgresSchema($this->db);
    }

    public function testTables()
    {
        $this->expectsQueryFile('show_tables', 0);
        $this->expectsQueryFile('show_table_comments', 1);

        $this->assertSame([
            'table1' => ['name' => 'table1', 'collation' => null, 'comment' => 'A test table.'],
            'table2' => ['name' => 'table2', 'collation' => null, 'comment' => null],
        ], $this->schema->tables());
    }

    public function testColumns()
    {
        $this->expectsQueryFile('show_table1_column_comments', 0);
        $this->expectsQueryFile('show_table1_columns', 1);

        $actual = $this->schema->columns('table1');
        $expected = [
            'id'        => ['name' => 'id',        'type' => 'identity', 'size' => null, 'scale' => null, 'nullable' => false, 'default' => null,                'collation' => null, 'comment' => null],
            'small1'    => ['name' => 'small1',    'type' => 'smallint', 'size' => null, 'scale' => null, 'nullable' => true,  'default' => 33,                  'collation' => null, 'comment' => null],
            'integer1'  => ['name' => 'integer1',  'type' => 'integer',  'size' => null, 'scale' => null, 'nullable' => false, 'default' => -44,                 'collation' => null, 'comment' => 'I am cool!'],
            'unsigned1' => ['name' => 'unsigned1', 'type' => 'unsigned', 'size' => null, 'scale' => null, 'nullable' => true,  'default' => 55,                  'collation' => null, 'comment' => null],
            'bigint1'   => ['name' => 'bigint1',   'type' => 'bigint',   'size' => null, 'scale' => null, 'nullable' => true,  'default' => 66,                  'collation' => null, 'comment' => null],
            'numeric1'  => ['name' => 'numeric1',  'type' => 'numeric',  'size' => 10,   'scale' => 0,    'nullable' => false, 'default' => '1234567890',        'collation' => null, 'comment' => null],
            'numeric2'  => ['name' => 'numeric2',  'type' => 'numeric',  'size' => 4,    'scale' => 1,    'nullable' => true,  'default' => '123.4',             'collation' => null, 'comment' => null],
            'float1'    => ['name' => 'float1',    'type' => 'float',    'size' => null, 'scale' => null, 'nullable' => false, 'default' => 3.14,                'collation' => null, 'comment' => 'a rose is a rose'],
            'string1'   => ['name' => 'string1',   'type' => 'string',   'size' => 255,  'scale' => null, 'nullable' => false, 'default' => 'Panama',            'collation' => null, 'comment' => 'lola'],
            'string2'   => ['name' => 'string2',   'type' => 'string',   'size' => 50,   'scale' => null, 'nullable' => true,  'default' => null,                'collation' => 'de_DE', 'comment' => null],
            'text1'     => ['name' => 'text1',     'type' => 'text',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,                'collation' => 'de_DE', 'comment' => null],
            'guid1'     => ['name' => 'guid1',     'type' => 'guid',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,                'collation' => null, 'comment' => null],
            'binary1'   => ['name' => 'binary1',   'type' => 'binary',   'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,                'collation' => null, 'comment' => null],
            'binary2'   => ['name' => 'binary2',   'type' => 'binary',   'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,                'collation' => null, 'comment' => null],
            'blob1'     => ['name' => 'blob1',     'type' => 'blob',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,                'collation' => null, 'comment' => null],
            'boolean1'  => ['name' => 'boolean1',  'type' => 'boolean',  'size' => null, 'scale' => null, 'nullable' => true,  'default' => true,                'collation' => null, 'comment' => null],
            'date1'     => ['name' => 'date1',     'type' => 'date',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,                'collation' => null, 'comment' => null],
            'datetime1' => ['name' => 'datetime1', 'type' => 'datetime', 'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,                'collation' => null, 'comment' => null],
            'timestamp1'=> ['name' => 'timestamp1','type' => 'timestamp','size' => null, 'scale' => null, 'nullable' => false, 'default' => 'CURRENT_TIMESTAMP', 'collation' => null, 'comment' => null],
            'time1'     => ['name' => 'time1',     'type' => 'time',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,                'collation' => null, 'comment' => null],
            'array1'    => ['name' => 'array1',    'type' => 'array',    'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,                'collation' => null, 'comment' => 'a rose is a rose'],
            'json1'     => ['name' => 'json1',     'type' => 'json',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,                'collation' => null, 'comment' => null],
            'object1'   => ['name' => 'object1',   'type' => 'object',   'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,                'collation' => null, 'comment' => null],
        ];
        $this->assertCount(count($expected), $actual);
        foreach ($actual as $i => $item) {
            $this->assertSame($expected[$i], $actual[$i]);
        }
    }

    public function testIndexes()
    {
        $this->expectsQueryFile('show_table1_indexes');

        $actual = $this->schema->indexes('table1');

        $expected = [
            'table1_integer1_unique'       => ['name' => 'table1_integer1_unique',       'columns' => ['integer1'],           'unique' => true,  'primary' => false],
            'table1_pkey'                  => ['name' => 'table1_pkey',                  'columns' => ['id'],                 'unique' => true,  'primary' => true],
            'table1_string1_string2_index' => ['name' => 'table1_string1_string2_index', 'columns' => ['string1', 'string2'], 'unique' => false, 'primary' => false],
        ];

        $this->assertCount(count($expected), $actual);
        foreach ($actual as $i => $item) {
            $this->assertSame($expected[$i], $actual[$i]);
        }
    }

    public function testCreateTable()
    {
        $this->expectsExecFile('create_table1');

        $this->assertInstanceOf(PostgresSchema::class, $this->schema->createTable('table1', [
            'id'          => ['type' => 'identity'],
            'small1'      => ['type' => 'smallint',  'nullable' => true, 'default' => 33],
            'integer1'    => ['type' => 'integer',   'default' => -44, 'comment' => 'I am cool!'],
            'unsigned1'   => ['type' => 'unsigned',  'nullable' => true, 'default' => 55],
            'bigint1'     => ['type' => 'bigint',    'nullable' => true, 'default' => 66],
            'numeric1'    => ['type' => 'numeric',   'default' => '1234567890'],
            'numeric2'    => ['type' => 'numeric',   'size' => 4, 'scale' => 1, 'nullable' => true, 'default' => '123.4'],
            'float1'      => ['type' => 'float',     'default' => 3.14, 'comment' => 'a rose is a rose'],
            'string1'     => ['type' => 'string',    'default' => 'Panama', 'comment' => 'lola'],
            'string2'     => ['type' => 'string',    'size' => 50, 'nullable' => true, 'collation' => 'de_DE'],
            'text1'       => ['type' => 'text',      'nullable' => true, 'collation' => 'de_DE'],
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

    public function testCreateTableWithBigIdentity()
    {
        $this->expectsExecFile('create_table2');

        $this->assertInstanceOf(PostgresSchema::class, $this->schema->createTable('table2', [
            'id' => ['type' => 'bigidentity'],
        ]));
    }

    public function testCreateTemporaryTable()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec(['CREATE TEMPORARY TABLE "temp1" ("id" SERIAL PRIMARY KEY NOT NULL)']);

        $this->assertInstanceOf(PostgresSchema::class, $this->schema->createTable('temp1', [
            'id' => ['type' => 'identity'],
        ], ['temporary' => true]));
    }

    public function testCreateTableWithoutColumns()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->schema->createTable('table1', []);
    }

    public function testAddColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            'ALTER TABLE "table1" ADD COLUMN "array2" TEXT DEFAULT \'[7]\'',
            'COMMENT ON COLUMN "table1"."array2" IS \'(DC2Type:array)\'',
            ]);

        $this->assertInstanceOf(PostgresSchema::class, $this->schema->addColumn('table1', 'array2', [
            'type'    => 'array',
            'nullable' => true,
            'default' => '[7]',
        ]));
    }

    public function testRecreateTable()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('scalar')
            ->with('SELECT COUNT(*) FROM "table1"')
            ->willReturn(5);

        //$this->expectsQueryFile('show_table1_column_comments', 1);
        /** @noinspection SqlDialectInspection */
        $this->expectsQuery("
            SELECT c.column_name, pgd.description
            FROM pg_statio_all_tables AS st
            INNER JOIN pg_description pgd ON pgd.objoid = st.relid
            INNER JOIN information_schema.columns c ON (pgd.objsubid = c.ordinal_position AND c.table_schema = st.schemaname and c.table_name = st.relname)
            WHERE c.table_schema = ? 
            AND st.relname = ?
            AND pgd.description > ''
        ", ['public', 'table1'], [], 1);

        //$this->expectsQueryFile('show_table1_columns', 2);
        /** @noinspection SqlDialectInspection */
        $this->expectsQuery("
            SELECT column_name, 
              data_type,
              column_default,
              is_nullable,
              is_identity,
              character_maximum_length AS length,
              numeric_precision AS precision,
              numeric_precision_radix AS radix,
              numeric_scale AS scale,
              collation_name
            FROM information_schema.columns
            WHERE table_schema = ?
            AND table_name = ?
            ORDER by ordinal_position
            ", ['public', 'table1'],
            [[
                'column_name' => 'string1',
                'data_type' => 'character varying',
                'column_default' => null,
                'is_nullable' => 'NO',
                'is_identity' => 'NO',
                'length' => 255,
                'precision' => NULL,
                'radix' => NULL,
                'scale' => NULL,
                'collation_name' => NULL,
            ]],
            2
        );

        //$this->expectsQueryFile('show_table1_indexes', 3);
        /** @noinspection SqlDialectInspection */
        $this->expectsQuery("
            SELECT
              i.relname as index_name,
              a.attname as column_name,
              ix.indisunique,
              ix.indisprimary
            FROM pg_index AS ix
            INNER JOIN pg_class AS t ON t.oid = ix.indrelid
            INNER JOIN pg_class AS i ON i.oid = ix.indexrelid
            INNER JOIN pg_namespace AS n ON t.relnamespace = n.oid
            INNER JOIN pg_attribute AS a ON (a.attrelid = t.oid AND a.attnum = ANY(ix.indkey))
            WHERE t.relkind = 'r'
            AND n.nspname = ?
            AND t.relname = ?
            ORDER BY i.relname
            ", ['public', 'table1'],
            [['index_name' => 'table1_string1_index', 'column_name' => 'string1', 'indisunique' => false, 'indisprimary' => false]],
            3
        );

        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            'ALTER TABLE "table1" RENAME TO "t',
            'CREATE TABLE "table1" ("string1" VARCHAR(255) NOT NULL, "column1" VARCHAR(255) NOT NULL)',
            'INSERT INTO "table1" ("string1","column1") SELECT "string1", \'\' AS "column1" FROM t',
            'DROP TABLE t',
            'CREATE INDEX "table1_string1_index" ON "table1" ("string1")',
        ], false);

        $this->assertInstanceOf(PostgresSchema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'string',
        ]));
    }

    public function testAddIndex()
    {
        $this->expectsExecFile('create_table1_index');
        $this->assertInstanceOf(PostgresSchema::class, $this->schema->addIndex('table1', ['string1', 'string2']));
    }

    public function testAddPrimaryIndex()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec(['ALTER TABLE "table1" ADD PRIMARY KEY ("id")']);
        $this->assertInstanceOf(PostgresSchema::class, $this->schema->addIndex('table1', 'id', ['primary' => true]));
    }

    public function testDropIndex()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec(['DROP INDEX table1_column1_column2_index']);

        $this->assertInstanceOf(PostgresSchema::class, $this->schema->dropIndex('table1', ['column1', 'column2']));
    }

    public function testDropUniqueIndex()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec(['DROP INDEX table1_column1_unique']);

        $this->assertInstanceOf(PostgresSchema::class, $this->schema->dropIndex('table1', 'column1', ['unique' => true]));
    }

    public function testDropPrimaryIndex()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec(['ALTER TABLE "table1" DROP CONSTRAINT table1_pkey']);

        $this->assertInstanceOf(PostgresSchema::class, $this->schema->dropIndex('table1', null, ['primary' => true]));
    }

    public function testDropIndexWithName()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec(['DROP INDEX index1']);

        $this->assertInstanceOf(PostgresSchema::class, $this->schema->dropIndex('table1', null, ['name' => 'index1']));
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

}
