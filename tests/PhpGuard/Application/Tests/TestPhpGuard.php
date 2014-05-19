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

use PhpGuard\Application\PhpGuard;
use PhpGuard\Listen\Event\ChangeSetEvent;

class TestPhpGuard extends PhpGuard
{
    public function start()
    {
        $listener = $this->getContainer()->get('listen.listener');
        $listener->latency(10);
        $listener->alwaysNotify(true);
        parent::start();
    }

    public function listen(ChangeSetEvent $event)
    {
        parent::listen($event);
        // always stop after first evaluation
        $event->getListener()->stop();
    }

}