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

use PhpGuard\Application\Container\ContainerAware;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\PhpGuardEvents;
use PhpGuard\Listen\Listen;
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
            PhpGuardEvents::preLoadConfig => 'preLoad',
            PhpGuardEvents::postLoadConfig => 'postLoad',
        );
    }

    public function preLoad(GenericEvent $event)
    {
        /* @var PhpGuard $guard */
        $guard = $event->getSubject();
        $guard->loadPlugins();
        $guard->setOptions(array());
    }

    public function postLoad()
    {
        $this->setupParameters();
        /* @var \PhpGuard\Application\Plugin\PluginInterface $plugin */
        $container = $this->container;
        $phpGuard = $container->get('phpguard');
        $plugins = $container->getByPrefix('plugins');

        foreach($plugins as $plugin){
            if(!$plugin->isActive()){
                continue;
            }
            $logger = new Logger($plugin->getName());
            $logger->pushHandler($container->get('logger.handler'));
            $plugin->setLogger($logger);
            $plugin->configure();
            $phpGuard->log('Plugin <comment>'.$plugin->getName().'</comment> is running');
        }

        $this->setupListen();
    }

    private function setupParameters()
    {
        $container = $this->container;

        if(is_null($container->getParameter('phpguard.use_tty',null))){
            $container->setParameter('phpguard.use_tty',true);
        }
    }

    private function setupListen()
    {
        $container = $this->container;
        $adapter = $container->get('listen.adapter');
        $listener = $container->get('listen.listener');
        $this->getPhpGuard()->log('Using <comment>'.get_class($adapter).'</comment>',null,'Listen');
        $this->getPhpGuard()->log('Scanning Directory <comment>Please Wait!</comment>',null,'Listen');

        $listener->setAdapter($adapter);

        $this->getPhpGuard()->log('Start to monitor at <comment>'.getcwd().'</comment>',null,'Listen');
    }

    /**
     * @return \PhpGuard\Application\PhpGuard
     */
    private function getPhpGuard()
    {
        return $this->container->get('phpguard');
    }
}