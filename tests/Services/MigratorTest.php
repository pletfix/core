<?php

namespace Core\Tests\Services;

use Core\Exceptions\MigrationException;
use Core\Services\Migrator;
use Core\Testing\TestCase;

class MigratorTest extends TestCase
{
    /**
     * @var Migrator
     */
    private $m;

    public static function setUpBeforeClass()
    {
        self::defineMemoryAsDefaultDatabase();

        @mkdir(storage_path('~test'));

        // Create fake plugin migration
        $currPath = substr(__DIR__, strlen(base_path()) + 1);
        @mkdir(manifest_path('~plugins'));
        file_put_contents(manifest_path('~plugins/migrations.php'),
            "<?php return array ('20170204121100_CreateFooTable' => '$currPath/plugins/test/migrations/20170204121100_CreateFooTable.php');"
        );
    }

    public static function tearDownAfterClass()
    {
        database()->disconnect();

        self::removeMigrationFiles();
        @rmdir(storage_path('~test'));

        @unlink(manifest_path('~plugins/migrations.php'));
        @rmdir(manifest_path('~plugins'));
    }

    private static function removeMigrationFiles()
    {
        @unlink(storage_path('~test/20170204121101_UpdateBurTable.php'));
        @unlink(storage_path('~test/20170204121102_UpdateBuzTable.php'));
        @unlink(storage_path('~test/20170204121103_UpdateBuuTable.php'));
        @unlink(storage_path('~test/20170204121104_UpdateBarTable.php'));
        @unlink(storage_path('~test/20170204121105_UpdateBazTable.php'));
        @unlink(storage_path('~test/20170204121106_UpdateBaaTable.php'));
        @unlink(storage_path('~test/foobar.php'));
        @unlink(storage_path('~test/20170204121100_Foobar.php'));
        @unlink(storage_path('~test/20170204121101_Foobar.php'));
    }

    protected function setUp()
    {
        self::removeMigrationFiles();

        $this->m = new Migrator(null, storage_path('~test'), manifest_path('~plugins/migrations.php'));
    }

