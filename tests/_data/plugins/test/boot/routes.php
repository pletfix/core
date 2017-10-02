<?php

$route = Core\Services\DI::getInstance()->get('route');

$route->get('dummy', 'DummyController@index');

?>