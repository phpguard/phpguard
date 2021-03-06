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

use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Container;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Util\Filesystem;
use PHPUnit_Framework_TestCase;

/**
 * Class FunctionalTestCase
 *
 * @package PhpGuard\Application\Test
 * @codeCoverageIgnore
 */
abstract class FunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface
     */
    protected static $container;

    protected static $tmpDir;

    protected static $cwd;

    public static function setUpBeforeClass()
    {
        static::createApplication();

        if (is_null(static::$cwd)) {
            static::$cwd = getcwd();
        }

        if (is_null(static::$tmpDir)) {
            $cwd = ltrim(str_replace(DIRECTORY_SEPARATOR,'-',static::$cwd),'-');
            static::$tmpDir = sys_get_temp_dir().'/phpguard-test/'.$cwd.DIRECTORY_SEPARATOR.uniqid('test');
        }

        static::$container->get('filesystem')->mkdir(static::$tmpDir);

        chdir(static::$tmpDir);
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        static::$container->get('filesystem')->create(static::$tmpDir);
        @chdir(static::$cwd);
    }

    public static function createApplication()
    {
        $app = new TestApplication();
        static::$container = $app->getContainer();
        static::$container->setParameter('phpguard.use_tty',false);
    }

    protected function evaluate()
    {
        static::$container->get('logger')
            ->addDebug('Start evaluate on: '.getcwd())
        ;
        static::$container->get('listen.listener')->evaluate();
    }

    /**
     * @return PhpGuard
     */
    protected function getPhpGuard()
    {
        return static::$container->get('phpguard');
    }

    /**
     * @return TestShell
     */
    protected function getShell()
    {
        return static::$container->get('ui.shell');
    }

    protected function getDisplay()
    {
        return $this->getTester()->getDisplay();
    }

    /**
     * @return TestApplication
     */
    public function getApplication()
    {
        return static::$container->get('ui.application');
    }

    /**
     * @return ApplicationTester
     */
    protected function getTester()
    {
        return static::$container->get('tester');
    }

    protected function assertDisplayContains($expected,$message=null)
    {
        $display = $this->getDisplay();
        $this->assertContains($expected,$display,$message);
    }

    protected function assertNotDisplayContains($expected,$message=null)
    {
        $display = $this->getDisplay();
        $this->assertNotContains($expected,$display,$message);
    }
}
