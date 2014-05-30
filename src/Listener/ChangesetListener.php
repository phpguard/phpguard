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
use PhpGuard\Application\Event\ProcessEvent;
use PhpGuard\Application\Event\ResultEvent;
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
    private $results;

    public static function getSubscribedEvents()
    {
        return array(
            ApplicationEvents::postEvaluate => array(
                array('postEvaluate',10),
                array('showPrompt',-1000)
            ),
            ApplicationEvents::preRunCommand => 'preRunCommand',
            ApplicationEvents::postRunCommand => 'postRunCommand',
            ApplicationEvents::runAll => 'runAllCommand',
        );
    }

    public function postEvaluate(EvaluateEvent $event)
    {
        /* @var \PhpGuard\Application\Plugin\PluginInterface $plugin */
        /* @var \PhpGuard\Application\Log\ConsoleHandler $loggerHandler */

        $container = $this->container;

        // always set exit code to zero first
        $container->setParameter('application.exit_code',0);
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
                    $resultEvent = new ResultEvent(ResultEvent::ERROR,$e->getMessage(),array(),$e);
                    $results[] = new ProcessEvent($plugin,array($resultEvent));
                }

                $dispatcher->dispatch(
                    ApplicationEvents::postRunCommand,
                    $runEvent
                );
            }
        }
        $this->renderResults($container,$results);
        $this->results = $results;
        //$loggerHandler->reset();
    }

    public function showPrompt()
    {
        $loggerHandler = $this->container->get('logger.handler');
        if(count($this->results) > 0 || $loggerHandler->isLogged()){
            $this->container->get('ui.shell')->installReadlineCallback();
        }
        $loggerHandler->reset();
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
        $this->container->setParameter('application.exit_code',0);
        if ( is_null($plugin = $event->getArgument('plugin')) ) {
            $plugins = $this->container->getByPrefix('plugins');
        }
        else {
            $name = 'plugins.'.$plugin;
            if($this->container->has($name)){
                $plugin = $this->container->get('plugins.'.$plugin);
                $plugins = array($plugin);
            }
            else {
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
            }else{
                $this->getLogger()->addDebug(
                    'Start running all for plugin '.$plugin->getTitle()
                );
                $results[] = $plugin->runAll();
                $this->getLogger()->addDebug(
                    'End running all for plugin '.$plugin->getTitle()
                );
            }
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
        /* @var \PhpGuard\Application\Event\ProcessEvent $resultEvent */
        /* @var \PhpGuard\Application\Event\ResultEvent $event */
        $logger = $container->get('logger');

        foreach($results as $resultEvent)
        {
            foreach($resultEvent->getResults() as $event)
            {
                $status = $event->getResult();
                switch($status){
                    case ResultEvent::SUCCEED:
                        $logger->addSuccess($event->getMessage());
                        break;
                    case ResultEvent::FAILED:
                        $logger->addFail($event->getMessage());
                        break;
                    case ResultEvent::BROKEN:
                        $logger->addFail($event->getMessage());
                        break;
                    case ResultEvent::ERROR:
                        $logger->addFail($event->getMessage());
                        $this->printTrace($event->getTrace());
                        break;
                }
            }
        }
    }

    private function printTrace($trace)
    {
        $writer = $this->container->get('ui.output');
        for ($i = 0, $count = count($trace); $i < $count; $i++) {
            $writer->writeln($trace[$i]);
        }
    }
}