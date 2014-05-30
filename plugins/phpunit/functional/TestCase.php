<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PHPUnit\Functional;


use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Test\FunctionalTestCase;
use PhpGuard\Application\Util\Filesystem;
use PhpGuard\Plugins\PHPUnit\Inspector;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class TestCase extends FunctionalTestCase
{
    protected $composerDumped = false;

    protected function setUp()
    {
        parent::setUp();
        $file = PhpGuard::getPluginCache('phpunit').'/result_test.dat';
        static::$container->setParameter('phpunit.inspector_cache',$file);
    }

    static public function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::buildFixtures();
        chdir(static::$tmpDir);
    }

    static protected function buildFixtures()
    {
        $finder  = Finder::create();
        Filesystem::copyDir(__DIR__.'/fixtures',static::$tmpDir,$finder);
        chdir(static::$tmpDir);
        $exFinder = new ExecutableFinder();
        if(!is_executable($executable=$exFinder->find('composer.phar'))){
            $executable = $exFinder->find('composer');
        }
        $process = new Process($executable.' dumpautoload');
        $process->run();
        //static::$composerOutput = $process->getOutput();
    }

    /**
     * @return Inspector
     */
    protected function getInspector()
    {
        return static::$container->get('phpunit.inspector');
    }
} 