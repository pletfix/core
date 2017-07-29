<?php

namespace Core\Tests\Services\PDOs\Schemas;

use Core\Services\PDOs\SqlServer;
use Core\Services\PDOs\Schemas\SqlServerSchema;
use InvalidArgumentException;

require_once 'SchemaTestCase.php';

class SqlServerSchemaTest extends SchemaTestCase
{
    public static function setUpBeforeClass()
    {
        self::$fixturePath = __DIR__  . '/../../../_data/fixtures/sqlserver';
    }

    protected function setUp()
    {
        $this->db = $this->getMockBuilder(SqlServer::class)
            ->setConstructorArgs([['database' => '~test', 'username' => 'sa', 'password' => 'psss']])
            ->setMethods(['quote', 'exec', 'query', 'scalar', 'transaction'])
            ->getMockForAbstractClass();

        $this->db->expects($this->any())
            ->method('quote')
            ->willReturnCallback(function($value) {
                return "'$value'";
            });

        $this->db->expects($this->any())
            ->method('transaction')
            ->willReturnCallback(function($callback) {
                $res = $callback($this->db);
                return $res;
            });

        $this->schema = new SqlServerSchema($this->db);
    }

    public function testTables()
    {
        /** @noinspection PhpIncludeInspection */
        $fixture = include __DIR__  . '/../../../_data/fixtures/sqlserver/get_default_collation.php';
        $this->db->expects($this->once())->method('scalar')->with($fixture['query'])->willReturn($fixture['result']);
        $this->expectsQueryFile('show_tables', 1);
        $this->expectsQueryFile('show_table_comments', 2);

        $this->assertSame([
            'table1' => ['name' => 'table1', 'collation' => 'Latin1_General_CI_AS', 'comment' => 'A test table.'],
            'table2' => ['name' => 'table2', 'collation' => 'Latin1_General_CI_AS', 'comment' => null],
        ], $this->schema->tables());
    }

