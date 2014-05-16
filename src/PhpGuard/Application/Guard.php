<?php

namespace PhpGuard\Application;

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Monolog\Logger;
use PhpGuard\Application\Console\LogHandler as LogerHandler;
use PhpGuard\Application\Console\Shell;
use PhpGuard\Application\Event\EvaluateEvent;
use PhpGuard\Application\Exception\ConfigurationException;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\Interfaces\PluginInterface;
use PhpGuard\Application\Listener\ConfigurationListener;
use PhpGuard\Listen\Adapter\Inotify\InotifyAdapter;
use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpGuard\Application\Listener\ChangesetListener;
use PhpGuard\Listen\Events;
use PhpGuard\Plugins\PhpSpec\PhpSpecPlugin;
use \PhpGuard\Listen\Listen;
use PhpGuard\Plugins\PHPUnit\PHPUnitPlugin;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PhpGuard
 *
 */
class Guard
{
    const VERSION = '1.0.0-dev';

    private $watchers = array();

    /**
     * @var Container
     */
    private $container;

    private $options;

    public function addWatcher($definition)
    {
        $watcher = new Watcher();
        foreach($definition as $name => $value){
            if(!$watcher->hasOption($name)){
                throw new ConfigurationException(sprintf(
                    'Watcher do not have "%s" configuration.',
                    $name
                ));
            }
            call_user_func(array($watcher,'setOption'),$name,$value);

        }
        $this->watchers[] = $watcher;
        return $watcher;
    }

    public function getWatchers()
    {
        return $this->watchers;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function setupServices(ContainerInterface $container)
    {
        $container->set('guard',$this);
        $container->set('guard.config',function(){
            return new Configuration();
        });

        $container->set('guard.ui.output',new ConsoleOutput());

        $container->setShared('guard.dispatcher', function ($c) {
            $dispatcher = new EventDispatcher;

            array_map(
                array($dispatcher, 'addSubscriber'),
                $c->getByPrefix('guard.dispatcher.listeners')
            );

            return $dispatcher;
        });

        $container->setShared('guard.logger.handler',function($c){
            $handler = new Console\LogHandler($c->getParameter('guard.log_level'));
            $handler->setLevel(LogLevel::DEBUG);
            return $handler;
        });

        $container->setShared('guard.logger',function($c){
            $logger = new Logger('Guard');
            $logger->pushHandler($c->get('guard.logger.handler'));
            return $logger;
        });

        $container->set('guard.dispatcher.listeners.config',function($c){
            return new ConfigurationListener();
        });

        $container->set('guard.dispatcher.listeners.changeset',function($c){
            return new ChangesetListener();
        });

        $container->setShared('guard.ui.shell',function($c){
            $shell = new Shell($c);
            return $shell;
        });
        $this->container = $container;
    }

    public function setupListen(ContainerInterface $container)
    {
        $container->setShared('guard.listen.adapter',function($c){
            return Listen::getDefaultAdapter();
        });

        $container->setShared('guard.listen.listener',function($c){
            $listener = Listen::to(getcwd());
            $listener
                //->setLogger($c->get('guard.logger'))
                ->callback(array($c->get('guard'),'listen'))
            ;
            return $listener;
        });
    }

    public function loadPlugins()
    {
        $this->container->setShared('guard.plugins.phpspec',function(){
            return new PhpSpecPlugin();
        });
        $this->container->setShared('guard.plugins.phpunit',function(){
            return new PHPUnitPlugin();
        });
    }

    public function loadConfiguration()
    {
        $event = new GenericEvent($this);
        $dispatcher = $this->container->get('guard.dispatcher');
        $dispatcher->dispatch(PhpGuardEvents::CONFIG_PRE_LOAD,$event);

        $this->container->get('guard.config')
            ->compileFile(getcwd().'/phpguard.yml')
        ;
        $dispatcher->dispatch(PhpGuardEvents::CONFIG_POST_LOAD,$event);
    }

    public function start()
    {
        /* @var \PhpGuard\Listen\Listener */
        $listener = $this->container->get('guard.listen.listener');
        if(isset($this->options['ignores'])){
            foreach($this->options['ignores'] as $ignored){
                $listener->ignores($ignored);
            }
        }

        $this->log('<info>Starting to watch at <comment>{path}</comment></info>',array('path'=>getcwd()));
        $listener->start();
    }

    public function listen(ChangeSetEvent $event)
    {
        $this->getContainer()->get('guard.dispatcher')
            ->dispatch(PhpGuardEvents::POST_EVALUATE,new EvaluateEvent($event));
    }

    public function setOptions(array $options=array())
    {
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    private function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'ignores' => array(),
        ));

        $resolver->setNormalizers(array(
            'ignores' => function($value){
                    if(!is_array($value)){
                        return array($value);
                    }
            }
        ));
    }

    public function log($message, $context=array(),$level = LogLevel::INFO)
    {
        /* @var \Psr\Log\LoggerInterface $logger */
        $logger = $this->container->get('guard.logger');
        $logger->log($level,$message,$context);
    }
}