<?php

namespace Core\Tests\Services\PDOs\Schemas;

use Core\Services\PDOs\MySQL;
use Core\Services\PDOs\Schemas\MySQLSchema;
use InvalidArgumentException;

require_once 'SchemaTestCase.php';

class MySQLSchemaTest extends SchemaTestCase
{
    public static function setUpBeforeClass()
    {
        self::$fixturePath = __DIR__  . '/../../../_data/fixtures/mysql';
    }

    protected function setUp()
    {
        $this->db = $this->getMockBuilder(MySQL::class)
            ->setConstructorArgs([['database' => '~test']])
            ->setMethods(['quote', 'version', 'exec', 'query'])
            ->getMockForAbstractClass();

        $this->db->expects($this->any())
            ->method('quote')
            ->willReturnCallback(function($value) {
                return "'$value'";
            });

        $this->db->expects($this->any())
            ->method('version')
            ->willReturn('5.5.5-10.1.21-MariaDB');

        $this->schema = new MySQLSchema($this->db);
    }
    
    public function testTables()
    {
        $this->expectsQueryFile('show_tables');

        $this->assertSame([
            'table1' => ['name' => 'table1', 'collation' => 'utf8_unicode_ci', 'comment' => 'A test table.'],
            'table2' => ['name' => 'table2', 'collation' => 'utf8_unicode_ci', 'comment' => null],
        ], $this->schema->tables());
    }

    public function testColumns()
    {
        $this->expectsQueryFile('show_table1_columns');

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
            'string1'   => ['name' => 'string1',   'type' => 'string',   'size' => 255,  'scale' => null, 'nullable' => false, 'default' => 'Panama', 'collation' => 'utf8_unicode_ci',   'comment' => 'lola'],
            'string2'   => ['name' => 'string2',   'type' => 'string',   'size' => 50,   'scale' => null, 'nullable' => true,  'default' => null,     'collation' => 'latin1_general_cs', 'comment' => null],
            'text1'     => ['name' => 'text1',     'type' => 'text',     'size' => null, 'scale' => null, 'nullable' => true,  'default' => null,     'collation' => 'latin1_general_cs', 'comment' => null],
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

    public function testIndexes()
    {
        $this->expectsQueryFile('show_table1_indexes');

        $this->assertSame([
            'PRIMARY'                      => ['name' => 'PRIMARY',                      'columns' => ['id'],                 'unique' => true,  'primary' => true],
            'table1_integer1_unique'       => ['name' => 'table1_integer1_unique',       'columns' => ['integer1'],           'unique' => true,  'primary' => false],
            'table1_string1_string2_index' => ['name' => 'table1_string1_string2_index', 'columns' => ['string1', 'string2'], 'unique' => false, 'primary' => false],
        ], $this->schema->indexes('table1'));
    }

    public function testCreateTable()
    {
        $this->expectsExecFile('create_table1');

        $this->assertInstanceOf(MySQLSchema::class, $this->schema->createTable('table1', [
            'id'          => ['type' => 'identity'],
            'small1'      => ['type' => 'smallint',  'nullable' => true, 'default' => 33],
            'integer1'    => ['type' => 'integer',   'default' => -44, 'comment' => 'I am cool!'],
            'unsigned1'   => ['type' => 'unsigned',  'nullable' => true, 'default' => 55],
            'bigint1'     => ['type' => 'bigint',    'nullable' => true, 'default' => 66],
            'numeric1'    => ['type' => 'numeric',   'default' => '1234567890'],
            'numeric2'    => ['type' => 'numeric',   'size' => 4, 'scale' => 1, 'nullable' => true, 'default' => '123.4'],
            'float1'      => ['type' => 'float',     'default' => 3.14, 'comment' => 'a rose is a rose'],
            'string1'     => ['type' => 'string',    'default' => 'Panama', 'comment' => 'lola'],
            'string2'     => ['type' => 'string',    'size' => 50, 'nullable' => true, 'collation' => 'latin1_general_cs'],
            'text1'       => ['type' => 'text',      'nullable' => true, 'collation' => 'latin1_general_cs'],
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
        $this->expectsExec(['
            CREATE TEMPORARY TABLE `temp1` (`id` INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT) 
            DEFAULT CHARACTER SET latin1 COLLATE latin1_general_ci ENGINE = InnoDB
        ']);

        $this->assertInstanceOf(MySQLSchema::class, $this->schema->createTable('temp1', [
            'id' => ['type' => 'identity'],
        ], ['charset' => 'latin1', 'collation' => 'latin1_general_ci', 'engine' => 'InnoDB', 'temporary' => true]));
    }

    public function testCreateTableWithoutColumns()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->schema->createTable('table1', []);
    }

    public function testRenameTable()
    {
        /** @noinspection SqlDialectInspection */
        $this->expectsExec(['RENAME TABLE `table1` TO `table99`']);

        $this->assertInstanceOf(MySQLSchema::class, $this->schema->renameTable('table1', 'table99'));
    }

    public function testRenameColumn()
    {
        $this->expectsQueryFile('show_table1_columns');
        /** @noinspection SqlDialectInspection */
        $this->expectsExec(["ALTER TABLE `table1` CHANGE COLUMN `string1` `string99` VARCHAR(255) NOT NULL DEFAULT 'Panama' COLLATE utf8_unicode_ci COMMENT 'lola'"]);

        $this->assertInstanceOf(MySQLSchema::class, $this->schema->renameColumn('table1', 'string1', 'string99'));
    }

    public function testRenameNotExistColumn()
    {
        $this->expectsQueryFile('show_table1_columns');

        $this->expectException(InvalidArgumentException::class);
        $this->schema->renameColumn('table1', 'foo', 'bar');
    }

    public function testExtractFieldType()
    {
        $this->assertSame([null, null, null, null], $this->invokePrivateMethod($this->schema, 'extractFieldType', ['']));
        $this->assertSame(['INT', 10, 0, true], $this->invokePrivateMethod($this->schema, 'extractFieldType', ['int(10) unsigned']));
        $this->assertSame(['INT', 10, null, false], $this->invokePrivateMethod($this->schema, 'extractFieldType', ['int(10)']));
        $this->assertSame(['STRING', 10, null, false], $this->invokePrivateMethod($this->schema, 'extractFieldType', ['string(10)']));
        $this->assertSame(['UNSIGNED', null, null, false], $this->invokePrivateMethod($this->schema, 'extractFieldType', ['unsigned)']));
    }

    public function testConvertFieldType()
    {
        $this->assertSame('json', $this->invokePrivateMethod($this->schema, 'convertFieldType', ['json']));
        $this->assertSame('string', $this->invokePrivateMethod($this->schema, 'convertFieldType', ['wrong'])); // fallback type
    }

}