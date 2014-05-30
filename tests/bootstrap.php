<?php

$loader = require __DIR__ . '/../vendor/autoload.php';

$basedir = realpath(__DIR__.'/..');
$loader->addPsr4('PhpGuard\\Application\\Functional\\', __DIR__.'/functional');
$loader->register();