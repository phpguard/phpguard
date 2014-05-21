<?php

$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->add('PhpGuard\\Application\\Tests\\', __DIR__);
$loader->add('PhpGuard\\Plugins\\', __DIR__);
$loader->register();