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


use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Test\FunctionalTestCase;
use PhpGuard\Application\Spec\ObjectBehavior as ob;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Finder\Finder;

class TestCase extends FunctionalTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        ob::mkdir(self::$tmpDir);
        self::buildFixtures();
        chdir(self::$tmpDir);
        self::createApplication();
        self::$app->getContainer()->setShared('plugins.test',function($c){
            $plugin = new TestPlugin();
            $logger = new Logger('TestPlugin');
            $logger->pushHandler($c->get('logger.handler'));
            $plugin->setLogger($logger);
            return $plugin;
        });
        self::$tester->run(array());
    }

    static protected function buildFixtures($prefix=null)
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

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        ob::cleanDir(self::$tmpDir);
    }
}