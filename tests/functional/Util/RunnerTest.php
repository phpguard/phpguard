<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Functional\Util;

use PhpGuard\Application\Functional\TestCase;
use PhpGuard\Application\Util\Runner;
use Symfony\Component\Process\ProcessBuilder;

class RunnerTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->getTester()->run('-vvv');
    }

    /**
     * @return Runner
     */
    protected function getRunner()
    {
        return static::$container->get('runner');
    }

    public function testShouldRunProcessBuilder()
    {
        $runner = $this->getRunner();

        $builder = new ProcessBuilder(array(
            'php',
            '--version'
        ));
        $runner->run($builder);
        $this->assertDisplayContains(PHP_VERSION);
    }

    public function testShouldNotWriteOutputWhenSilentEnabled()
    {
        $builder = new ProcessBuilder(array(
            'php',
            '--version'
        ));
        $process = $this->getRunner()->run($builder,array('silent'=>true));

        $this->assertNotDisplayContains(PHP_VERSION);
        $this->assertContains(PHP_VERSION,$process->getOutput());
    }
}