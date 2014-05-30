<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Functional\Bridge;


use PhpGuard\Application\Bridge\CodeCoverageRunner;
use PhpGuard\Application\Functional\TestCase;
use PhpGuard\Application\Test\FunctionalTestCase;
use PhpGuard\Application\Util\Filesystem;

class CodeCoverageRunnerTest extends TestCase
{
    /**
     * @return CodeCoverageRunner
     */
    protected function getCoverageRunner()
    {
        return static::$container->get('coverage.runner');
    }

    public static function setUpBeforeClass()
    {
        FunctionalTestCase::setUpBeforeClass();
        static::buildFixtures('coverage');
    }

    protected function setUp()
    {
        parent::setUp();
        if(file_exists($file = CodeCoverageRunner::getCacheFile())){
            //unlink($file);
        }
        $this->getTester()->run('-vvv');
    }
}
 