<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Functional;

use PhpGuard\Application\Test\FunctionalTestCase;
use PhpGuard\Application\Test\TestApplication;
use PhpGuard\Application\Util\Filesystem;
use Symfony\Component\Finder\Finder;

class TestCase extends FunctionalTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::buildFixtures();
    }

    public static function buildFixtures($suffix='common')
    {
        $finder = Finder::create();
        Filesystem::copyDir(static::$cwd.'/tests/fixtures/'.$suffix,static::$tmpDir,$finder);
    }

    /**
     * @param  bool            $initialize
     * @return TestApplication
     */
    public static function createApplication($initialize = false)
    {
        parent::createApplication($initialize);
        static::$container->setShared('plugins.test',function () {
            return new TestPlugin();
        });

    }
}
