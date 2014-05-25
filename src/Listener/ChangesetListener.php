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
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Event\CommandEvent;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Event\EvaluateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;


/**
 * Class ChangesetListener
 *
 */
class ChangesetListener extends ContainerAware implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            ApplicationEvents::postEvaluate => 'postEvaluate',
            ApplicationEvents::preRunCommand => 'preRunCommand',
            ApplicationEvents::postRunCommand => 'postRunCommand',
            ApplicationEvents::runAllCommands => 'runAllCommand',
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
        $loggerHandler->reset();
        $results = array();
        foreach($container->getByPrefix('plugins') as $plugin){
            if(!$plugin->isActive()){
                continue;
            }
            $paths = $plugin->getMatchedFiles($event);
            if(count($paths) > 0){
                $runEvent = new GenericEvent($plugin,array('paths' =>$paths));
                $dispatcher->dispatch(
                    ApplicationEvents::preRunCommand,
                    $runEvent
                );
                try{
                    $result = $plugin->run($paths);
                    if($result){
                        if(!is_array($result)){
                            $result = array($result);
                        }
                        $results = array_merge($results,$result);
                    }
                }catch(\Exception $e){
                    $results[] = new CommandEvent($plugin,CommandEvent::BROKEN,$e->getMessage(),$e);
                }

                $dispatcher->dispatch(
                    ApplicationEvents::postRunCommand,
                    $runEvent
                );
            }
        }
        $this->renderResults($container,$results);
        if(count($results) > 0 || $loggerHandler->isLogged()){
            $container->get('ui.shell')->installReadlineCallback();
        }
    }

    public function preRunCommand(GenericEvent $event)
    {
        $shell = $this->getShell();
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
        $results = array();
        foreach($plugins as $plugin)
        {
            if(!$plugin->isActive()){
                $this->getLogger()->addFail(sprintf('Plugin "%s" is not active',$plugin->getTitle()));
                continue;
            }
            $this->getLogger()->addDebug(
                'Start running all for plugin '.$plugin->getName()
            );
            $result = $plugin->runAll();
            if($result){
                $result = is_array($result) ? $result:array($result);
                $results = array_merge($results,$result);
            }
            $this->getLogger()->addDebug(
                'End running all for plugin '.$plugin->getName()
            );
        }
        if(count($results) > 0){
            $this->renderResults($event->getSubject(),$results);
        }
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

    private function renderResults(ContainerInterface $container,$results)
    {
        /* @var \PhpGuard\Application\Log\Logger $logger */
        /* @var \PhpGuard\Application\Event\CommandEvent $event */
        $logger = $container->get('logger');

        foreach($results as $event)
        {
            $status = $event->getResult();
            switch($status){
                case CommandEvent::SUCCEED:
                    $logger->addSuccess($event->getMessage());
                    break;
                case CommandEvent::FAILED:
                    $logger->addFail($event->getMessage());
                    break;
                case CommandEvent::BROKEN:
                    $logger->addFail($event->getMessage());
                    break;
            }
        }
    }
}