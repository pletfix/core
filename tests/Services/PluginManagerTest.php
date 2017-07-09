<?php

namespace Core\Tests\Services;

use Core\Exceptions\PluginException;
use Core\Services\AssetManager;
use Core\Services\DI;
use Core\Services\PluginManager;
use Core\Testing\TestCase;
use InvalidArgumentException;

class PluginManagerTest extends TestCase
{
    public static function tearDownAfterClass()
    {
        $manifestPath = manifest_path('~plugins');
        if (file_exists($manifestPath)) {
            @unlink($manifestPath . '/assets.php');
            @unlink($manifestPath . '/bootstrap.php');
            @unlink($manifestPath . '/classes.php');
            @unlink($manifestPath . '/commands.php');
            @unlink($manifestPath . '/languages.php');
            @unlink($manifestPath . '/migrations.php');
            @unlink($manifestPath . '/packages.php');
            @unlink($manifestPath . '/routes.php');
            @unlink($manifestPath . '/services.php');
            @unlink($manifestPath . '/views.php');
            @rmdir($manifestPath);
        }

        @unlink(config_path('~test.php'));

        @unlink(public_path('~test/dummy.txt'));
        @rmdir(public_path('~test'));
    }

    protected function setUp()
    {
//        self::tearDownAfterClass();
        $manifestPath = manifest_path('~plugins');
        if (file_exists($manifestPath)) {
            @unlink($manifestPath . '/assets.php');
            @unlink($manifestPath . '/bootstrap.php');
            @unlink($manifestPath . '/classes.php');
            @unlink($manifestPath . '/commands.php');
            @unlink($manifestPath . '/languages.php');
            @unlink($manifestPath . '/migrations.php');
            @unlink($manifestPath . '/packages.php');
            @unlink($manifestPath . '/routes.php');
            @unlink($manifestPath . '/services.php');
            @unlink($manifestPath . '/views.php');
            @rmdir($manifestPath);
        }
    }

