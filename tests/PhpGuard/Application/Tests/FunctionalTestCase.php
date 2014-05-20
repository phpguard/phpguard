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

use PhpGuard\Application\Spec\ObjectBehavior as ob;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Finder\Finder;

abstract class FunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    static $tmpDir;
    static $cwd;

    /**
     * @var TestApplication
     */
    protected $app;

    /**
     * @var ApplicationTester
     */
    protected $tester;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        if(is_null(self::$tmpDir)){
            self::$tmpDir = ob::$tmpDir;
        }
        if(is_null(self::$cwd)){
            self::$cwd = getcwd();
        }
        ob::mkdir(self::$tmpDir);
    }

    protected function setUp()
    {
        parent::setUp();
        ob::mkdir(self::$tmpDir);
        $this->buildFixtures();

        chdir(self::$tmpDir);
        $this->app = $this->getApplication();
        $this->tester = $this->getApplicationTester($this->app);
        $this->tester->run(array());
    }

    protected function tearDown()
    {
        ob::cleanDir(self::$tmpDir);
    }

    protected function getShell()
    {
        return $this->app->getShell();
    }

    protected function getDisplay()
    {
        return $this->tester->getDisplay();
    }

    /**
     * @return TestPhpGuard
     */
    protected function getPhpGuard()
    {
        return new TestPhpGuard();
    }

    /**
     * @return TestApplication
     */
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

    protected function buildFixtures($prefix=null)
    {
        $finder = Finder::create();
        $finder->in(__DIR__.'/fixtures');

        foreach($finder->files() as $file){
            $target = self::$tmpDir.$prefix.'/'.$file->getRelativePathname();
            ob::mkdir(dirname($target));
            copy($file,$target);
            if(false!==strpos($target,'.php')){
                require_once($target);
            }
        }
    }
} 