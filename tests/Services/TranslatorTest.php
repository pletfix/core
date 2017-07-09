<?php

namespace Core\Tests\Services;

use Core\Services\DI;
use Core\Services\Translator;
use Core\Testing\TestCase;

class TranslatorTest extends TestCase
{
    /**
     * @var Translator
     */
    private $t;

    public static function setUpBeforeClass()
    {
        DI::getInstance()->get('config')
            ->set('app.locale', '~testlocale')
            ->set('app.fallback_locale', '~testfallback');

        DI::getInstance()->get('translator')
            ->setLocale('~testlocale');

        $path1 = resource_path('lang/~testlocale');
        $path2 = resource_path('lang/~testfallback');
        @mkdir($path1);
        @mkdir($path2);
        file_put_contents($path1 . '/dummy.php', '<?php return [\'welcome\' => \'Hello {name}!\'];');
        file_put_contents($path2 . '/dummy.php', '<?php return [\'welcome\' => \'Hallo {name}!!\', \'foo\' => \'fallback\'];');
    }

    public static function tearDownAfterClass()
    {
        $path1 = resource_path('lang/~testlocale');
        $path2 = resource_path('lang/~testfallback');
        @unlink($path1 . '/dummy.php');
        @unlink($path2 . '/dummy.php');
        @rmdir($path1);
        @rmdir($path2);
    }

    protected function setUp()
    {
        $this->t = new Translator;
    }

    public function testSetAndGetLocale()
    {
        $this->assertSame('~testlocale', $this->t->getLocale());
        $this->assertInstanceOf(Translator::class, $this->t->setLocale('~testlocale2'));
        $this->assertSame('~testlocale2', $this->t->getLocale());
    }

    public function testTranslate()
    {
        $this->assertSame('Hello Frank!', $this->t->translate('dummy.welcome', ['name' => 'Frank']));
        $this->assertSame('fallback', $this->t->translate(('dummy.foo'))); // fallback
        $this->assertSame('dummy.bar', $this->t->translate(('dummy.bar'))); // not translated
    }

    public function testTranslateWithoutLanguageFile()
    {
        // todo dad geht jetzt einfacher!!
        $pluginPathCreated = false;
        $migrateFileCreated = false;
        $manifest = manifest_path('plugins/languages.php');
        if (!file_exists($manifest)) {
            if (!file_exists(dirname($manifest))) {
                @mkdir(dirname($manifest));
                $pluginPathCreated = true;
            }
            file_put_contents($manifest, '<?php return array ();', LOCK_EX);
            $migrateFileCreated = true;
        }
        try {
            $this->t->setLocale('~testlocale2'); // language file not exists
            $this->assertSame('dummy.bar', $this->t->translate(('dummy.bar')));
        }
        finally {
            if ($migrateFileCreated) {
                @unlink($manifest);
            }
            if ($pluginPathCreated) {
                @rmdir(dirname($manifest));
            }
        }
    }

    public function testHas()
    {
        $this->assertTrue($this->t->has('dummy.welcome'));
        $this->assertTrue($this->t->has(('dummy.foo'))); // fallback
        $this->assertFalse($this->t->has(('dummy.bar'))); // not translated
    }

    public function testHasForLocal()
    {
        $this->assertTrue($this->t->hasForLocale('dummy.welcome'));
        $this->assertFalse($this->t->hasForLocale(('dummy.foo'))); // fallback
        $this->assertFalse($this->t->hasForLocale(('dummy.bar'))); // not translated
    }

}