<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Test;


use PhpGuard\Application\Console\Shell;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Spec\ObjectBehavior;

/**
 * Class TestShell
 *
 * @package PhpGuard\Application\Test
 * @codeCoverageIgnore
 */
class TestShell extends Shell
{
    const EXIT_SHELL_MESSAGE = 'shell exit';
    protected $copyContainer;
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->copyContainer = $container;
        $file = sys_get_temp_dir().'/history_phpguard_test';
        if(is_file($file)){
            unlink($file);
        }
        $this->historyFile = $file;
    }

    public function installReadlineCallback()
    {
        return;
    }

    public function run()
    {
        $this->copyContainer->get('ui.output')->write($this->getPrompt());
        return;
    }

    public function exitShell()
    {
        $this->copyContainer->get('logger')
            ->addCommon(self::EXIT_SHELL_MESSAGE);
    }
}