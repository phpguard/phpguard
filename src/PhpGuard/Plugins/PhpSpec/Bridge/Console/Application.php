<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Bridge\Console;

use PhpGuard\Plugins\PhpSpec\Bridge\Loader\ResourceLoader;
use PhpGuard\Plugins\PhpSpec\Bridge\PhpGuardExtension;
use PhpGuard\Plugins\PhpSpec\Bridge\Console\RunCommand;
use PhpGuard\Plugins\PhpSpec\Inspector;
use PhpSpec\Console\Application as BaseApplication;
use PhpSpec\Console\Command;
use PhpSpec\Extension;
use PhpSpec\Formatter;
use PhpSpec\Loader;
use PhpSpec\ServiceContainer;

/**
 * Class Application
 *
 */
class Application extends BaseApplication
{
    /**
     * @var Inspector
     */
    protected $inspector;

    protected function loadConfigurationFile(ServiceContainer $container)
    {
        BaseApplication::loadConfigurationFile($container);
        $inspector = $this->inspector;
        $container->setShared('event_dispatcher.listeners.phpguard',function($c) use($inspector){
            $ext = new PhpGuardExtension();
            $ext->load($c);
            return $ext;
        });
    }

    public function setInspector(Inspector $inspector)
    {
        $this->inspector = $inspector;
    }

    /**
     * Override setup commands
     *
     * Add Bridge\RunCommand
     * @param ServiceContainer $container
     */
    protected function setupCommands(ServiceContainer $container)
    {
        BaseApplication::setupCommands($container);
        $container->setShared('console.commands.run',function($c){
            return new RunCommand();
        });
    }

}