<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Listener;

use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Configuration\ConfigEvents;
use PhpGuard\Application\Event\GenericEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ApplicationListener
 *
 */
class ApplicationListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            ApplicationEvents::initialize => 'initialize',
            ApplicationEvents::started => 'started',
            ApplicationEvents::terminated => 'terminated',
        );
    }

    public function initialize(GenericEvent $event,$eventName,EventDispatcherInterface $dispatcher)
    {
        $container = $event->getContainer();
        if($container->getParameter('app.initialized',false)){
            return;
        }
        $dispatcher->dispatch(ConfigEvents::LOAD,$event);
        $container->setParameter('app.initialized',true);
    }

    public function started(GenericEvent $event)
    {
        $this->setupListen($event->getContainer());
    }

    public function terminated(GenericEvent $event)
    {
        $container = $event->getContainer();
        $container->get('phpguard')->stop();
        $container->get('ui.application')->exitApplication();
    }

    public function runAll(GenericEvent $event)
    {
        $container = $event->getContainer();
        $logger = $container->get('logger');
        
        if(is_null($plugin = $event->getArgument('plugin'))){
            $plugins = $container->getByPrefix('plugins');
        }else{
            $name = 'plugins.'.$plugin;
            if($container->has($name)){
                $plugin = $container->get('plugins.'.$plugin);
                $plugins = array($plugin);
            }else{
                $logger->addFail(sprintf(
                    'Plugin "%s" is not registered',
                    $plugin
                ));
                return;
            }
        }

        /* @var \PhpGuard\Application\Plugin\PluginInterface $plugin */

        if(!is_array($plugins)){
            $plugins = array($plugins);
        }

        foreach($plugins as $plugin)
        {
            if(!$plugin->isActive()){
                continue;
            }
            $logger->addDebug(
                'Start running all for plugin '.$plugin->getName()
            );
            $plugin->runAll();
            $logger->addDebug(
                'End running all for plugin '.$plugin->getName()
            );
        }
    }

    private function setupListen($container)
    {
        $adapter = $container->get('listen.adapter');
        $listener = $container->get('listen.listener');
        $logger = $container->get('logger');

        $logger->addCommon('Using <comment>'.get_class($adapter).'</comment>');
        $logger->addCommon('Scanning current working directory <comment>Please Wait!</comment>');
        $listener->setAdapter($adapter);
        $logger->addCommon('Start to monitor at <comment>'.getcwd().'</comment>');
    }

}