    public function testPackageNameIsInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        new PluginManager('pletfix');
    }

    public function testPackageNotFound()
    {
        $this->expectException(InvalidArgumentException::class);
        new PluginManager('pletfix/foo');
    }

    public function testRegisterAndUnregister()
    {
        $manifestPath = manifest_path('~plugins');
        $m = new PluginManager('pletfix/~test', __DIR__ . '/plugins/test', $manifestPath);
        $this->assertInstanceOf(PluginManager::class, $m);

        $assetManager = $this->getMockBuilder(AssetManager::class)->setMethods(['publish', 'remove'])->getMock();
        $assetManager->expects($this->once())->method('publish')->with(null, true, '~test')->willReturnSelf();
        $assetManager->expects($this->once())->method('remove')->with(null, '~test')->willReturnSelf();
        DI::getInstance()->set('asset-manager', $assetManager, true);

        include 'plugins/test/src/Commands/DummyCommand.php.fake';

        @unlink(config_path('~test.php'));
        @unlink(public_path('~test/dummy.txt'));

        $currPath = substr(__DIR__, strlen(base_path()) + 1);

        // register

        $this->assertInstanceOf(PluginManager::class, $m->register());
        $this->assertTrue($m->isRegistered());

        // asset
        $this->assertFileExists($manifestPath . '/assets.php');
        /** @noinspection PhpIncludeInspection */
        $data = include $manifestPath . '/assets.php';
        $this->assertSame([
            '~test' => [
                'js/~dummy.js' => [$currPath  . '/plugins/test/assets/js/dummy.js']
            ]
        ], $data);

        // bootstraps
        $this->assertFileExists($manifestPath . '/bootstrap.php');
        $data = file_get_contents($manifestPath . '/bootstrap.php');
        $this->assertStringEndsWith('(new Pletfix\Test\Bootstraps\DummyBootstrap)->boot();', trim($data));

        // classes
        $this->assertFileExists($manifestPath . '/classes.php');
        /** @noinspection PhpIncludeInspection */
        $data = include $manifestPath . '/classes.php';
        $this->assertSame([
            'Auth'        => ['DummyAuth' => 'Pletfix\\Test\\Auth\\DummyAuth'],
            'Controllers' => ['DummyController' => 'Pletfix\\Test\\Controllers\\DummyController'],
            'Middleware'  => ['Dummy' => 'Pletfix\\Test\\Middleware\\Dummy']
        ], $data);

        // commands
        $this->assertFileExists($manifestPath . '/commands.php');
        /** @noinspection PhpIncludeInspection */
        $data = include $manifestPath . '/commands.php';
        $this->assertSame([
            'dummy:run' => [
                'class'       => 'Pletfix\\Test\\Commands\\DummyCommand',
                'name'        => 'dummy:run',
                'description' => 'Dummy Command.'
            ]
        ], $data);

        // config
        $this->assertFileExists(config_path('~test.php'));
        /** @noinspection PhpIncludeInspection */
        $data = include config_path('~test.php');
        $this->assertSame(['foo' => 'bar'], $data);

        // languages
        $this->assertFileExists($manifestPath . '/languages.php');
        /** @noinspection PhpIncludeInspection */
        $data = include $manifestPath . '/languages.php';
        $this->assertSame([
            'de' => ['~test' => $currPath . '/plugins/test/lang/de.php'],
            'en' => ['~test' => $currPath . '/plugins/test/lang/en.php']
        ], $data);

        // migrations
        $this->assertFileExists($manifestPath . '/migrations.php');
        /** @noinspection PhpIncludeInspection */
        $data = include $manifestPath . '/migrations.php';
        $this->assertSame([
            '20170204121100_CreateFooTable' => $currPath . '/plugins/test/migrations/20170204121100_CreateFooTable.php'
        ], $data);

        // packages
        $this->assertFileExists($manifestPath . '/packages.php');
        /** @noinspection PhpIncludeInspection */
        $data = include $manifestPath . '/packages.php';
        $this->assertSame([
            'pletfix/~test' => $currPath . '/plugins/test'
        ], $data);

        // public
        $this->assertFileExists(public_path('~test/dummy.txt'));
        $data = file_get_contents(public_path('~test/dummy.txt'));
        $this->assertSame('Dummy', $data);

        // routes
        $this->assertFileExists($manifestPath . '/routes.php');
        $data = file_get_contents($manifestPath . '/routes.php');
        $this->assertStringEndsWith('$route->get(\'dummy\', \'DummyController@index\');', trim($data));

        // services
        $this->assertFileExists($manifestPath . '/services.php');
        $data = file_get_contents($manifestPath . '/services.php');
        $this->assertStringEndsWith('$di->set(\'test\', \Pletfix\Test\DummyService::class, true);', trim($data));

        // views
        $this->assertFileExists($manifestPath . '/views.php');
        /** @noinspection PhpIncludeInspection */
        $data = include $manifestPath . '/views.php';
        $this->assertSame([
            'admin.dummy' => $currPath . '/plugins/test/views/admin/dummy.blade.php',
        ], $data);

        // unregister

        $this->assertInstanceOf(PluginManager::class, $m->unregister());
        $this->assertFalse($m->isRegistered());

        // asset
        $this->assertFileExists($manifestPath . '/assets.php');
        /** @noinspection PhpIncludeInspection */
        $data = include $manifestPath . '/assets.php';
        $this->assertSame([], $data);

        // bootstraps
        $this->assertFileExists($manifestPath . '/bootstrap.php');
        $data = file_get_contents($manifestPath . '/bootstrap.php');
        $this->assertSame('<?php', trim($data));

        // classes
        $this->assertFileExists($manifestPath . '/classes.php');
        /** @noinspection PhpIncludeInspection */
        $data = include $manifestPath . '/classes.php';
        $this->assertSame([], $data);

        // commands
        $this->assertFileExists($manifestPath . '/commands.php');
        /** @noinspection PhpIncludeInspection */
        $data = include $manifestPath . '/commands.php';
        $this->assertSame([], $data);

        // config
        $this->assertFileExists(config_path('~test.php'));
        /** @noinspection PhpIncludeInspection */
        $data = include config_path('~test.php');
        $this->assertSame(['foo' => 'bar'], $data); // Must not be deleted!

        // languages
        $this->assertFileExists($manifestPath . '/languages.php');
        /** @noinspection PhpIncludeInspection */
        $data = include $manifestPath . '/languages.php';
        $this->assertSame([], $data);

        // migrations
        $this->assertFileExists($manifestPath . '/migrations.php');
        /** @noinspection PhpIncludeInspection */
        $data = include $manifestPath . '/migrations.php';
        $this->assertSame([], $data);

        // packages
        $this->assertFileExists($manifestPath . '/packages.php');
        /** @noinspection PhpIncludeInspection */
        $data = include $manifestPath . '/packages.php';
        $this->assertSame([], $data);

        // public
        $this->assertFileNotExists(public_path('~test/dummy.txt'));

        // routes
        $this->assertFileExists($manifestPath . '/routes.php');
        $data = file_get_contents($manifestPath . '/routes.php');
        $this->assertSame('<?php', trim($data));

        // services
        $this->assertFileExists($manifestPath . '/services.php');
        $data = file_get_contents($manifestPath . '/services.php');
        $this->assertSame('<?php', trim($data));

        // views
        $this->assertFileExists($manifestPath . '/views.php');
        /** @noinspection PhpIncludeInspection */
        $data = include $manifestPath . '/views.php';
        $this->assertSame([], $data);
    }

    public function testUpdate()
    {
        $manifestPath = manifest_path('~plugins');

        $m = new PluginManager('pletfix/~test', __DIR__ . '/plugins/test', $manifestPath);
        $m->register();

        file_put_contents(config_path('~test.php'), '<?php return [\'foo\' => \'baz\'];');

        $this->assertInstanceOf(PluginManager::class, $m->update());

        /** @noinspection PhpIncludeInspection */
        $data = include config_path('~test.php');
        $this->assertSame(['foo' => 'baz'], $data); // config must not be override!

        $m->unregister();
    }

    public function testEmptyPlugin()
    {
        $manifestPath = manifest_path('~plugins');

        $m = new PluginManager('pletfix/~empty', __DIR__ . '/plugins/empty', $manifestPath);
        $this->assertInstanceOf(PluginManager::class, $m);
        $this->assertInstanceOf(PluginManager::class, $m->register());
        $this->assertTrue($m->isRegistered());

        $m2 = new PluginManager('pletfix/~test', __DIR__ . '/plugins/test', $manifestPath);
        $m2->register()->unregister();

        $this->assertInstanceOf(PluginManager::class, $m->unregister());
        $this->assertFalse($m->isRegistered());
    }

    public function testRegisterAlreadyRegisteredPlugin()
    {
        $manifestPath = manifest_path('~plugins');
        $m = new PluginManager('pletfix/~empty', __DIR__ . '/plugins/empty', $manifestPath);
        $m->register();
        $this->expectException(PluginException::class);
        $m->register();
    }

    public function testUpdateNotRegisteredPlugin()
    {
        $manifestPath = manifest_path('~plugins');
        $m = new PluginManager('pletfix/~empty', __DIR__ . '/plugins/empty', $manifestPath);
        $this->expectException(PluginException::class);
        $m->update();
    }

    public function testUnregisterNotRegisteredPlugin()
    {
        $manifestPath = manifest_path('~plugins');
        $m = new PluginManager('pletfix/~empty', __DIR__ . '/plugins/empty', $manifestPath);
        $this->expectException(PluginException::class);
        $m->unregister();
    }

    public function testWithoutPsr4()
    {
        $this->expectException(InvalidArgumentException::class);
        new PluginManager('pletfix/~faulty', __DIR__ . '/plugins/faulty', manifest_path('~plugins'));
    }

}