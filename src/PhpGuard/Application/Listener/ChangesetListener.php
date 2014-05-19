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
use PhpGuard\Application\Event\EvaluateEvent;
use Symfony\Component\Console\Output\OutputInterface;
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
            PhpGuardEvents::POST_EVALUATE => 'postEvaluate',
            PhpGuardEvents::PRE_RUN_COMMANDS => 'preRunCommand',
            PhpGuardEvents::POST_RUN_COMMANDS => 'postRunCommand',
            PhpGuardEvents::runAllCommands => 'runAllCommand',
        );
    }

    public function postEvaluate(EvaluateEvent $event)
    {
        /* @var \PhpGuard\Application\Interfaces\PluginInterface $plugin */
        $container = $this->container;

        $dispatcher = $container->get('phpguard.dispatcher');

        foreach($container->getByPrefix('phpguard.plugins') as $plugin){
            $paths = $plugin->getMatchedFiles($event);
            if(count($paths) > 0){
                $runEvent = new GenericEvent($plugin,$paths);
                $dispatcher->dispatch(
                    PhpGuardEvents::PRE_RUN_COMMANDS,
                    $runEvent
                );

                $plugin->run($paths);

                $dispatcher->dispatch(
                    PhpGuardEvents::POST_RUN_COMMANDS,
                    $runEvent
                );
            }
        }
    }

    public function preRunCommand(GenericEvent $event,$paths)
    {
        $shell = $this->getShell();
        $output = $this->container->get('phpguard.ui.output');
        $output->writeln("");
        $this->getPhpGuard()->log(
            'Begin executing '.$event->getSubject()->getName(),
            'PhpGuard',OutputInterface::VERBOSITY_DEBUG
        );
        $shell->unsetStreamBlocking();
    }

    public function postRunCommand(GenericEvent $event)
    {
        $shell = $this->getShell();
        $this->getPhpGuard()->log(
            'End executing '.$event->getSubject()->getName(),
            'PhpGuard',
            OutputInterface::VERBOSITY_DEBUG
        );
        $shell->setStreamBlocking();
        $shell->installReadlineCallback();
    }

    public function runAllCommand(GenericEvent $event)
    {
        /* @var \PhpGuard\Application\Interfaces\PluginInterface $plugin */

        $this->unsetStreamBlocking();

        $this->getPhpGuard()->log();
        $this->getPhpGuard()->log('Running all commands');
        $plugins = $this->container->getByPrefix('phpguard.plugins');
        foreach($plugins as $plugin)
        {
            $plugin->runAll();
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
        return $this->container->get('phpguard.ui.shell');
    }

    /**
     * @return \PhpGuard\Application\PhpGuard
     */
    private function getPhpGuard()
    {
        return $this->container->get('phpguard');
    }
}