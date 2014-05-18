<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\PhpGuard\Plugins\PhpSpec;

use PhpGuard\Application\Runner;
use PhpGuard\Plugins\PhpSpec\PhpSpecPlugin;

class MockPhpSpecPlugin extends PhpSpecPlugin
{
    /**
     * @var Runner
     */
    protected $runner;

    public function setRunner(Runner $runner)
    {
        $this->runner = $runner;
    }

    public function createRunner($command,array $arguments=array())
    {
        $this->runner->setCommand($command);
        $this->runner->setArguments($arguments);
        return $this->runner;
    }
}