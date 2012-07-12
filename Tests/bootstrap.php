<?php
// Routing Tests Bootstrap

require_once 'Mapper.php';
require_once 'Route.php';
require_once 'Exception/ResourceNotFoundException.php';

$baseDir = realpath(__DIR__ .'/../../') . DIRECTORY_SEPARATOR;

if (file_exists($baseDir . 'Http/Request.php')) {
    require_once $baseDir . 'Http/Collection.php';
    require_once $baseDir . 'Http/Request.php';

    echo "Component Http Found!\n\n";
}
