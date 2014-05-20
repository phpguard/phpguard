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

use PhpGuard\Application\Spec\ObjectBehavior;
use Symfony\Component\Console\Tester\ApplicationTester;

abstract class FunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    static $tmpDir;
    static $cwd;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        if(is_null(self::$tmpDir)){
            self::$tmpDir = ObjectBehavior::$tmpDir;
        }
        if(is_null(self::$cwd)){
            self::$cwd = getcwd();
        }
    }


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
    protected function getApplicationTester($app = null)
    {
        if(is_null($app)){
            $app  = $this->getApplication();
        }
        $tester = new ApplicationTester($app);
        return $tester;
    }
} 