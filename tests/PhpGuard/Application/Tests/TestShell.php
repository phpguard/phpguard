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


use PhpGuard\Application\Console\Shell;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\Spec\ObjectBehavior;

class TestShell extends Shell
{
    const EXIT_SHELL_MESSAGE = 'shell exit';
    protected $copyContainer;
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->copyContainer = $container;
        $this->historyFile = ObjectBehavior::$tmpDir.'/history';
    }

    public function installReadlineCallback()
    {

    }

    public function run()
    {

    }

    public function exitShell()
    {
        $this->copyContainer->get('phpguard')
            ->log(self::EXIT_SHELL_MESSAGE);
    }
}