<?php

$di = \Core\Services\DI::getInstance();

/** @noinspection PhpUndefinedClassInspection, PhpUndefinedNamespaceInspection */
$di->set('foo', \Pletfix\Test\FooService::class, true);

?>