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


use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Event\GenericEvent;
use PhpGuard\Application\PhpGuard;

class TestPhpGuard extends PhpGuard
{
    public function start()
    {
        $container = $this->container;
        $dispatcher = $container->get('dispatcher');

        $event = new GenericEvent($container);
        $dispatcher->dispatch(ApplicationEvents::started,$event);


        $shell = $container->get('ui.shell');
        $this->showHeader();
        $shell->showPrompt();
        $container->get('logger')->addDebug('Test Application Started');
    }

    public function evaluate()
    {
        $container = $this->container;
        $container->get('logger')->addCommon('Start to Evaluate');
        parent::evaluate();
    }
} 