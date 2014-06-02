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
use PhpGuard\Application\Event\ProcessEvent;
use PhpGuard\Application\Event\ResultEvent;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Event\EvaluateEvent;
use PhpGuard\Application\Event\GenericEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
            //ApplicationEvents::postEvaluate =>
            ApplicationEvents::evaluate => 'evaluate',
            ApplicationEvents::postEvaluate => array('showPrompt',-1000),
            ApplicationEvents::preRunCommand => 'preRunCommand',
            ApplicationEvents::postRunCommand => 'postRunCommand',
            ApplicationEvents::runAll => 'runAllCommand',

        );
    }

    public function evaluate(EvaluateEvent $event)
    {
        /* @var \PhpGuard\Application\Plugin\PluginInterface $plugin */
        /* @var \PhpGuard\Application\Log\ConsoleHandler $loggerHandler */

        $container = $this->container;

        // always set exit code to zero first
        $container->setParameter('application.exit_code',0);
        $loggerHandler = $container->get('logger.handler');
        $exception = null;
        $loggerHandler->reset();

        foreach ($container->getByPrefix('plugins') as $plugin) {
            if (!$plugin->isActive()) {
                continue;
            }
            $paths = $plugin->getMatchedFiles($event);
            if (count($paths) > 0) {
                $this->preRunCommand($paths);
                try {
                    $result = $plugin->run($paths);
                    if ($result) {
                        $event->addProcessEvent($result);
                    }
                } catch (\Exception $e) {
                    $resultEvent = new ResultEvent(ResultEvent::ERROR,$e->getMessage(),array(),$e);
                    $processEvent = new ProcessEvent($plugin,array($resultEvent));
                    $event->addProcessEvent($processEvent);
                }
                $this->postRunCommand();
            }
        }
    }

    public function showPrompt()
    {
        $loggerHandler = $this->container->get('logger.handler');
        if (count($this->results) > 0 || $loggerHandler->isLogged()) {
            $this->container->get('ui.shell')->showPrompt();
        }
        $loggerHandler->reset();
    }

    public function preRunCommand($paths)
    {
        $shell = $this->getShell();
        foreach ($paths as $path) {
            $this->getLogger()->addDebug(
                'Match file: '.$path
            );
        }
        $shell->unsetStreamBlocking();
    }

    public function postRunCommand()
    {
        $shell = $this->getShell();
        $shell->setStreamBlocking();
    }

    public function runAllCommand(GenericEvent $event)
    {
        /* @var \PhpGuard\Application\Plugin\PluginInterface $plugin */
        $this->container->setParameter('application.exit_code',0);
        if ( is_null($plugin = $event->getArgument('plugin')) ) {
            $plugins = $this->container->getByPrefix('plugins');
        } else {
            $name = 'plugins.'.$plugin;
            if ($this->container->has($name)) {
                $plugin = $this->container->get('plugins.'.$plugin);
                $plugins = array($plugin);
            } else {
                throw new \RuntimeException(sprintf(
                        'Plugin "%s" is not registered',
                        $plugin
                ));
            }
        }

        foreach ($plugins as $plugin) {
            if (!$plugin->isActive()) {
                $this->getLogger()->addDebug(sprintf('Plugin "%s" is not active',$plugin->getTitle()));
                continue;
            } else {
                $this->getLogger()->addDebug(
                    'Start running all for plugin '.$plugin->getTitle()
                );
                $event->addProcessEvent($plugin->runAll());
                $this->getLogger()->addDebug(
                    'End running all for plugin '.$plugin->getTitle()
                );
            }
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
}
