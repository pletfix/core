<?php

namespace Core\Tests\Services\PDOs\Schemas;

use Core\Services\Contracts\Database;
use Core\Services\PDOs\Schemas\Contracts\Schema;
use Core\Testing\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class SchemaTestCase extends TestCase
{
    /**
     * @var string
     */
    protected static $fixturePath;

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var Database|PHPUnit_Framework_MockObject_MockObject
     */
    protected $db;

    private function replaceFirstMatch($search, $replace, $subject)
    {
        $pos = strpos($subject, $search);

        return $pos !== false ? substr_replace($subject, $replace, $pos, strlen($search)) : $subject;
    }

    protected function expectsExec(array $statements, $same = true)
    {
        foreach ($statements as $i => $statement) {
            $statements[$i] = trim(preg_replace('/\s+/', ' ', str_replace("\n", '', $statement)));
        }

        $sequence = 0;
        $this->db->expects($this->any())
            ->method('exec')
            ->willReturnCallback(function($statement, $bindings) use ($statements, &$sequence, $same) {
                foreach ($bindings as $binding) {
                    $statement = $this->replaceFirstMatch('?', "'$binding'", $statement);
                }
                $statement = trim(preg_replace('/\s+/', ' ', str_replace("\n", '', $statement)), '; ');
                if ($same) {
                    $this->assertSame($statements[$sequence], $statement);
                }
                else {
                    $this->assertStringStartsWith($statements[$sequence], $statement);
                }
                $sequence++;

                return 0;
            });
    }

    protected function expectsExecFile($name)
    {
        $statements = explode(';', file_get_contents(static::$fixturePath . '/' . $name . '.sql'));
        $this->expectsExec($statements);
    }

    protected function expectsQuery($expectedQuery, $expectedBindings, $result, $index = null)
    {
        $this->db->expects($index === null ? $this->once() : $this->at($index))
            ->method('query')
            ->willReturnCallback(function($actualQuery, $actualBindings) use ($expectedQuery, $expectedBindings, $result) {
                $actualQuery   = trim(preg_replace('/\s+/', ' ', str_replace("\n", '', $actualQuery)), '; ');
                $expectedQuery = trim(preg_replace('/\s+/', ' ', str_replace("\n", '', $expectedQuery)), '; ');
                $this->assertSame($expectedQuery, $actualQuery);
                $this->assertSame($expectedBindings, $actualBindings);
                return $result;
            });
    }

    protected function expectsQueryFile($name, $index = null)
    {
        /** @noinspection PhpIncludeInspection */
        $fixture = include static::$fixturePath . '/' . $name . '.php';
        $this->expectsQuery($fixture['query'], $fixture['bindings'], $fixture['result'], $index);
    }
}