    public function testColumns()
    {
        $this->expectsQueryFile('show_table1_column_comments', 0);
        $this->expectsQueryFile('show_table1_column_collations', 1);
        $this->expectsQueryFile('show_table1_columns', 3);

        $actual = $this->schema->columns('table1');

        $expected = [
            'id'        => ['name' => 'id',        'type' => 'identity', 'size' => null, 'scale' => null, 'nullable' => false, 'default' => null,     'collation' => null,                'comment' => null],
            'small1'    => ['name' => 'small1',    'type' => 'smallint', 'size' => null, 'scale' => null, 'nullable' => true,  'default' => 33,       'collation' => null,                'comment' => null],
            'integer1'  => ['name' => 'integer1',  'type' => 'integer',  'size' => null, 'scale' => null, 'nullable' => false, 'default' => -44,      'collation' => null,                'comment' => 'I am cool!'],
            'unsigned1' => ['name' => 'unsigned1', 'type' => 'unsigned', 'size' => null, 'scale' => null, 'nullable' => true,  'default' => 55,       'collation' => null,                'comment' => null],
            'bigint1'   => ['name' => 'bigint1',   'type' => 'bigint',   'size' => null, 'scale' => null, 'nullable' => true,  'default' => 66,       'collation' => null,                'comment' => null],
            'numeric1'  => ['name' => 'numeric1',  'type' => 'numeric',  'size' => 10,   'scale' => 0,    'nullable' => false, 'default' => '1234567890', 'collation' => null,            'comment' => null],
            'numeric2'  => ['name' => 'numeric2',  'type' => 'numeric',  'size' => 4,    'scale' => 1,    'nullable' => true,  'default' => '123.4',  'collation' => null,                'comment' => null],
            'float1'    => ['name' => 'float1',    'type' => 'float',    'size' => null, 'scale' => null, 'nullable' => false, 'default' => 3.14,     'collation' => null,                'comment' => 'a rose is a rose'],
            'string1'   => ['name' => 'string1',   'type' => 'string',   'size' => 255,  'scale' => null, 'nullable' => false, 'default' => 'Panama', 'collation' => 'Latin1_General_CI_AS', 'comment' => 'lola'],
            'string2'   => ['name' => 'string2',   'type' => 'string',   'size' => 50,   'scale' => null, 'nullable' => true,  'default' => null,     'collation' => 'Latin1_General_CS_AS', 'comment' => null],
            'text1'     => ['name' => 'text1',     'type' => 'text',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => 'Latin1_General_CS_AS', 'comment' => null],
            'guid1'     => ['name' => 'guid1',     'type' => 'guid',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,                'comment' => null],
            'binary1'   => ['name' => 'binary1',   'type' => 'binary',   'size' => 2,    'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,                'comment' => null],
            'binary2'   => ['name' => 'binary2',   'type' => 'binary',   'size' => 3,    'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,                'comment' => null],
            'blob1'     => ['name' => 'blob1',     'type' => 'blob',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,                'comment' => null],
            'boolean1'  => ['name' => 'boolean1',  'type' => 'boolean',  'size' => null, 'scale' => null, 'nullable' => true,  'default' => true,     'collation' => null,                'comment' => null],
            'date1'     => ['name' => 'date1',     'type' => 'date',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,                'comment' => null],
            'datetime1' => ['name' => 'datetime1', 'type' => 'datetime', 'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,                'comment' => null],
            'timestamp1'=> ['name' => 'timestamp1','type' => 'timestamp','size' => null, 'scale' => null, 'nullable' => false, 'default' => 'CURRENT_TIMESTAMP', 'collation' => null,     'comment' => null],
            'time1'     => ['name' => 'time1',     'type' => 'time',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,                'comment' => null],
            'array1'    => ['name' => 'array1',    'type' => 'array',    'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,                'comment' => 'a rose is a rose'],
            'json1'     => ['name' => 'json1',     'type' => 'json',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,                'comment' => null],
            'object1'   => ['name' => 'object1',   'type' => 'object',   'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => null,                'comment' => null],
        ];
        $this->assertCount(count($expected), $actual);
        foreach ($actual as $i => $item) {
            $this->assertSame($expected[$i], $actual[$i]);
        }
    }

    public function testColumnsWithBigIdentity()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsQuery("
            SELECT c.name AS column_name, CAST(cd.value AS VARCHAR(255)) AS description
            FROM sysobjects AS t
            INNER JOIN syscolumns AS c ON c.id = t.id
            INNER JOIN sys.extended_properties AS cd ON (cd.major_id = c.id AND cd.minor_id = c.colid)
            WHERE cd.name = 'MS_Description'
            AND t.type = 'u'
            AND t.name = ?
        ", ['table1'], [], 0);

        /** @noinspection SqlDialectInspection */
        $this->expectsQuery("
            SELECT c.name, c.collation_name 
            FROM sys.columns AS c
            JOIN sys.objects AS t ON c.object_id = t.object_id
            WHERE t.type = 'U' 
            AND t.name = ?
            AND c.collation_name IS NOT NULL
        ", ['table1'], [], 1);

        /** @noinspection SqlDialectInspection */
        $this->expectsQuery(
            "EXEC sp_columns 'table1'", [], [[
                'TABLE_QUALIFIER' => 'pletfix',
                'TABLE_OWNER' => 'dbo',
                'TABLE_NAME' => 'table1',
                'COLUMN_NAME' => 'id',
                'DATA_TYPE' => '-5',
                'TYPE_NAME' => 'bigint identity',
                'PRECISION' => '19',
                'LENGTH' => '8',
                'SCALE' => '0',
                'RADIX' => '10',
                'NULLABLE' => '0',
                'REMARKS' => null,
                'COLUMN_DEF' => null,
                'SQL_DATA_TYPE' => '-5',
                'SQL_DATETIME_SUB' => null,
                'CHAR_OCTET_LENGTH' => null,
                'ORDINAL_POSITION' => '1',
                'IS_NULLABLE' => 'NO',
                'SS_DATA_TYPE' => '63'
            ]],
            3
        );

        $this->assertSame([
            'id' => ['name' => 'id', 'type' => 'bigidentity', 'size' => null, 'scale' => null, 'nullable' => false, 'default' => null,                'collation' => null, 'comment' => null],
        ], $this->schema->columns('table1'));
    }

    public function testIndexes()
    {
        $this->expectsQueryFile('show_table1_indexes');

        $actual = $this->schema->indexes('table1');

        $expected = [
            'table1_integer1_unique'       => ['name' => 'table1_integer1_unique',       'columns' => ['integer1'],           'unique' => true,  'primary' => false],
            'PK__table1__3213E83F3C69FB99' => ['name' => 'PK__table1__3213E83F3C69FB99', 'columns' => ['id'],                 'unique' => true,  'primary' => true],
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

        $this->assertInstanceOf(SqlServerSchema::class, $this->schema->createTable('table1', [
            'id'          => ['type' => 'identity'],
            'small1'      => ['type' => 'smallint',  'nullable' => true, 'default' => 33],
            'integer1'    => ['type' => 'integer',   'default' => -44, 'comment' => 'I am cool!'],
            'unsigned1'   => ['type' => 'unsigned',  'nullable' => true, 'default' => 55],
            'bigint1'     => ['type' => 'bigint',    'nullable' => true, 'default' => 66],
            'numeric1'    => ['type' => 'numeric',   'default' => '1234567890'],
            'numeric2'    => ['type' => 'numeric',   'size' => 4, 'scale' => 1, 'nullable' => true, 'default' => '123.4'],
            'float1'      => ['type' => 'float',     'default' => 3.14, 'comment' => 'a rose is a rose'],
            'string1'     => ['type' => 'string',    'default' => 'Panama', 'comment' => 'lola'],
            'string2'     => ['type' => 'string',    'size' => 50, 'nullable' => true, 'collation' => 'Latin1_General_CS_AS'],
            'text1'       => ['type' => 'text',      'nullable' => true, 'collation' => 'Latin1_General_CS_AS'],
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

        $this->assertInstanceOf(SqlServerSchema::class, $this->schema->createTable('table2', [
            'id' => ['type' => 'bigidentity'],
        ]));
    }

    public function testAddColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            "ALTER TABLE [table1] ADD [array2] TEXT DEFAULT '[7]' NULL",
            "EXEC sp_addextendedproperty 'MS_Description', '(DC2Type:array)', 'SCHEMA', 'dbo', 'TABLE', 'table1', 'COLUMN', 'array2'",
        ]);

        $this->assertInstanceOf(SqlServerSchema::class, $this->schema->addColumn('table1', 'array2', [
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
            ->with('SELECT COUNT(*) FROM [table1]')
            ->willReturn(5);

        //$this->expectsQueryFile('show_table1_column_comments', 1);
        /** @noinspection SqlDialectInspection */
        $this->expectsQuery("
            SELECT c.name AS column_name, CAST(cd.value AS VARCHAR(255)) AS description
            FROM sysobjects AS t
            INNER JOIN syscolumns AS c ON c.id = t.id
            INNER JOIN sys.extended_properties AS cd ON (cd.major_id = c.id AND cd.minor_id = c.colid)
            WHERE cd.name = 'MS_Description'
            AND t.type = 'u'
            AND t.name = ?
        ", ['table1'], [], 1);

        //$this->expectsQueryFile('show_table1_column_collations', 2);
        /** @noinspection SqlDialectInspection */
        $this->expectsQuery("
            SELECT c.name, c.collation_name 
            FROM sys.columns AS c
            JOIN sys.objects AS t ON c.object_id = t.object_id
            WHERE t.type = 'U' 
            AND t.name = ?
            AND c.collation_name IS NOT NULL
            ", ['table1'],
            [['name' => 'string1', 'collation_name' => 'Latin1_General_CI_AS']],
            2
        );
        //$this->expectsQueryFile('show_table1_columns', 4);
        /** @noinspection SqlDialectInspection */
        $this->expectsQuery("EXEC sp_columns 'table1'", [], [[
                'TABLE_QUALIFIER' => 'pletfix',
                'TABLE_OWNER' => 'dbo',
                'TABLE_NAME' => 'table1',
                'COLUMN_NAME' => 'string1',
                'DATA_TYPE' => '-9',
                'TYPE_NAME' => 'nvarchar',
                'PRECISION' => '255',
                'LENGTH' => '510',
                'SCALE' => NULL,
                'RADIX' => NULL,
                'NULLABLE' => '0',
                'REMARKS' => NULL,
                'COLUMN_DEF' => NULL,
                'SQL_DATA_TYPE' => '-9',
                'SQL_DATETIME_SUB' => NULL,
                'CHAR_OCTET_LENGTH' => '510',
                'ORDINAL_POSITION' => '9',
                'IS_NULLABLE' => 'NO',
                'SS_DATA_TYPE' => '39',
            ]],
            4
        );

        //$this->expectsQueryFile('show_table1_indexes', 3);
        /** @noinspection SqlDialectInspection */
        $this->expectsQuery("
            SELECT 
                i.name, 
                co.[name] AS column_name,
                i.is_unique, 
                i.is_primary_key
            FROM sys.indexes i 
            INNER JOIN sys.index_columns ic ON ic.object_id = i.object_id  AND ic.index_id = i.index_id
            INNER JOIN sys.columns co ON co.object_id = i.object_id AND co.column_id = ic.column_id
            INNER JOIN sys.tables t ON t.object_id = i.object_id
            WHERE t.is_ms_shipped = 0 AND t.[name] = ?
            ORDER BY i.[name], ic.is_included_column, ic.key_ordinal
            ", ['table1'],
            [[
                'name' => 'table1_string1_index',
                'column_name' => 'string1',
                'is_unique' => '0',
                'is_primary_key' => '0',
            ]],
            5
        );

        /** @noinspection SqlDialectInspection */
        $this->expectsExec([
            "EXEC sp_rename 'table1', 't",
            'CREATE TABLE [table1] ([string1] NVARCHAR(255) COLLATE Latin1_General_CI_AS NOT NULL, [column1] NVARCHAR(255) NOT NULL)',
            'INSERT INTO [table1] ([string1],[column1]) SELECT [string1], \'\' AS [column1] FROM t',
            'DROP TABLE t',
            'CREATE INDEX table1_string1_index ON [table1] ([string1])',
        ], false);

        $this->assertInstanceOf(SqlServerSchema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'string',
        ]));
    }

    public function testRenameColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec(["EXEC sp_rename 'table1.string1', 'string99', 'COLUMN'"]);

        $this->assertInstanceOf(SqlServerSchema::class, $this->schema->renameColumn('table1', 'string1', 'string99'));
    }

    public function testAddIndex()
    {
        $this->expectsExecFile('create_table1_index');

        $this->assertInstanceOf(SqlServerSchema::class, $this->schema->addIndex('table1', null, [
            'columns' => ['string1', 'string2'],
        ]));
    }

    public function testAddPrimaryIndex()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec(['ALTER TABLE [table1] ADD PRIMARY KEY ([id])']);

        $this->assertInstanceOf(SqlServerSchema::class, $this->schema->addIndex('table1', null, [
            'columns' => ['id'],
            'primary'  => true,
        ]));
    }

    public function testAddIndexWithoutColumns()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->schema->addIndex('table1', 'index1', []);
    }

    public function testDropIndex()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec(['DROP INDEX table1_column1_column2_index ON [table1]']);

        $this->assertInstanceOf(SqlServerSchema::class, $this->schema->dropIndex('table1', null, [
            'columns' => ['column1', 'column2'],
        ]));
    }

    public function testDropUniqueIndex()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec(['DROP INDEX table1_column1_unique ON [table1]']);

        $this->assertInstanceOf(SqlServerSchema::class, $this->schema->dropIndex('table1', null, [
            'columns' => ['column1'],
            'unique'  => true,
        ]));
    }

    public function testDropPrimaryIndex()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('scalar')
            ->with("
                SELECT name  
                FROM sys.key_constraints  
                WHERE type = 'PK' 
                AND OBJECT_NAME(parent_object_id) = ?;
            ", ['table1'])
            ->willReturn('PK__table1__3213E83F3C69FB99');

        /** @noinspection SqlDialectInspection */
        $this->expectsExec(['ALTER TABLE [table1] DROP CONSTRAINT PK__table1__3213E83F3C69FB99']);

        $this->assertInstanceOf(SqlServerSchema::class, $this->schema->dropIndex('table1', null, [
            'primary'  => true,
        ]));
    }

    public function testDropIndexWithName()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec(['DROP INDEX index1 ON [table1]']);

        $this->assertInstanceOf(SqlServerSchema::class, $this->schema->dropIndex('table1', 'index1', [
        ]));
    }

    public function testDropIndexWithoutColumnsAndWithoutName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->schema->dropIndex('table1', null, []);
    }

    public function testConvertFieldType()
    {
        $this->assertSame('string', $this->invokePrivateMethod($this->schema, 'convertFieldType', ['wrong'])); // fallback type
    }

    public function testZero()
    {
        $this->assertSame('0001-01-01', $this->schema->zero('date'));
        $this->assertSame('00:00:00', $this->schema->zero('time'));
        $this->assertSame('0001-01-01 00:00:00', $this->schema->zero('datetime'));
        $this->assertSame('0001-01-01 00:00:00', $this->schema->zero('timestamp'));
    }
}
