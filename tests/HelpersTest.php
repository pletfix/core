<?php

namespace Core\Tests\Functions;

use Core\Services\Contracts\Collection;
use Core\Services\Contracts\Paginator;
use Core\Services\Contracts\Response;
use Core\Services\DatabaseFactory;
use Core\Services\DI;
use Core\Services\Request;
use Core\Testing\TestCase;
use InvalidArgumentException;

/**
 * This test includes Laravel's Pluralizer and Helper Tests ([MIT License](https://github.com/laravel/framework/blob/5.4/LICENSE.md)).
 *
 * @see https://github.com/laravel/framework/blob/5.4/tests/Support/SupportPluralizerTest.php Pluralizer Test on GitHub
 * @see https://github.com/laravel/framework/blob/5.4/tests/Support/SupportHelpersTest.php Helpers Test on GitHub
 */
class HelpersTest extends TestCase
{
    private static $origRequest;
    private static $origDBFactory;

    public static function setUpBeforeClass()
    {
        self::$origRequest = DI::getInstance()->get('request');
        self::$origDBFactory = DI::getInstance()->get('database-factory');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('request', self::$origRequest, true);
        DI::getInstance()->set('database-factory', self::$origDBFactory, true);
    }

    // Paths

    public function testAppPath()
    {
        $this->assertEquals(BASE_PATH . '/app', app_path());
        $this->assertEquals(BASE_PATH . '/app/foo', app_path('foo'));
    }

    public function testBasePath()
    {
        $this->assertEquals(BASE_PATH, base_path());
        $this->assertEquals(BASE_PATH . '/foo', base_path('foo'));
    }

    public function testConfigPath()
    {
        $this->assertEquals(BASE_PATH . '/config', config_path());
        $this->assertEquals(BASE_PATH . '/config/foo', config_path('foo'));
    }

    public function testManifestPath()
    {
        $this->assertEquals(BASE_PATH . '/.manifest', manifest_path());
        $this->assertEquals(BASE_PATH . '/.manifest/foo', manifest_path('foo'));
    }

    public function testPublicPath()
    {
        $this->assertEquals(BASE_PATH . '/public', public_path());
        $this->assertEquals(BASE_PATH . '/public/foo', public_path('foo'));
    }

    public function testResourcePath()
    {
        $this->assertEquals(BASE_PATH . '/resources', resource_path());
        $this->assertEquals(BASE_PATH . '/resources/foo', resource_path('foo'));
    }

    public function testStoragePath()
    {
        $this->assertEquals(BASE_PATH . '/storage', storage_path());
        $this->assertEquals(BASE_PATH . '/storage/foo', storage_path('foo'));
    }

    public function testVendorPath()
    {
        $this->assertEquals(BASE_PATH . '/vendor', vendor_path());
        $this->assertEquals(BASE_PATH . '/vendor/foo', vendor_path('foo'));
    }

    public function testWorkbenchPath()
    {
        $this->assertEquals(BASE_PATH . '/workbench', workbench_path());
        $this->assertEquals(BASE_PATH . '/workbench/foo', workbench_path('foo'));
    }

    // Strings

    public function testPlural()
    {
        $this->assertEquals('children', plural('child'));
        $this->assertEquals('Children', plural('Child'));
        $this->assertEquals('CHILDREN', plural('CHILD'));
        $this->assertEquals('Tests', plural('Test'));
        $this->assertEquals('iPhones', plural('iPhone'));
        $this->assertEquals('knowledge', plural('knowledge'));
        $this->assertEquals('TRAFFIC', plural('TRAFFIC'));
    }

    public function testSingular()
    {
        $this->assertEquals('child', singular('children'));
        $this->assertEquals('Child', singular('Children'));
        $this->assertEquals('CHILD', singular('CHILDREN'));
        $this->assertEquals('Test', singular('Tests'));
        $this->assertEquals('iPhone', singular('iPhones'));
    }

