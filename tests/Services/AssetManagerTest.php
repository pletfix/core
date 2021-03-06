<?php

namespace Core\Tests\Services;

use Core\Services\AssetManager;
use Core\Testing\TestCase;
use InvalidArgumentException;

class AssetManagerTest extends TestCase
{
    private static $assetPath;
    private static $manifestFile;
    private static $pluginManifestOfAssets;

    public static function setUpBeforeClass()
    {
        self::$assetPath = realpath(__DIR__ . '/../_data/assets');
        self::$manifestFile = manifest_path('~assets/manifest.php');
        self::$pluginManifestOfAssets = realpath(__DIR__ . '/../_data/plugin_manifest/assets.php');
    }

    public static function tearDownAfterClass()
    {
        $manifestPath = manifest_path('~assets');
        $manifestFile = $manifestPath . '/manifest.php';

        // Remove generated unique build files.
        if (file_exists($manifestFile)) {
            /** @noinspection PhpIncludeInspection */
            $build = include $manifestFile;
            if (isset($build['~test/js/~test.js'])) {
                @unlink(public_path($build['~test/js/~test.js']));
            }
            if (isset($build['~test/css/~test.css'])) {
                @unlink(public_path($build['~test/css/~test.css']));
            }
        }

        // Remove generated files under the public path.
        $publicPath = public_path('~test');
        @unlink($publicPath . '/js/~test.js');
        @unlink($publicPath . '/css/~test.css');
        @unlink($publicPath . '/files/test.txt');
        @unlink($publicPath . '/files/test2.txt');
        @unlink($publicPath . '/files/test3.txt');
        @unlink($publicPath . '/~foo.js');
        @rmdir($publicPath . '/js');
        @rmdir($publicPath . '/css');
        @rmdir($publicPath . '/files');
        @rmdir($publicPath);

        // Remove manifest.
        @unlink($manifestFile);
        @rmdir($manifestPath);
    }

    protected function setUp()
    {
        @unlink(self::$manifestFile);
    }

    public function testPublishAndRemove()
    {
        $m = new AssetManager(self::$assetPath . '/build.php', self::$manifestFile, self::$pluginManifestOfAssets);

        // publish

        $this->assertInstanceOf(AssetManager::class, $m->publish());

        /** @noinspection PhpIncludeInspection */
        $build = include self::$manifestFile;
        $this->assertTrue(is_array($build));
        $this->assertArrayHasKey('~test/js/~test.js', $build);
        $this->assertArrayHasKey('~test/css/~test.css', $build);

        $this->assertFileExists(public_path($build['~test/js/~test.js']));
        $this->assertFileExists(public_path($build['~test/css/~test.css']));
        $this->assertFileExists(public_path('~test/js/~test.js'));
        $this->assertFileExists(public_path('~test/css/~test.css'));
        $this->assertFileExists(public_path('~test/files/test.txt'));
        $this->assertFileExists(public_path('~test/files/test2.txt'));
        $this->assertFileExists(public_path('~test/files/test3.txt'));

        $js = file_get_contents(public_path($build['~test/js/~test.js']));
        $css = file_get_contents(public_path($build['~test/css/~test.css']));
        $this->assertSame("alert('Test 1');\nalert('Test 2');", $js);
        $this->assertSame("body{padding:50px}\n.foo{padding:50px}\nbody{padding:50px}\nbody\n  padding: 50px\nbody{padding:50px}", $css);
        $this->assertSame($js, file_get_contents(public_path('~test/js/~test.js')));
        $this->assertSame($css, file_get_contents(public_path('~test/css/~test.css')));

        // remove

        $this->assertInstanceOf(AssetManager::class, $m->remove());

        $this->assertFileNotExists(public_path($build['~test/js/~test.js']));
        $this->assertFileNotExists(public_path($build['~test/css/~test.css']));
        $this->assertFileNotExists(public_path('~test/js/~test.js'));
        $this->assertFileNotExists(public_path('~test/css/~test.css'));
        $this->assertFileNotExists(public_path('~test/files/test.txt'));
        $this->assertFileNotExists(public_path('~test/files/test2.txt'));
        $this->assertFileNotExists(public_path('~test/files/test3.txt'));

        /** @noinspection PhpIncludeInspection */
        $build = include self::$manifestFile;
        $this->assertSame([], $build);
    }

