<?php

$loader = require __DIR__ . '/../vendor/autoload.php';

$basedir = realpath(__DIR__.'/..');
$loader->addPsr4('PhpGuard\\Application\\Tests\\', __DIR__.'/functional');
$loader->addPsr4('PhpGuard\\Plugins\\PhpSpec\\Tests\\', $basedir.'/plugins/phpspec/tests');
$loader->addPsr4('PhpGuard\\Plugins\\PhpUnit\\Tests\\', $basedir.'/plugins/phpunit/tests');
$loader->register();