    public function testCamelCase()
    {
        $this->assertEquals('fooBar', camel_case('FooBar'));
        $this->assertEquals('fooBar', camel_case('foo_bar'));
        $this->assertEquals('fooBarBaz', camel_case('Foo-barBaz'));
        $this->assertEquals('fooBarBaz', camel_case('foo-bar_baz'));
    }

    public function testLowerCase()
    {
        $this->assertEquals('foobar', lower_case('FooBar'));
    }

    public function testPascalCase()
    {
        $this->assertEquals('FooBar', pascal_case('fooBar'));
        $this->assertEquals('FooBar', pascal_case('foo_bar'));
        $this->assertEquals('FooBarBaz', pascal_case('foo-barBaz'));
        $this->assertEquals('FooBarBaz', pascal_case('foo-bar_baz'));
    }

    public function testRandomString()
    {
        $result = random_string(20);
        $this->assertInternalType('string', $result);
        $this->assertEquals(20, strlen($result));
    }

    public function testSnakeCase()
    {
        $this->assertEquals('foo_bar', snake_case('fooBar'));
    }

    public function testTitleCase()
    {
        $this->assertEquals('Foo Bar', title_case('foo bar'));
    }

    public function testUpperCase()
    {
        $this->assertEquals('FOO BAR', upper_case('foo bar'));
    }

    public function testLimitString()
    {
        $string = 'The PHP framework for web artisans.';
        $this->assertEquals('The PHP...', limit_string($string, 7));
        $this->assertEquals('The PHP', limit_string($string, 7, ''));
        $this->assertEquals('The PHP framework for web artisans.', limit_string($string, 100));

        $nonAsciiString = '这是一段中文';
        $this->assertEquals('这是一...', limit_string($nonAsciiString, 6));
        $this->assertEquals('这是一', limit_string($nonAsciiString, 6, ''));
    }

    public function testSlug()
    {
        $this->assertEquals('foo-bar', slug('foo Bar'));
    }

    public function testUtf8ToAscii()
    {
        $this->assertEquals('aeoeueiiat', utf8_to_ascii('äöüई@'));
    }

    // Miscellaneous

