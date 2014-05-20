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


use PhpGuard\Application\Tests\FunctionalTestCase;

class ShellTest extends FunctionalTestCase
{
    public function testShouldEvaluateChange()
    {
        $this->buildFixtures();
        chdir(self::$tmpDir);
        $app = $this->getApplication();
        $tester = $this->getApplicationTester($app);

        $tester->run(array(),array('-vvv'));
        touch($file1 = getcwd().'/src/PhpGuardTest/Namespace1/NewClass.php');
        $app->evaluate();
        $display = $tester->getDisplay();
        $this->assertContains($file1,$display);
    }
}
 