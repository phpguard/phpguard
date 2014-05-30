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
use Symfony\Component\Finder\Finder;

class TestCase extends FunctionalTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::buildFixtures();
    }

    static public function buildFixtures($suffix='common')
    {
        $finder = Finder::create();
        $finder->in(static::$cwd.'/tests/fixtures/'.$suffix);

        foreach($finder->files() as $file){
            $target = static::$tmpDir.'/'.$file->getRelativePathname();
            self::mkdir(dirname($target));
            copy($file,$target);
        }
    }


    /**
     * @param   bool $initialize
     * @return  TestApplication
     */
    static public function createApplication($initialize = false)
    {
        parent::createApplication($initialize);
        static::$container->setShared('plugins.test',function(){
            return new TestPlugin();
        });

    }
} 