    public function testAbort()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->expectException(\Core\Exceptions\HttpException::class);
        abort(Response::HTTP_FORBIDDEN);
    }

    public function testAbortWithMessage()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->expectException(\Core\Exceptions\HttpException::class);
        $this->expectExceptionMessage('Foo');
        abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Foo');
    }

    public function testAsset()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['baseUrl'])->getMock();
        $request->expects($this->any())->method('baseUrl')->willReturn('my_base_url');
        DI::getInstance()->set('request', $request, true);

        $manifestFile = manifest_path('assets/manifest.php');
        /** @noinspection PhpIncludeInspection */
        $manifest = file_exists($manifestFile) ? include $manifestFile : [];
        if (!empty($manifest)) {
            $key = key($manifest);
            $value = $manifest[$key];
        }
        else {
            $key = 'foo.xyz';
            $value = 'foo.xyz';
        }
        $this->assertEquals('my_base_url/foo.xyz', asset('foo.xyz'));
        $this->assertEquals('my_base_url/' . $value, asset($key));
    }

    public function testAssetManager()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\AssetManager::class, asset_manager());
    }

    public function testAuth()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\Auth::class, auth());
    }

    public function testBcrypt()
    {
        $plain = 'geheim';
        $pw = bcrypt($plain);
        $this->assertEquals(60, strlen($pw));
        $this->assertTrue(password_verify($plain, $pw));
    }

    public function testBenchmark()
    {
        $result = benchmark(function(){}, 1, true);
        $this->assertEquals(3, count($result));

        ob_start();
        try {
            $result = benchmark(function () {}, 2);
        }
        finally {
            $out = ob_get_clean();
        }
        $this->assertNull($result);
        $this->assertStringStartsWith('Delay:', $out);
    }

    public function testCache()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\Cache::class, cache());

        DI::getInstance()->get('config')->set('cache.stores.~foo', ['driver' => 'Array']);
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\Cache::class, cache('~foo'));

        $this->expectException(InvalidArgumentException::class);
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        cache('~bar');
    }

    public function testCanonicalUrl()
    {
        DI::getInstance()->get('config')->set('app.url', 'http://mycanonical.com/mypath');
        DI::getInstance()->get('config')->set('locale.supported', ['en' => 'English']);
        $this->assertSame('http://mycanonical.com/mypath', canonical_url());

        $locale = locale();
        DI::getInstance()->get('config')->set('locale.supported', ['en' => 'English', 'de' => 'Deutsch']);
        $this->assertSame('http://mycanonical.com/mypath/' . $locale, canonical_url());
    }

    public function testCollect()
    {
        $c = collect(['a', 'b']);
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\Collection::class, $c);
        $this->assertSame(['a', 'b'], $c->all());
    }

    public function testCommand()
    {
        $tmp = storage_path('tmp.txt');
        if (file_exists($tmp)) {
            unlink($tmp);
        }

        $stdio = stdio();
        $stdout = $stdio->getStdout();
        $stdio->setStdout(fopen($tmp, 'w'));
        try {
            $result = command('help');
        }
        finally {
            $stdio->setStdout($stdout);
        }

        if (file_exists($tmp)) {
            $out = file_get_contents($tmp);
            unlink($tmp);
        }
        else {
            $out = '';
        }

        $this->assertEquals(0, $result);
        $this->assertTrue(strpos($out, 'Command tool for Pletfix') !== false);
    }

    public function testConfig()
    {
        $result = config('app.name');
        $this->assertNotEmpty($result);
    }

    public function testCookie()
    {
        $c = cookie();
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\Cookie::class, $c);
        $c->set('~foo', 'bar');
        try {
            $this->assertSame('bar', cookie('~foo', 'baz'));
        }
        finally {
            $c->delete('~foo');
        }
        $this->assertSame('baz', cookie('~foo', 'baz'));
    }

    public function testCsrfToken()
    {
        $t1 = csrf_token();
        $t2 = csrf_token();
        $this->assertEquals(40, strlen($t1));
        $this->assertEquals($t1, $t2);
    }

    public function testDatabase()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\Database::class, database());

        DI::getInstance()->get('config')->set('database.stores.~foo', ['driver' => 'SQLite']);
        DI::getInstance()->set('database-factory', DatabaseFactory::class, true);

        $this->assertSame('SQLite', database('~foo')->config('driver'));

        $this->expectException(InvalidArgumentException::class);
        database('~bar');
    }

    public function testDatetime()
    {
        $now = time();
        $todayString = strftime('%Y-%m-%d', $now);

        $dt = datetime();
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\DateTime::class, $dt);
        $this->assertSame($todayString, $dt->toDateString());

        $dt = datetime(new \DateTime('now'));
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\DateTime::class, $dt);
        $this->assertSame($todayString, $dt->toDateString());

        $dt = datetime($now);
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\DateTime::class, $dt);
        $this->assertSame($todayString, $dt->toDateString());

        $dt = datetime([2017, 3, 4, 5, 6, 7]);
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\DateTime::class, $dt);
        $this->assertSame('2017-03-04 05:06:07', $dt->toDateTimeString());

        $dt::setLocaleFormat([
            'datetime' => 'Y-m-d H:i',
            'date'     => 'Y-m-d',
            'time'     => 'H:i',
        ]);

        $dt = datetime('2017-03-04 05:06', null, 'locale');
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\DateTime::class, $dt);
        $this->assertSame('2017-03-04 05:06:00', $dt->toDateTimeString());

        $dt = datetime('2017-03-04', null, 'locale.date');
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\DateTime::class, $dt);
        $this->assertSame('2017-03-04', $dt->toDateString());

        $dt = datetime('05:06', null, 'locale.time');
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\DateTime::class, $dt);
        $this->assertSame($todayString . ' 05:06:00', $dt->toDateTimeString());

        $dt = datetime('201703040506', null, 'Ymdhi');
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\DateTime::class, $dt);
        $this->assertSame('2017-03-04 05:06:00', $dt->toDateTimeString());
    }

    public function testDi()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\DI::class, di());
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\Config::class, di('config'));
        /** @var Collection $c */
        $c = di('collection', [['foo', 'bar']]);
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\Collection::class, $c);
        $this->assertSame(['foo', 'bar'], $c->all());
    }

    public function testDump()
    {
        $this->assertSame('4711', dump(4711, true));

        ob_start();
        try {
            dump('Pletfix');
        }
        finally {
            $out = ob_get_clean();
        }

        $this->assertSame("'Pletfix'", $out);
    }