    public function testManifestPathNotExists()
    {
        // Set a manifest path that does not exist, so the path should be created.
        $manifestPath = manifest_path('~assets_dummy');
        try {
            $m = new AssetManager(self::$assetPath . '/build.php', $manifestPath . '/manifest.php');
            $this->assertInstanceOf(AssetManager::class, $m->publish());
            $this->assertDirectoryExists($manifestPath);
            $this->assertInstanceOf(AssetManager::class, $m->remove());
        } finally {
            @unlink($manifestPath . '/manifest.php');
            @rmdir($manifestPath);
        }
    }

    public function testNothingToDo()
    {
        $m = new AssetManager(self::$assetPath . '/build_nothing_todo.php', self::$manifestFile, self::$pluginManifestOfAssets);
        $m->publish('~test/~foo.js');
        $this->assertFileExists(public_path('~test/~foo.js'));
        $m->publish('~test/~foo.js'); // nothing to do!
        $m->remove('~test/~foo.js');
        $this->assertFileNotExists(public_path('~test/~foo.js'));
        $m->remove('~test/~foo.js');  // nothing to do!
    }

    public function testRelativePath()
    {
        $m = new AssetManager(self::$assetPath . '/build_relative_path.php', self::$manifestFile, self::$pluginManifestOfAssets);
        $m->publish('~test/~foo.js');
        $this->assertFileExists(public_path('~test/~foo.js'));
        $this->assertTrue(strpos(file_get_contents(public_path('~test/~foo.js')), 'Copyright (c) Nils Adermann, Jordi Boggiano') !== false);
        $m->remove('~test/~foo.js');
    }

    public function testDestNotDefined()
    {
        $m = new AssetManager(self::$assetPath . '/build.php', self::$manifestFile, self::$pluginManifestOfAssets);
        $this->expectException(InvalidArgumentException::class);
        $m->publish('~test/~foo.js');
    }

    public function testPublishAndRemovePlugin()
    {
        $m = new AssetManager(self::$assetPath . '/build_nothing_todo.php', self::$manifestFile, self::$pluginManifestOfAssets);

        // publish

        $this->assertInstanceOf(AssetManager::class, $m->publish(null, true, 'fake-plugin'));

        /** @noinspection PhpIncludeInspection */
        $build = include self::$manifestFile;
        $this->assertTrue(is_array($build));
        $this->assertArrayHasKey('~test/js/~plugin.js', $build);
        $this->assertFileExists(public_path($build['~test/js/~plugin.js']));
        $this->assertFileExists(public_path('~test/js/~plugin.js'));
        $js = file_get_contents(public_path($build['~test/js/~plugin.js']));
        $this->assertSame("alert('Plugin');", $js);
        $this->assertSame($js, file_get_contents(public_path('~test/js/~plugin.js')));

        // remove

        $this->assertInstanceOf(AssetManager::class, $m->remove(null, 'fake-plugin'));
        $this->assertFileNotExists(public_path($build['~test/js/~plugin.js']));
        $this->assertFileNotExists(public_path('~test/js/~plugin.js'));

        /** @noinspection PhpIncludeInspection */
        $build = include self::$manifestFile;
        $this->assertSame([], $build);
    }

    public function testPublishNotInstalledPlugin()
    {
        $m = new AssetManager(self::$assetPath . '/build.php', self::$manifestFile, self::$pluginManifestOfAssets);
        $this->expectException(InvalidArgumentException::class);
        $m->publish(null, true, 'wrong');
    }

    public function testDestNotDefinedInPlugin()
    {
        $m = new AssetManager(self::$assetPath . '/build.php', self::$manifestFile, self::$pluginManifestOfAssets);
        $this->expectException(InvalidArgumentException::class);
        $m->publish('~test/~foo.js', true, 'fake-plugin');
    }
}