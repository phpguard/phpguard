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
use Symfony\Component\Finder\Finder;

class TestCase extends FunctionalTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        ob::mkdir(static::$tmpDir);
        static::buildFixtures();
        chdir(static::$tmpDir);
        static::createApplication();
        static::$app->getContainer()->setShared('plugins.test',function($c){
            $plugin = new TestPlugin();
            $logger = new Logger('TestPlugin');
            $logger->pushHandler($c->get('logger.handler'));
            $plugin->setLogger($logger);
            return $plugin;
        });
        static::$tester->run(array());
    }

    static protected function buildFixtures($prefix=null)
    {
        $finder = Finder::create();
        $finder->in(__DIR__.'/fixtures');

        foreach($finder->files() as $file){
            $target = static::$tmpDir.$prefix.'/'.$file->getRelativePathname();
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
        ob::cleanDir(static::$tmpDir);
    }

    public function setUp()
    {
        parent::setUp();
        static::$tester->run(array('-vvv'=>''));
    }
}