<?php

declare(strict_types=1);

$autoloader = require dirname(__DIR__, 3) . '/../vendor/autoload.php';
$autoloader->addPsr4('App\\', dirname(__DIR__) . '/src/');
$autoloader->addPsr4('App\\Tests\\', __DIR__ . '/');
