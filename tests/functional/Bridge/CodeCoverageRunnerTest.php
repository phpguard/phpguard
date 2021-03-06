<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Functional\Coverage;

use PhpGuard\Application\Bridge\CodeCoverage\CodeCoverageSession;
use PhpGuard\Application\Event\GenericEvent;
use PhpGuard\Application\Event\ProcessEvent;
use PhpGuard\Application\Functional\TestCase;
use PhpGuard\Application\Util\Filesystem;

class CodeCoverageRunnerTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->getTester()->run('-vvv');
    }

    public function testPrintReport()
    {
        if (file_exists($file=CodeCoverageSession::getCacheFile())) {
            unlink($file);
        }

        $options = array(
            'enabled'=>true,
            'output.text' => true,
            'output.html' => getcwd(),
            'output.clover' => getcwd().'/clover.xml',
        );
        static::$container->setParameter('session.results',array('some'));

        $runner = new CodeCoverageSession();
        $runner->setOptions($options);
        $runner->setContainer(static::$container);

        $event = new ProcessEvent(static::$container->get('plugins.test'));
        $genericEvent = new GenericEvent(static::$container);
        $genericEvent->addProcessEvent($event);
        // not display coverage when results file not exists
        $runner->process($genericEvent);
        $this->assertNotDisplayContains('html output');
        $this->assertNotDisplayContains('text output');
        $this->assertNotDisplayContains('clover output');

        // display coverage when cache file exists
        Filesystem::create()->serialize($file,$runner);
        $runner->process($genericEvent);
        $this->assertDisplayContains('html output');
        $this->assertDisplayContains('text output');
        $this->assertDisplayContains('clover output');
    }
}
