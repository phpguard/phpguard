<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Tests\Console;

use PhpGuard\Application\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Tester\ApplicationTester;
use PhpGuard\Application\Console\Shell;

class ShellTest extends Shell
{
    public function run()
    {
        $this->getOutput()->write('shell is running');
    }
}

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function getApplication()
    {
        $app = new Application();
        $container = $app->getContainer();

        $container->set('guard.ui.shell',function($c){
            return new ShellTest($c);
        });
        $app->setAutoExit(false);
        return $app;
    }

    public function testRun()
    {
        $this->markTestIncomplete();
    }
}
 