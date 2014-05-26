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

class DescribeCommandTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }

    public function testRun()
    {
        $this->getTester()->run('phpspec:describe psr0/namespace1/FooClass');
        $this->assertDisplayContains('created');
    }
}
 