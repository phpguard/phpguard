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

use PhpGuard\Application\Functional\TestCase;
use PhpGuard\Application\Functional\TestPlugin;
use PhpGuard\Application\PhpGuard;

class ApplicationTest extends TestCase
{
    public function testShouldStartShellOnRunning()
    {
        $this->getTester()->run('-vvv');
        $this->assertDisplayContains('Welcome');
    }

    /**
     * @dataProvider getTestRunCommand
     *
     */
    public function testShouldRunCommand($command,$expected)
    {
        $this->getTester()->run($command.' --tags=foobar');
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
}