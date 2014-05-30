<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Functional\Command;

use PhpGuard\Plugins\PhpSpec\Functional\TestCase;
use PhpGuard\Plugins\PhpSpec\Command\DescribeCommand;
use Symfony\Component\Console\Tester\CommandTester;

class DescribeCommandTest extends TestCase
{
    public function testRun()
    {
        //$this->markTestIncomplete('not implemented yet');
        //$this->getTester()->run('phpspec:describe psr0/namespace1/FooClass');
        //$this->assertDisplayContains('created');
        $this->getApplication()->add(new DescribeCommand());
        $this->getTester()->run('phpspec:describe psr0/namespace1/FooClass -vvv');
        $this->assertDisplayContains('psr0\\namespace1\\FooClass');
    }
}
 