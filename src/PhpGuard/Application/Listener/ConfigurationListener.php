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
use PhpGuard\Application\PhpGuardEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class ConfigurationListener
 *
 */
class ConfigurationListener extends ContainerAware implements EventSubscriberInterface
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
            PhpGuardEvents::CONFIG_PRE_LOAD => 'preLoad',
            PhpGuardEvents::CONFIG_POST_LOAD => 'postLoad',
        );
    }

    public function preLoad()
    {

    }

    public function postLoad(GenericEvent $event)
    {
        /* @var \PhpGuard\Application\Guard $guard */
        $guard = $event->getSubject();
        $guard->setupListen($guard->getContainer());

        //$adapter = $guard->getContainer()->get('guard.listen.adapter');
        //$adapter->ignore('vendor');

        $container = $event->getSubject()->getContainer();

        /*foreach($event->getSubject()->getWatchers() as $watcher)
        {
            $container->get('guard.listen.adapter')
                ->track($watcher);
        }*/
    }
}