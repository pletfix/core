<?php

$route = Core\Application::route();

$route->get('', function() {
    return 'The Pletfix Core is running!';
});
