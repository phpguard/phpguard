<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Event;

use PhpGuard\Application\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\GenericEvent as BaseGenericEvent;

/**
 * Class GenericEvent
 */
class GenericEvent extends BaseGenericEvent
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ProcessEvent[]
     */
    private $processEvents = array();

    public function __construct(ContainerInterface $container,array $arguments=array())
    {
        $this->container = $container;
        parent::__construct($container,$arguments);
    }

    /**
     * @return \PhpGuard\Application\Container\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param ProcessEvent $event
     */
    public function addProcessEvent(ProcessEvent $event)
    {
        $this->processEvents[] = $event;
    }

    /**
     * @return \PhpGuard\Application\Event\ProcessEvent[]
     */
    public function getProcessEvents()
    {
        return $this->processEvents;
    }
}