    public function testBase()
    {
        $db = database();

        // run the first migration
        copy(__DIR__ . '/migrations/20170204121101_UpdateBurTable.php', storage_path('~test/20170204121101_UpdateBurTable.php'));
        copy(__DIR__ . '/migrations/20170204121102_UpdateBuzTable.php', storage_path('~test/20170204121102_UpdateBuzTable.php'));
        $this->assertInstanceOf(Migrator::class, $this->m->run());
        $t = $db->table('_migrations')->all();
        $this->assertSame([
            ['id' => '1', 'name' => '20170204121100_CreateFooTable', 'batch' => '1'],
            ['id' => '2', 'name' => '20170204121101_UpdateBurTable', 'batch' => '1'],
            ['id' => '3', 'name' => '20170204121102_UpdateBuzTable', 'batch' => '1'],
        ], $t);
        $this->assertEquals(2, $db->table('foo')->count());

        // run the same migration ones more
        $this->m->run();
        $t = $db->table('_migrations')->all();
        $this->assertSame([
            ['id' => '1', 'name' => '20170204121100_CreateFooTable', 'batch' => '1'],
            ['id' => '2', 'name' => '20170204121101_UpdateBurTable', 'batch' => '1'],
            ['id' => '3', 'name' => '20170204121102_UpdateBuzTable', 'batch' => '1'],
        ], $t);
        $this->assertEquals(2, $db->table('foo')->count());

        // run the second migration
        copy(__DIR__ . '/migrations/20170204121103_UpdateBuuTable.php', storage_path('~test/20170204121103_UpdateBuuTable.php'));
        $this->m->run();
        $t = $db->table('_migrations')->all();
        $this->assertSame([
            ['id' => '1', 'name' => '20170204121100_CreateFooTable', 'batch' => '1'],
            ['id' => '2', 'name' => '20170204121101_UpdateBurTable', 'batch' => '1'],
            ['id' => '3', 'name' => '20170204121102_UpdateBuzTable', 'batch' => '1'],
            ['id' => '4', 'name' => '20170204121103_UpdateBuuTable', 'batch' => '2'],
        ], $t);
        $this->assertEquals(3, $db->table('foo')->count());

        // run the third migration
        copy(__DIR__ . '/migrations/20170204121104_UpdateBarTable.php', storage_path('~test/20170204121104_UpdateBarTable.php'));
        copy(__DIR__ . '/migrations/20170204121105_UpdateBazTable.php', storage_path('~test/20170204121105_UpdateBazTable.php'));
        $this->m->run();
        $t = $db->table('_migrations')->all();
        $this->assertSame([
            ['id' => '1', 'name' => '20170204121100_CreateFooTable', 'batch' => '1'],
            ['id' => '2', 'name' => '20170204121101_UpdateBurTable', 'batch' => '1'],
            ['id' => '3', 'name' => '20170204121102_UpdateBuzTable', 'batch' => '1'],
            ['id' => '4', 'name' => '20170204121103_UpdateBuuTable', 'batch' => '2'],
            ['id' => '5', 'name' => '20170204121104_UpdateBarTable', 'batch' => '3'],
            ['id' => '6', 'name' => '20170204121105_UpdateBazTable', 'batch' => '3'],
        ], $t);
        $this->assertEquals(5, $db->table('foo')->count());

        // run the fourth migration
        copy(__DIR__ . '/migrations/20170204121106_UpdateBaaTable.php', storage_path('~test/20170204121106_UpdateBaaTable.php'));
        $this->m->run();
        $t = $db->table('_migrations')->all();
        $this->assertSame([
            ['id' => '1', 'name' => '20170204121100_CreateFooTable', 'batch' => '1'],
            ['id' => '2', 'name' => '20170204121101_UpdateBurTable', 'batch' => '1'],
            ['id' => '3', 'name' => '20170204121102_UpdateBuzTable', 'batch' => '1'],
            ['id' => '4', 'name' => '20170204121103_UpdateBuuTable', 'batch' => '2'],
            ['id' => '5', 'name' => '20170204121104_UpdateBarTable', 'batch' => '3'],
            ['id' => '6', 'name' => '20170204121105_UpdateBazTable', 'batch' => '3'],
            ['id' => '7', 'name' => '20170204121106_UpdateBaaTable', 'batch' => '4'],
        ], $t);
        $this->assertEquals(6, $db->table('foo')->count());

        // rollback batch 4
        $this->assertInstanceOf(Migrator::class, $this->m->rollback());
        $t = $db->table('_migrations')->all();
        $this->assertSame([
            ['id' => '1', 'name' => '20170204121100_CreateFooTable', 'batch' => '1'],
            ['id' => '2', 'name' => '20170204121101_UpdateBurTable', 'batch' => '1'],
            ['id' => '3', 'name' => '20170204121102_UpdateBuzTable', 'batch' => '1'],
            ['id' => '4', 'name' => '20170204121103_UpdateBuuTable', 'batch' => '2'],
            ['id' => '5', 'name' => '20170204121104_UpdateBarTable', 'batch' => '3'],
            ['id' => '6', 'name' => '20170204121105_UpdateBazTable', 'batch' => '3'],
        ], $t);
        $this->assertEquals(5, $db->table('foo')->count());

        // rollback batch 3
        $this->m->rollback();
        $t = $db->table('_migrations')->all();
        $this->assertSame([
            ['id' => '1', 'name' => '20170204121100_CreateFooTable', 'batch' => '1'],
            ['id' => '2', 'name' => '20170204121101_UpdateBurTable', 'batch' => '1'],
            ['id' => '3', 'name' => '20170204121102_UpdateBuzTable', 'batch' => '1'],
            ['id' => '4', 'name' => '20170204121103_UpdateBuuTable', 'batch' => '2'],
        ], $t);
        $this->assertEquals(3, $db->table('foo')->count());

        // reset
        $this->assertInstanceOf(Migrator::class, $this->m->reset());
        $t = $db->table('_migrations')->all();
        $this->assertSame([], $t);
    }

    public function testInvalidMigrationFileName()
    {
        file_put_contents(storage_path('~test/foobar.php'), "<?php class Foobar {}");
        $this->expectException(MigrationException::class);
        $this->m->run();
    }

    public function testClassDoesNotMatchWithFileName()
    {
        file_put_contents(storage_path('~test/20170204121100_Foobar.php'), "<?php class NotFoobar {}");
        $this->expectException(MigrationException::class);
        $this->m->run();
    }

    public function testClassDoesNotImplementsMigrationContract()
    {
        file_put_contents(storage_path('~test/20170204121101_Foobar.php'), "<?php class Foobar {}");
        $this->expectException(MigrationException::class);
        $this->m->run();
    }

}