//    public function testDd()
//    {
//        ob_start();
//        try {
//            dd('Pletfix');
//        }
//        finally {
//            $out = ob_get_clean();
//        }
//
//        $this->assertSame("'Pletfix'", $out);
//    }

    public function testE()
    {
        $this->assertEquals('A &#039;quote&#039; is &lt;b&gt;bold&lt;/b&gt;', e('A \'quote\' is <b>bold</b>'));
    }

    public function testEnv()
    {
        putenv('test_true=true');
        putenv('test_false=false');
        putenv('test_empty=empty');
        putenv('test_null=null');
        putenv('test_string=Pletfix');
        putenv('test_string2="Pletfix"');
        $this->assertNull(env(''));
        $this->assertTrue(env('test_true'));
        $this->assertFalse(env('test_false'));
        $this->assertEmpty(env('test_empty'));
        $this->assertNull(env('test_null'));
        $this->assertSame('Pletfix', env('test_string'));
        $this->assertSame('Pletfix', env('test_string2'));
    }

    public function testError()
    {
        $this->assertEquals('foodef1', error('foo1', 'foodef1'));
        flash()->set('errors.foo1', 'bar1')->age();
        $this->assertEquals('bar1', error('foo1', 'foodef1'));
        $this->assertEquals(['foo1' => 'bar1'], error());
    }

    public function testGuessFileExtension()
    {
        $this->assertSame('png', guess_file_extension('image/png'));
    }

    public function testHttpStatus()
    {
        $this->assertSame('Bad Request', http_status_text(Response::HTTP_BAD_REQUEST));
    }

    public function testIsAbsolutePath()
    {
        $this->assertTrue(is_absolute_path('/tmp'));
        $this->assertFalse(is_absolute_path('tmp'));
        $this->assertFalse(is_absolute_path('./tmp'));
    }

    public function testIsConsole()
    {
        $this->assertTrue(is_console());
    }

    public function testIsTesting()
    {
        $this->assertTrue(is_testing());
        DI::getInstance()->get('config')->set('app.env', 'dummy');
        $this->assertFalse(is_testing());
        DI::getInstance()->get('config')->set('app.env', 'testing');
        $this->assertTrue(is_testing());
    }

    public function testIsWin()
    {
        $isWin = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
        $this->assertSame($isWin, is_windows());
    }

    public function testListClasses()
    {
        $path = storage_path('~test');
        @mkdir($path);
        @mkdir($path . '/Foo');
        touch($path . '/Foo/MyClass4Controller.php');
        touch($path . '/Foo/MyClass5Controller.php');
        touch($path . '/Foo/MyClass6.php');
        touch($path . '/MyClass1Controller.php');
        touch($path . '/MyClass2Controller.php');
        touch($path . '/MyClass3.php');
        try {
            $result = [];
            list_classes($result, $path, 'MyNamespace');
            $this->assertEquals(6, count($result));
            $this->assertEquals('MyNamespace\Foo\MyClass4Controller', $result[0]);
            $this->assertEquals('MyNamespace\MyClass3', $result[5]);

            $result = [];
            list_classes($result, $path, 'MyNamespace', 'Controller');
            $this->assertEquals(4, count($result));
            $this->assertEquals('MyNamespace\Foo\MyClass4Controller', $result[0]);
            $this->assertEquals('MyNamespace\MyClass2Controller', $result[3]);

            $result = [];
            list_classes($result, storage_path('~not_exists'), 'MyNamespace', 'Controller');
        }
        finally {
            unlink($path . '/Foo/MyClass4Controller.php');
            unlink($path . '/Foo/MyClass5Controller.php');
            unlink($path . '/Foo/MyClass6.php');
            unlink($path . '/MyClass1Controller.php');
            unlink($path . '/MyClass2Controller.php');
            unlink($path . '/MyClass3.php');
            rmdir($path . '/Foo');
            rmdir($path);
        }
    }

    public function testListFiles()
    {
        $path = storage_path('~test');
        @mkdir($path);
        touch($path . '/a.txt');
        touch($path . '/b.txt');
        touch($path . '/c.ini');
        @mkdir($path . '/foo');
        touch($path . '/foo/d.txt');
        touch($path . '/foo/e.txt');
        touch($path . '/foo/f.ini');
        try {
            $result = [];
            list_files($result, $path);
            $this->assertEquals(6, count($result));
            $this->assertEquals($path . '/a.txt', $result[0]);
            $this->assertEquals($path . '/foo/f.ini', $result[5]);

            $result = [];
            list_files($result, $path, ['txt']);
            $this->assertEquals(4, count($result));
            $this->assertEquals($path . '/a.txt', $result[0]);
            $this->assertEquals($path . '/foo/e.txt', $result[3]);

            $result = [];
            list_files($result, $path, null, false);
            $this->assertEquals(3, count($result));
            $this->assertEquals($path . '/a.txt', $result[0]);
            $this->assertEquals($path . '/c.ini', $result[2]);
        }
        finally {
            @unlink($path . '/a.txt');
            @unlink($path . '/b.txt');
            @unlink($path . '/c.ini');
            @unlink($path . '/foo/d.txt');
            @unlink($path . '/foo/e.txt');
            @unlink($path . '/foo/f.ini');
            @rmdir($path . '/foo');
            @rmdir($path);
        }
    }

    public function testLocale()
    {
        $curr = locale();
        $other = $curr == 'en' ? 'de' : 'en';
        $this->assertSame($other, locale($other));
        try {
            $this->assertSame($other, DI::getInstance()->get('translator')->getLocale());
            $this->assertSame($other, DI::getInstance()->get('date-time')->getLocale());
            $this->assertSame($other, DI::getInstance()->get('cookie')->get('locale'));
        }
        finally {
            locale($curr);
        }
    }

    public function testLogger()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, logger());
    }

    public function testMakeDir()
    {
        $path = storage_path('~test');
        @rmdir($path . '/foo/bar');
        @rmdir($path . '/foo');
        @rmdir($path);
        $this->assertTrue(make_dir($path. '/foo/bar'));
        try {
            $this->assertDirectoryExists($path);
        }
        finally {
            @rmdir($path . '/foo/bar');
            @rmdir($path . '/foo');
            @rmdir($path);
        }
    }

    public function testMailAddress()
    {
        $this->assertSame('user@example.com', mail_address('User <user@example.com>'));
        $this->assertSame('user@example.com', mail_address('user@example.com'));
    }

    public function testMailer()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\Mailer::class, mailer());
    }

    public function testMessage()
    {
        $this->assertEquals('foodef2', message('foodef2'));
        flash()->set('message', 'bar2')->age();
        $this->assertEquals('bar2', message('foodef2'));
    }

    public function testMigrator()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\Migrator::class, migrator());
    }

    public function testMimeType()
    {
        $this->assertSame('image/png', mime_type(__DIR__ . '/_data/images/logo_50x50.png'));
    }

    public function testOld()
    {
        $this->assertEquals('foodef3', old('foo3', 'foodef3'));
        flash()->set('input.foo3', 'bar3')->age();
        $this->assertEquals('bar3', old('foo3', 'foodef3'));
        $this->assertEquals(['foo3' => 'bar3'], old());
    }

    public function testPaginator()
    {
        $p = paginator(10, 5, 2);
        $this->assertInstanceOf(Paginator::class, $p);
        $this->assertSame(10, $p->total());
        $this->assertSame(5, $p->limit());
        $this->assertSame(2, $p->currentPage());
    }

    public function testPluginManager()
    {
        $this->expectException(InvalidArgumentException::class);
        plugin_manager('~foo/~bar');
    }

    public function testRedirect()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['baseUrl'])->getMock();
        $request->expects($this->any())->method('baseUrl')->willReturn('my_base_url');
        DI::getInstance()->set('request', $request, true);
        $this->assertInstanceOf(Response::class, redirect('foo', ['bar' => 4711, 'batz' => 'butz']));
        $this->assertRedirectedTo('my_base_url/foo?bar=4711&batz=butz');
    }

    public function testRemoveDir()
    {
        $path = storage_path('~test');
        @mkdir($path);
        touch($path . '/a.txt');
        touch($path . '/b.txt');
        @mkdir($path . '/foo');
        touch($path . '/foo/b.txt');
        try {
            // remove a file
            $this->assertFileExists($path . '/b.txt');
            $this->assertTrue(remove_dir($path . '/b.txt'));
            $this->assertFileNotExists($path . '/b.txt');
            // remove a directory
            $this->assertFileExists($path . '/foo/b.txt');
            $this->assertTrue(remove_dir($path));
            $this->assertDirectoryNotExists($path);
        }
        finally {
            @unlink($path . '/a.txt');
            @unlink($path . '/b.txt');
            @unlink($path . '/foo/b.txt');
            @rmdir($path . '/foo');
            @rmdir($path);
        }
    }

    public function testResponse()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\Response::class, response());
    }

    public function testRequest()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['baseUrl'])->getMock();
        $request->expects($this->any())->method('baseUrl')->willReturn('my_base_url');
        DI::getInstance()->set('request', $request, true);
        $this->assertInstanceOf(Request::class, request());
    }

    public function testSession()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\Session::class, session());
        $this->assertSame('bar', session('foo', 'bar'));
    }

    public function testStdio()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\Stdio::class, stdio());
    }

    public function testT()
    {
        DI::getInstance()->get('config')
            ->set('locale.default',  '~testlocale')
            ->set('locale.fallback', '~testfallback');

        DI::getInstance()->get('translator')
            ->setLocale('~testlocale');

        $dir = resource_path('lang');
        if (!file_exists($dir)) {
            make_dir($dir, 0755);
        }

        $path1 = resource_path('lang/~testlocale');
        $path2 = resource_path('lang/~testfallback');
        @mkdir($path1);
        @mkdir($path2);
        file_put_contents($path1 . '/dummy.php', '<?php return [\'welcome\' => \'Hello {name}!\'];');
        file_put_contents($path2 . '/dummy.php', '<?php return [\'welcome\' => \'Hallo {name}!!\', \'foo\' => \'fallback\'];');
        try {
            $this->assertSame('Hello Frank!', t('dummy.welcome', ['name' => 'Frank']));
            $this->assertSame('fallback', t('dummy.foo'));
            $this->assertSame('dummy.bar', t('dummy.bar')); // not translated
        }
        finally {
            @unlink($path1 . '/dummy.php');
            @unlink($path2 . '/dummy.php');
            @rmdir($path1);
            @rmdir($path2);
        }
    }

    public function testUrl()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['baseUrl'])->getMock();
        $request->expects($this->any())->method('baseUrl')->willReturn('my_base_url');
        DI::getInstance()->set('request', $request, true);

        $this->assertEquals('my_base_url', url());
        $this->assertEquals('my_base_url/foo', url('foo'));
        $this->assertEquals('my_base_url/foo?bar=4711&batz=butz', url('foo', ['bar' => 4711, 'batz' => 'butz']));
    }

    public function testView()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->assertInstanceOf(\Core\Services\Contracts\View::class, view());
        $this->expectException(InvalidArgumentException::class);
        view('');
    }
}