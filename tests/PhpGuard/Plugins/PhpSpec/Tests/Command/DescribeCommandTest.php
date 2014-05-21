<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Tests\Command;


use PhpGuard\Plugins\PhpSpec\Command\DescribeCommand;
use PhpGuard\Plugins\PhpSpec\Tests\TestCase;

class DescribeCommandTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$app->add(new DescribeCommand());
        self::$tester->run(array('-vvv'=>''));
    }

    public function testRun()
    {
        self::$tester->run(array('phpspec:describe','class'=>'PhpSpecTest1/FooClass'));
        $display = self::$tester->getDisplay(false);
        $this->assertContains('created',$display);

    }
}
 