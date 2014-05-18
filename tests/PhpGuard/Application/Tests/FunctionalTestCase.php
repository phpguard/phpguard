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

use Symfony\Component\Console\Tester\ApplicationTester;

abstract class FunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return TestPhpGuard
     */
    protected function getPhpGuard()
    {
        return new TestPhpGuard();
    }

    public function getApplication()
    {
        return new TestApplication();
    }

    /**
     * @return \ApplicationTester
     */
    protected function getApplicationTester()
    {
        $tester = new ApplicationTester($this->getApplication());
        return $tester;
    }
} 