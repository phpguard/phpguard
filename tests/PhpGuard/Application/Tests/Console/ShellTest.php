<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Tests\Console;


use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Tests\TestCase;
use PhpGuard\Application\Tests\TestPlugin;
use PhpGuard\Application\Test\TestShell;

class ShellTest extends TestCase
{
    public function testShouldEvaluateChange()
    {
        touch($file1 = self::$tmpDir.'/src/PhpGuardTest/Namespace1/NewClass.php');
        $this->getShell()->evaluate();
        $this->assertContains($file1,$this->getDisplay());
    }

    /**
     * @dataProvider getTestRunCommand
     */
    public function testShouldRunCommand($command,$expected)
    {
        $this->getShell()->runCommand($command);
        $this->assertContains($expected,$this->getDisplay());
    }

    public function getTestRunCommand()
    {
        return array(
            array('help','The help command'),
            array('list','version '.PhpGuard::VERSION),
            array(false,TestPlugin::RUN_ALL_MESSAGE),
            array('',TestPlugin::RUN_ALL_MESSAGE),
            array('all',TestPlugin::RUN_ALL_MESSAGE),
            array('all foo','Plugin "foo" is not registered'),
            array('quit',TestShell::EXIT_SHELL_MESSAGE)
        );
    }

    public function testShouldRenderExceptionIfPluginThrowsExceptionWhenRunning()
    {
        $plugin = self::$app->getContainer()->get('plugins.test');
        $plugin->throwException = true;
        touch(self::$tmpDir.'/src/PhpGuardTest/Namespace1/TestThrow.php');
        $this->getShell()->evaluate();
        $this->assertContains(TestPlugin::THROW_MESSAGE,$this->getDisplay());
    }

    public function testCanBeStopped()
    {
        $this->getShell()->stop();
        $this->assertFalse($this->getShell()->isRunning());
    }
}