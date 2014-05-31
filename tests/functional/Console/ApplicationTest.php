<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Functional\Console;

use PhpGuard\Application\Console\Application;
use PhpGuard\Application\Container;
use PhpGuard\Application\Functional\TestCase;
use PhpGuard\Application\Functional\TestPlugin;
use PhpGuard\Application\PhpGuard;
use Symfony\Component\Console\Output\StreamOutput;

class ApplicationTest extends TestCase
{
    /**
     * @dataProvider getTestRunCommand
     *
     */
    public function testShouldRunCommand($command,$expected)
    {
        $this->getTester()->run($command);
        $this->assertDisplayContains($expected);
    }

    public function getTestRunCommand()
    {
        return array(
            array('help','The help command'),
            array('list','version ',PhpGuard::VERSION),
            array('all',TestPlugin::RUN_ALL_MESSAGE),
            array('all test',TestPlugin::RUN_ALL_MESSAGE),
            array('all foo','Plugin "foo" is not registered'),
        );
    }

    public function testShouldSetupConsoleServices()
    {
        $container = new Container();
        $container->set('ui.output',new StreamOutput(fopen('php://memory', 'w', false)));
        $application = new Application($container);

        $this->assertInstanceOf('PhpGuard\\Application\\Console\\Shell',$container->get('ui.shell'));
        $this->assertInstanceOf('PhpGuard\\Application\\Console\\Application',$container->get('ui.application'));
    }

    public function testShouldHandleParameterOptions()
    {
        $this->getTester()->run('-vvv --tags=foo --coverage');

        $container = static::$container;
        $this->assertContains('foo',$container->getParameter('filter.tags'));
        $this->assertTrue($container->getParameter('coverage.enabled'));
    }

    public function testShouldStartShellWhenRunning()
    {
        $this->assertDisplayContains($this->getShell()->getPrompt());
    }
}