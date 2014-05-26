<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Tests\Test;



use PhpGuard\Application\Functional\TestCase;

class FunctionalTestCaseTest extends TestCase
{
    /**
     *
     */
    public function testShouldOverrideDefaultSetup()
    {
        $this->getTester()->run('');
        $container = static::$container;
        $this->assertContains('\PhpGuard',get_class($this->getPhpGuard()));
        $this->assertContains('TestApplication',get_class($this->getApplication()));
        $this->assertContains('TestShell',get_class($this->getShell()));
        $this->assertContains('StreamOutput',get_class($container->get('ui.output')));
    }
}
 