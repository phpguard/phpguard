<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Tests;


use PhpGuard\Application\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;

class PhpGuardTest extends FunctionalTestCase
{
    public function testShouldCreateShellService()
    {
        $app = new Application();
        $container = $app->getContainer();
        $container->set('ui.output',new ConsoleOutput());
        $shell = $container->get('ui.shell');
        $this->assertInstanceOf('PhpGuard\\Application\\Console\\Shell',$shell);
    }

    public function testShouldSetupListenProperly()
    {
        $app = new Application();
        $phpguard = $app->getContainer()->get('phpguard');
        $phpguard->setOptions(array(
            'ignores' => 'foobar'
        ));
        $listener = $app->getContainer()->get('listen.listener');
        $this->assertInstanceOf('PhpGuard\\Listen\\Listener',$listener);

        $this->assertContains('foobar',$listener->getIgnores());
    }


}
 