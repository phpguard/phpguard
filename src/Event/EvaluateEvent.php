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

use PhpGuard\Listen\Event\ChangeSetEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class EvaluateEvent
 *
 */
class EvaluateEvent extends Event
{
    /**
     * @var \PhpGuard\Listen\Event\ChangeSetEvent
     */
    private $changeSet;

    public function __construct(ChangeSetEvent $event)
    {
        $this->changeSet = $event;

    }

    /**
     * @return ChangeSetEvent
     */
    public function getChangeSet()
    {
        return $this->changeSet;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->changeSet->getFiles();
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        return $this->changeSet->getEvents();
    }

    /**
     * @return \PhpGuard\Listen\Listener
     */
    public function getListener()
    {
        return $this->changeSet->getListener();
    }
}
