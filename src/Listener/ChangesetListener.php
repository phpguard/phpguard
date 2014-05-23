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
use PhpGuard\Application\PhpGuardEvents;
use PhpGuard\Application\Event\EvaluateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;


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
            PhpGuardEvents::postEvaluate => 'postEvaluate',
            PhpGuardEvents::preRunCommand => 'preRunCommand',
            PhpGuardEvents::postRunCommand => 'postRunCommand',
            PhpGuardEvents::runAllCommands => 'runAllCommand',
        );
    }

    public function postEvaluate(EvaluateEvent $event)
    {
        /* @var \PhpGuard\Application\Plugin\PluginInterface $plugin */
        /* @var \PhpGuard\Application\Log\ConsoleHandler $loggerHandler */

        $container = $this->container;
        $dispatcher = $container->get('dispatcher');
        $loggerHandler = $container->get('logger.handler');

        $exception = null;
        $pluginHasRun = false;
        $loggerHandler->reset();
        foreach($container->getByPrefix('plugins') as $plugin){
            if(!$plugin->isActive()){
                continue;
            }
            $paths = $plugin->getMatchedFiles($event);
            if(count($paths) > 0){
                $pluginHasRun = true;
                $runEvent = new GenericEvent($plugin,array('paths' =>$paths));
                $dispatcher->dispatch(
                    PhpGuardEvents::preRunCommand,
                    $runEvent
                );
                try{
                    $plugin->run($paths);
                }catch(\Exception $e){
                    $exception = $e;
                }

                $dispatcher->dispatch(
                    PhpGuardEvents::postRunCommand,
                    $runEvent
                );
                if(!is_null($exception)){
                    throw $exception;
                }
            }
        }

        if($pluginHasRun || $loggerHandler->isLogged()){
            $this->getShell()->installReadlineCallback();
        }
    }

    public function preRunCommand(GenericEvent $event)
    {
        $shell = $this->getShell();
        $output = $this->container->get('ui.output');
        $output->writeln("");
        $this->getLogger()->addDebug(
            'Begin executing '.$event->getSubject()->getName()
        );

        foreach($event->getArgument('paths') as $path){
            $this->getLogger()->addDebug(
                'Match file: '.$path->getRelativePathName()
            );
        }

        $shell->unsetStreamBlocking();
    }

    public function postRunCommand(GenericEvent $event)
    {
        $shell = $this->getShell();
        $this->getLogger()->addDebug(
            'End executing '.$event->getSubject()->getName()
        );
        $shell->setStreamBlocking();
    }

    public function runAllCommand(GenericEvent $event)
    {
        /* @var \PhpGuard\Application\Plugin\PluginInterface $plugin */

        if(is_null($plugin = $event->getArgument('plugin'))){
            $plugins = $this->container->getByPrefix('plugins');
        }else{
            $name = 'plugins.'.$plugin;
            if($this->container->has($name)){
                $plugin = $this->container->get('plugins.'.$plugin);
                $plugins = array($plugin);
            }else{
                throw new \RuntimeException(sprintf(
                        'Plugin "%s" is not registered',
                        $plugin
                ));
            }
        }

        $this->getShell()->unsetStreamBlocking();

        foreach($plugins as $plugin)
        {
            if(!$plugin->isActive()){
                continue;
            }
            $this->getLogger()->addDebug(
                'Start running all for plugin '.$plugin->getName()
            );
            $plugin->runAll();
            $this->getLogger()->addDebug(
                'End running all for plugin '.$plugin->getName()
            );
        }

        // restore shell behavior
        $this->getShell()->setStreamBlocking();
        $this->getShell()->installReadlineCallback();
    }

    /**
     * @return \PhpGuard\Application\Console\Shell
     */
    private function getShell()
    {
        return $this->container->get('ui.shell');
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->container->get('logger');
    }
}