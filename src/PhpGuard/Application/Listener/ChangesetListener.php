<?php

namespace PhpGuard\Application\Listener;

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use PhpGuard\Application\ContainerAware;
use PhpGuard\Application\Guard;
use PhpGuard\Application\Interfaces\PluginInterface;
use PhpGuard\Application\PhpGuardEvents;
use PhpGuard\Application\Watcher;
use PhpGuard\Application\Event\EvaluateEvent;
use PhpGuard\Listen\Listener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class ChangesetListener
 *
 */
class ChangesetListener extends ContainerAware implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            PhpGuardEvents::POST_EVALUATE => 'postEvaluate'
        );
    }

    public function postEvaluate(EvaluateEvent $event)
    {
        /* @var PluginInterface $plugin */
        $container = $this->container;

        foreach($container->getByPrefix('guard.plugins') as $plugin){
            $plugin->run($event->getChangeSet());
        }
    }

    /**
     * @return Guard
     */
    private function getGuard()
    {
        return $this->container->get('guard');
    }
}