<?php

namespace Core\Tests\Services;

use Core\Services\ViewCompiler;
use Core\Testing\TestCase;

class ViewCompilerTest extends TestCase
{
    /**
     * @var ViewCompiler
     */
    private $c;

    protected function setUp()
    {
        $this->c = new ViewCompiler;
    }

    public function testEmptyString()
    {
        $this->assertSame('', $this->c->compile(''));
    }

    public function testComment()
    {
        $this->assertEquals("A  B", $this->c->compile("A {{-- A comment! --}} B"));
    }

    public function testEscapedEcho()
    {
        $this->assertEquals("<?php echo e(\$var); ?>", $this->c->compile("{{ \$var }}"));
        $this->assertEquals("<?php echo e(\$var); ?>", $this->c->compile("{{
            \$var
        }}"));
    }

    public function testRawEcho()
    {
        $this->assertEquals("<?php echo \$var; ?>", $this->c->compile("{!! \$var !!}"));
        $this->assertEquals("<?php echo \$var; ?>", $this->c->compile("{!!
            \$var
        !!}"));
    }

    public function testEchoWithDefaultValue()
    {
        $this->assertEquals("<?php echo e(isset(\$var) ? \$var : 'default'); ?>", $this->c->compile("{{ \$var or 'default' }}"));
        $this->assertEquals("<?php echo isset(\$var) ? \$var : 'default'; ?>",    $this->c->compile("{!! \$var or 'default' !!}"));
    }

    public function testMasking()
    {
        $this->assertEquals("{{ \$var }}", $this->c->compile("@{{ \$var }}"));
        $this->assertEquals("{!! \$var !!}", $this->c->compile("@{!! \$var !!}"));
        $this->assertEquals("@else", $this->c->compile("@@else"));
    }

    public function testExtendingLayout()
    {
        $actual =
            "@extends('layout')\n" .
            "<h1>Hello</h1>";

        $expected =
            "<h1>Hello</h1>\n" .
            "<?php echo \$this->make('layout', get_defined_vars()); ?>";

        $this->assertEquals($expected, $this->c->compile($actual));
    }

    public function testSection()
    {
        $actual =
            "@section('title', 'Test')";

        $expected =
            "<?php \$this->startSection('title', 'Test'); ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testSectionBlock()
    {
        $actual =
            "@section('content')\n" .
            "    You talking to me?\n" .
            "@endsection";

        $expected =
            "<?php \$this->startSection('content'); ?>\n" .
            "    You talking to me?\n" .
            "<?php \$this->endSection(); ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testYield()
    {
        $actual =
            "@yield('title')";

        $expected =
            "<?php echo \$this->yieldContent('title'); ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testYieldWithDefault()
    {
        $actual =
            "@yield('title', 'Test')";

        $expected =
            "<?php echo \$this->yieldContent('title', 'Test'); ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testInclude()
    {
        $actual =
            "@include('view')";

        $expected =
            "<?php echo \$this->make('view', get_defined_vars()); ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testIncludeWithVariables()
    {
        $actual =
            "@include('view', \$vars)";

        $expected =
            "<?php echo \$this->make('view', array_merge(get_defined_vars(), \$vars)); ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testIf()
    {
        $this->assertSame("<?php if(\$var === null): ?>", $this->c->compile("@if(\$var === null)"));
        $this->assertSame("<?php if(\$var === null): ?>", $this->c->compile("@if (\$var === null)"));
        $this->assertSame("<?php if(\$var === null): ?> ", $this->c->compile("@if    (\$var === null) "));

        $actual =
            "@if(\$var === null)\n" .
            "    foo\n" .
            "@endif";

        $expected =
            "<?php if(\$var === null): ?>\n" .
            "    foo\n" .
            "<?php endif; ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testIfWithElse()
    {
        $actual =
            "@if(\$var === null)\n" .
            "    foo\n" .
            "@else\n" .
            "    bar\n" .
            "@endif";

        $expected =
            "<?php if(\$var === null): ?>\n" .
            "    foo\n" .
            "<?php else: ?>\n" .
            "    bar\n" .
            "<?php endif; ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testIfWithElseIf()
    {
        $this->assertSame("<?php elseif(\$var === null): ?>", $this->c->compile("@elseif(\$var === null)"));
        $this->assertSame("<?php elseif(\$var === null): ?>", $this->c->compile("@elseif (\$var === null)"));
        $this->assertSame("<?php elseif(\$var === null): ?> ", $this->c->compile("@elseif    (\$var === null) "));

        $actual =
            "@if(\$var === null)\n" .
            "    foo\n" .
            "@elseif(\$var === '')\n" .
            "    bar\n" .
            "@else\n" .
            "    baz\n" .
            "@endif";

        $expected =
            "<?php if(\$var === null): ?>\n" .
            "    foo\n" .
            "<?php elseif(\$var === ''): ?>\n" .
            "    bar\n" .
            "<?php else: ?>\n" .
            "    baz\n" .
            "<?php endif; ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testFor()
    {
        $actual =
            "@for(\$i = 0; \$i < 10; \$i++)\n" .
            "    foo(\$i);\n" .
            "@endfor";

        $expected =
            "<?php for(\$i = 0; \$i < 10; \$i++): ?>\n" .
            "    foo(\$i);\n" .
            "<?php endfor; ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testForEach()
    {
        $actual =
            "@foreach(\$list as \$val)\n" .
            "    foo(\$val);\n" .
            "@endforeach";

        $expected =
            "<?php foreach(\$list as \$val): ?>\n" .
            "    foo(\$val);\n" .
            "<?php endforeach; ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testForEachWithKey()
    {
        $actual =
            "@foreach(\$list as \$key => \$val)\n" .
            "    foo(\$key, \$val);\n" .
            "@endforeach";

        $expected =
            "<?php foreach(\$list as \$key => \$val): ?>\n" .
            "    foo(\$key, \$val);\n" .
            "<?php endforeach; ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testWhile()
    {
        $actual =
            "@while(\$var === null)\n" .
            "    foo(\$var);\n" .
            "@endwhile";

        $expected =
            "<?php while(\$var === null): ?>\n" .
            "    foo(\$var);\n" .
            "<?php endwhile; ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testContinue()
    {
        $actual =
            "@while(\$var === null)\n" .
            "    @if(foo(\$var))\n" .
            "        @continue\n" .
            "    @endif\n" .
            "@endwhile";

        $expected =
            "<?php while(\$var === null): ?>\n" .
            "    <?php if(foo(\$var)): ?>\n" .
            "        <?php continue; ?>\n" .
            "    <?php endif; ?>\n" .
            "<?php endwhile; ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testContinueWithCondition()
    {
        $actual =
            "@while(\$var === null)\n" .
            "    @continue(\$var > 10)\n" .
            "@endwhile";

        $expected =
            "<?php while(\$var === null): ?>\n" .
            "    <?php if(\$var > 10) continue; ?>\n" .
            "<?php endwhile; ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testBreak()
    {
        $actual =
            "@while(\$var === null)\n" .
            "    @if(foo(\$var))\n" .
            "        @break\n" .
            "    @endif\n" .
            "@endwhile";

        $expected =
            "<?php while(\$var === null): ?>\n" .
            "    <?php if(foo(\$var)): ?>\n" .
            "        <?php break; ?>\n" .
            "    <?php endif; ?>\n" .
            "<?php endwhile; ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testContinueWithBreak()
    {
        $actual =
            "@while(\$var === null)\n" .
            "    @break(\$var > 10)\n" .
            "@endwhile";

        $expected =
            "<?php while(\$var === null): ?>\n" .
            "    <?php if(\$var > 10) break; ?>\n" .
            "<?php endwhile; ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testIsRole()
    {
        $actual =
            "@is('admin')\n" .
            "    Hello Admin!\n" .
            "@elseis('user')\n" .
            "    Hello User!\n" .
            "@elseis\n" .
            "    Hello Nobody!\n" .
            "@endis";

        $expected =
            "<?php if(auth()->is('admin')): ?>\n" .
            "    Hello Admin!\n" .
            "<?php elseif(auth()->is('user')): ?>\n" .
            "    Hello User!\n" .
            "<?php else: ?>\n" .
            "    Hello Nobody!\n" .
            "<?php endif; ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testIsNotRole()
    {
        $actual =
            "@isnot('admin')\n" .
            "    You are not an admin!\n" .
            "@elseisnot('user')\n" .
            "    You are not a user!\n" .
            "@elseisnot\n" .
            "    You are someone!\n" .
            "@endisnot";

        $expected =
            "<?php if(!auth()->is('admin')): ?>\n" .
            "    You are not an admin!\n" .
            "<?php elseif(!auth()->is('user')): ?>\n" .
            "    You are not a user!\n" .
            "<?php else: ?>\n" .
            "    You are someone!\n" .
            "<?php endif; ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testCanAbility()
    {
        $actual =
            "@can('write')\n" .
            "    Writeable!\n" .
            "@elsecan('read')\n" .
            "    Readonly!\n" .
            "@elsecan\n" .
            "    Hidden!\n" .
            "@endcan";

        $expected =
            "<?php if(auth()->can('write')): ?>\n" .
            "    Writeable!\n" .
            "<?php elseif(auth()->can('read')): ?>\n" .
            "    Readonly!\n" .
            "<?php else: ?>\n" .
            "    Hidden!\n" .
            "<?php endif; ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testCanNotAbility()
    {
        $actual =
            "@cannot('fastest')\n" .
            "    Not the fastest!\n" .
            "@elsecannot('fast')\n" .
            "    Not fast!\n" .
            "@elsecannot\n" .
            "    Slowly!\n" .
            "@endcannot";

        $expected =
            "<?php if(!auth()->can('fastest')): ?>\n" .
            "    Not the fastest!\n" .
            "<?php elseif(!auth()->can('fast')): ?>\n" .
            "    Not fast!\n" .
            "<?php else: ?>\n" .
            "    Slowly!\n" .
            "<?php endif; ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testEmbeddedPHP()
    {
        $actual =
            "@php(\$foo = 'bar')";

        $expected =
            "<?php (\$foo = 'bar'); ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testEmbeddedPHPBlock()
    {
        $actual =
            "@php\n" .
            "    \$foo = 'bar';\n" .
            "@endphp";

        $expected =
            "<?php \n" .
            "    \$foo = 'bar';\n" .
            " ?>";

        $this->assertSame($expected, $this->c->compile($actual));
    }

    public function testNotDefinedDirective()
    {
        $this->assertEquals("@foo", $this->c->compile("@foo"));
    }

}