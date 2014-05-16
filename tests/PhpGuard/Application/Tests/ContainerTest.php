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

use PhpGuard\Application\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    private $container;
    public function testGetService()
    {
        $container = new Container();
        $this->container = $container;
        $this->container->setShared('guard.dispatcher', function ($c) {
            $dispatcher = new EventDispatcher();

            array_map(
                array($dispatcher, 'addSubscriber'),
                $c->getByPrefix('guard.dispatcher.listeners')
            );

            return $dispatcher;
        });

        $this->assertTrue($this->container->has('guard.dispatcher'));
        $this->container->get('guard.dispatcher');
        $this->assertTrue($this->container->has('guard.dispatcher'));
        $this->container->get('guard.dispatcher');
        $this->assertTrue($this->container->has('guard.dispatcher'));
    }
}