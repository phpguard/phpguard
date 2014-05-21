<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Test;

use PhpGuard\Application\Spec\ObjectBehavior as ob;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Class FunctionalTestCase
 *
 * @package PhpGuard\Application\Test
 * @codeCoverageIgnore
 */
abstract class FunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    static $tmpDir;
    static $cwd;

    /**
     * @var TestApplication
     */
    static $app;

    /**
     * @var ApplicationTester
     */
    static $tester;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        if(!is_dir(self::$tmpDir)){
            self::$tmpDir = ob::$tmpDir;
        }
        if(!is_dir(self::$cwd)){
            self::$cwd = getcwd();
        }
        ob::mkdir(self::$tmpDir);
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        chdir(self::$cwd);
        ob::cleanDir(self::$tmpDir);
    }

    static public function createApplication()
    {
        $app = new TestApplication();
        self::$app = $app;
        self::$tester = new ApplicationTester(self::$app);
    }

    protected function getShell()
    {
        return self::$app->getShell();
    }

    protected function getDisplay()
    {
        return self::$tester->getDisplay();
    }

    /**
     * @return TestApplication
     */
    public function getApplication()
    {
        return self::$app;
    }

    /**
     * @return  \Symfony\Component\Console\Tester\ApplicationTester
     */
    protected function getApplicationTester()
    {
        return self::$tester;
    }
} 