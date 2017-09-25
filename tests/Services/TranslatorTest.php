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
            ->set('locale.default',  'en')
            ->set('locale.fallback', 'de');

        DI::getInstance()->get('translator')->setLocale('en');
    }

    protected function setUp()
    {
        $this->t = new Translator(__DIR__ . '/../_data/lang', __DIR__ . '/../_data/plugin_manifest/languages.php');
    }

    public function testSetAndGetLocale()
    {
        $this->assertSame('en', $this->t->getLocale());
        $this->assertInstanceOf(Translator::class, $this->t->setLocale('fr'));
        $this->assertSame('fr', $this->t->getLocale());
    }

    public function testTranslate()
    {
        $this->assertSame('Hello Frank!', $this->t->translate('dummy.welcome', ['name' => 'Frank']));
        $this->assertSame('fallback', $this->t->translate(('dummy.foo'))); // fallback
        $this->assertSame('dummy.bar', $this->t->translate(('dummy.bar'))); // not translated
    }

    public function testTranslateWithoutLanguageFile()
    {
        $this->t->setLocale('xy'); // language file not exists
        $this->assertSame('dummy.bar', $this->t->translate(('dummy.bar')));
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