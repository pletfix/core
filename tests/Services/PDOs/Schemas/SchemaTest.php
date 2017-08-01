<?php

namespace Core\Tests\Services\PDOs\Schemas;

use Core\Services\Database;
use Core\Services\PDOs\Schemas\Schema;
use Core\Testing\TestCase;
use InvalidArgumentException;
use PHPUnit_Framework_MockObject_MockObject;

class SchemaTest extends TestCase
{
    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var Database|PHPUnit_Framework_MockObject_MockObject
     */
    private $db;

    protected function setUp()
    {
        $this->db = $this->getMockBuilder(Database::class)
            ->setConstructorArgs([['database' => '~test']])
            ->setMethods(['exec', 'quote'])
            ->getMockForAbstractClass();

        $this->db->expects($this->any())
            ->method('quote')
            ->willReturnCallback(function($value) {
                return "'$value'";
            });

        $this->schema = $this->getMockBuilder(Schema::class)
            ->setConstructorArgs([$this->db])
            ->setMethods(['tables', 'columns', 'indexes', 'createTable'])
            ->getMockForAbstractClass();
    }

    public function testDropTable()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('DROP TABLE "table1"')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->dropTable('table1'));
    }

    public function testRenameTable()
    {
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" RENAME TO "foo"')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->renameTable('table1', 'foo'));
    }

    public function testTruncateTable()
    {
        $this->db->expects($this->once())
            ->method('exec')
            ->with('TRUNCATE TABLE "table1"')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->truncateTable('table1'));
    }

    public function testAddIdentityColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'identity',
        ]));
    }

    public function testBigIdentityColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'bigidentity',
        ]));
    }

    public function testAddSmallintColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" SMALLINT NOT NULL')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'smallint',
        ]));
    }

    public function testAddIntegerColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" INT NOT NULL')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'integer',
        ]));
    }

    public function testAddUnsignedColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" INT UNSIGNED NOT NULL')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'unsigned',
        ]));
    }

    public function testAddBigintColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" BIGINT NOT NULL')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'bigint',
        ]));
    }

    public function testAddNumericColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" NUMERIC(5, 6) NOT NULL')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type'  => 'numeric',
            'size'  => 5,
            'scale' => 6,
        ]));
    }

    public function testAddFloatColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" DOUBLE NOT NULL DEFAULT 3.14')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type'      => 'float',
            'size'      => 4,
            'scale'     => 1,
            'default'   => 3.14
        ]));
    }

    public function testAddStringColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" VARCHAR(50) NOT NULL DEFAULT \'blub\'')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type'    => 'string',
            'size'    => 50,
            'default' => 'blub',
        ]));
    }

    public function testAddNullabledStringColumnWithoutSize()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" VARCHAR(255)')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type'     => 'string',
            'nullable' => true,
        ]));
    }

    public function testAddTextColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" TEXT NOT NULL')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'text',
        ]));
    }

    public function testAddNullabledTextColumnWithCollationAndCommand()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" TEXT COLLATE utf8_unicode_ci COMMENT \'a rose is a rose\'')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type'      => 'text',
            'nullable'  => true,
            'collation' => 'utf8_unicode_ci',
            'comment'   => 'a rose is a rose',
        ]));
    }

    public function testAddArrayColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" TEXT NOT NULL COMMENT \'a rose is a rose (DC2Type:array)\'')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type'    => 'array',
            'comment' => 'a rose is a rose',
        ]));
    }

    public function testAddJsonColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" TEXT NOT NULL COMMENT \'(DC2Type:json)\'')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'json',
        ]));
    }

    public function testAddObjectColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" TEXT NOT NULL COMMENT \'(DC2Type:object)\'')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'object',
        ]));
    }

    public function testAddGuidColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" UUID NOT NULL')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'guid',
        ]));
    }

    public function testBinaryColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" VARBINARY(3) NOT NULL')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'binary',
            'size' => 3
        ]));
    }

    public function testAddBlobColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" BLOB NOT NULL')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'blob',
        ]));
    }

    public function testAddBoolColumnWithTrueAsDefault()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" BOOLEAN NOT NULL DEFAULT 1')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type'    => 'boolean',
            'default' => true,
        ]));
    }

    public function testAddBoolColumnWithFalseAsDefault()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" BOOLEAN NOT NULL DEFAULT 0')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type'    => 'boolean',
            'default' => false,
        ]));
    }

    public function testAddDateColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" DATE NOT NULL')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'date',
        ]));
    }

    public function testAddDatetimeColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" DATETIME NOT NULL')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'datetime',
        ]));
    }

    public function testAddTimestampColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type'    => 'timestamp',
            'default' => 'CURRENT_TIMESTAMP',
        ]));
    }

    public function testAddTimesColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD COLUMN "column1" TIME NOT NULL')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addColumn('table1', 'column1', [
            'type' => 'time',
        ]));
    }

    public function testAddUnknownColumnTypee()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->schema->addColumn('table1', 'column1', [
            'type' => 'wrong',
        ]);
    }

    public function testDropColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" DROP COLUMN "column1"')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->dropColumn('table1', 'column1'));
    }

    public function testRenameColumn()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" RENAME COLUMN "column1" TO "foo"')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->renameColumn('table1', 'column1', 'foo'));
    }

    public function testAddIndex()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD INDEX "table1_column1_column2_index" ("column1", "column2")')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addIndex('table1', null, [
            'columns' => ['column1', 'column2'],
        ]));
    }

    public function testAddUniqueIndex()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD UNIQUE "table1_column1_unique" ("column1")')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addIndex('table1', null, [
            'columns' => ['column1'],
            'unique'  => true,
        ]));
    }

    public function testAddPrimaryIndex()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD PRIMARY KEY ("column1")')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addIndex('table1', null, [
            'columns' => ['column1'],
            'primary'  => true,
        ]));
    }

    public function testAddIndexWithName()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" ADD INDEX "index1" ("column1")')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->addIndex('table1', 'index1', [
            'columns' => ['column1'],
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
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" DROP INDEX "table1_column1_column2_index"')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->dropIndex('table1', null, [
            'columns' => ['column1', 'column2'],
        ]));
    }

    public function testDropUniqueIndex()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" DROP INDEX "table1_column1_unique"')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->dropIndex('table1', null, [
            'columns' => ['column1'],
            'unique'  => true,
        ]));
    }

    public function testDropPrimaryIndex()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" DROP PRIMARY KEY')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->dropIndex('table1', null, [
            'primary'  => true,
        ]));
    }

    public function testDropIndexWithName()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('ALTER TABLE "table1" DROP INDEX "index1"')
            ->willReturn(0);

        $this->assertInstanceOf(Schema::class, $this->schema->dropIndex('table1', 'index1', [
        ]));
    }

    public function testDropIndexWithoutColumnsAndWithoutName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->schema->dropIndex('table1', null, []);
    }

    public function testZero()
    {
        $this->assertSame(0, $this->schema->zero('smallint'));
        $this->assertSame(0, $this->schema->zero('integer'));
        $this->assertSame(0, $this->schema->zero('unsigned'));
        $this->assertSame(0, $this->schema->zero('bigint'));
        $this->assertSame(0, $this->schema->zero('numeric'));
        $this->assertSame(0, $this->schema->zero('float'));

        $this->assertSame('', $this->schema->zero('string'));
        $this->assertSame('', $this->schema->zero('text'));
        $this->assertSame('', $this->schema->zero('binary'));
        $this->assertSame('', $this->schema->zero('blob'));

        $this->assertSame('[]', $this->schema->zero('array'));
        $this->assertSame('""', $this->schema->zero('json'));
        $this->assertSame('null', $this->schema->zero('object'));

        $this->assertSame('00000000-0000-0000-0000-000000000000', $this->schema->zero('guid'));

        $this->assertSame(false, $this->schema->zero('boolean'));

        $this->assertSame('0000-00-00', $this->schema->zero('date'));
        $this->assertSame('00:00:00', $this->schema->zero('time'));
        $this->assertSame('0000-00-00 00:00:00', $this->schema->zero('datetime'));
        $this->assertSame('0000-00-00 00:00:00', $this->schema->zero('timestamp'));

        $this->expectException(InvalidArgumentException::class);
        $this->schema->zero('wrong');
    }

    public function testExtractTypeHintFromComment()
    {
        $this->assertSame([null, null], $this->invokePrivateMethod($this->schema, 'extractTypeHintFromComment', ['']));
        $this->assertSame(['json', null], $this->invokePrivateMethod($this->schema, 'extractTypeHintFromComment', ['(DC2Type:json)']));
        $this->assertSame([null, 'a rose is a rose'], $this->invokePrivateMethod($this->schema, 'extractTypeHintFromComment', ['a rose is a rose']));
        $this->assertSame(['array', 'a rose is a rose'], $this->invokePrivateMethod($this->schema, 'extractTypeHintFromComment', ['a rose is a rose (DC2Type:array)']));
    }

//    public function testExtractFieldType()
//    {
//        $this->assertSame(['INT', 10, 0, true], $this->invokePrivateMethod($this->schema, 'extractFieldType', ['int(10) unsigned']));
//        $this->assertSame(['INT', 10, null, false], $this->invokePrivateMethod($this->schema, 'extractFieldType', ['int(10)']));
//        $this->assertSame(['STRING', 10, null, false], $this->invokePrivateMethod($this->schema, 'extractFieldType', ['string(10)']));
//        $this->assertSame(['UNSIGNED', null, null, false], $this->invokePrivateMethod($this->schema, 'extractFieldType', ['unsigned)']));
//    }
}
