<?php

$loader = require __DIR__ . '/../vendor/autoload.php';

$basedir = realpath(__DIR__.'/..');
$loader->addPsr4('PhpGuard\\Application\\Functional\\', __DIR__.'/functional');
$loader->addPsr4('PhpGuard\\Plugins\\PhpSpec\\Functional\\', $basedir.'/plugins/phpspec/functional');
$loader->addPsr4('PhpGuard\\Plugins\\PhpUnit\\Functional\\', $basedir.'/plugins/phpunit/functional');
$loader->register();