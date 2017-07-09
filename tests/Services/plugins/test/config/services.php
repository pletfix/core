<?php

$di = \Core\Services\DI::getInstance();

/** @noinspection PhpUndefinedClassInspection, PhpUndefinedNamespaceInspection */
$di->set('test', \Pletfix\Test\DummyService::class, true);

?>