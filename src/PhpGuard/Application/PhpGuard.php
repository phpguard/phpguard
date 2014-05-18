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

use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpGuard\Listen\Listen;
use PhpGuard\Application\Console\Shell;
use PhpGuard\Application\Event\EvaluateEvent;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\Listener\ConfigurationListener;
use PhpGuard\Application\Listener\ChangesetListener;
use PhpGuard\Plugins\PhpSpec\PhpSpecPlugin;
use PhpGuard\Plugins\PHPUnit\PHPUnitPlugin;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PhpGuard
 *
 */
class PhpGuard
{
    const VERSION = '1.0.0-dev';
    /**
     * @var Container
     */
    private $container;

    private $options = array(
        'ignores' => array(),
    );

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function setupServices()
    {
        $container = $this->container;
        $container->set('phpguard',$this);

        $container->setShared('phpguard.config',function(){
            return new Configuration();
        });

        $container->setShared('phpguard.dispatcher', function ($c) {
            $dispatcher = new EventDispatcher;

            array_map(
                array($dispatcher, 'addSubscriber'),
                $c->getByPrefix('phpguard.dispatcher.listeners')
            );

            return $dispatcher;
        });

        /*$container->setShared('phpguard.logger.handler',function($c){
            $handler = new Console\LogHandler($c->getParameter('phpguard.log_level'));
            $handler->setLevel(LogLevel::INFO);
            return $handler;
        });

        $container->setShared('phpguard.logger',function($c){
            $logger = new Logger('PhpGuard');
            $logger->pushHandler($c->get('phpguard.logger.handler'));
            return $logger;
        });*/

        $container->setShared('phpguard.dispatcher.listeners.config',function(){
            return new ConfigurationListener();
        });

        $container->setShared('phpguard.dispatcher.listeners.changeset',function(){
            return new ChangesetListener();
        });

        $container->setShared('phpguard.ui.shell',function($c){
            $shell = new Shell($c);
            return $shell;
        });
        $this->container = $container;
    }

    public function setupListen()
    {
        $container = $this->container;

        $container->setShared('phpguard.listen.listener',function($c){
            $listener = Listen::to(getcwd());
            $options = $c->get('phpguard')->getOptions();
            foreach($options['ignores'] as $ignored){
                $listener->ignores($ignored);
            }

            $phpguard = $c->get('phpguard');
            $listener->latency($options['latency']);
            $listener->callback(array($phpguard,'listen'));
            return $listener;
        });
    }

    public function loadPlugins()
    {
        $this->container->setShared('phpguard.plugins.phpspec',function(){
            return new PhpSpecPlugin();
        });
        $this->container->setShared('phpguard.plugins.phpunit',function(){
            return new PHPUnitPlugin();
        });
    }

    public function loadConfiguration()
    {
        $event = new GenericEvent($this);
        $dispatcher = $this->container->get('phpguard.dispatcher');
        $dispatcher->dispatch(PhpGuardEvents::CONFIG_PRE_LOAD,$event);

        if(!is_file($configFile=getcwd().'/phpguard.yml')){
            $configFile = getcwd().'/phpguard.yml.dist';
        }
        $this->container->get('phpguard.config')
            ->compileFile($configFile)
        ;
        $dispatcher->dispatch(PhpGuardEvents::CONFIG_POST_LOAD,$event);
    }

    public function start()
    {
        /* @var \PhpGuard\Listen\Listener $listener */
        $listener = $this->container->get('phpguard.listen.listener');

        $this->log('Starting to watch at <comment>'.getcwd().'</comment>');
        $listener->start();
    }

    public function listen(ChangeSetEvent $event)
    {
        $files = $event->getFiles();
        if(!empty($files)){
            $this->log();
            $dispatcher = $this->container->get('phpguard.dispatcher');
            $evaluateEvent = new EvaluateEvent($event);
            $dispatcher->dispatch(
                PhpGuardEvents::PRE_RUN_COMMANDS,
                $evaluateEvent
            );

            $dispatcher->dispatch(
                PhpGuardEvents::POST_EVALUATE,
                $evaluateEvent
            );

            $dispatcher->dispatch(
                PhpGuardEvents::POST_RUN_COMMANDS,
                $evaluateEvent
            );
        }
    }

    public function setOptions(array $options=array())
    {
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    public function getOptions()
    {
        return $this->options;
    }

    private function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'ignores' => array(),
            'latency' => 1000000,
        ));

        $resolver->setNormalizers(array(
            'ignores' => function(Options $options,$value){
                if(!is_array($value)){
                    $value = array($value);
                }
                return $value;
            }
        ));
    }

    /**
     * @param string $message
     */
    public function log($message=null,$channel='PhpGuard', $level=OutputInterface::VERBOSITY_NORMAL)
    {
        /* @var \Symfony\Component\Console\Output\OutputInterface $output */
        $output = $this->container->get('phpguard.ui.output');
        if(is_null($message)){
            $output->writeln("");
            return;
        }

        if($level > $output->getVerbosity()){
            return;
        }

        $time = new \DateTime();
        $format = "[%s][%s] %s";

        $message = sprintf($format,$time->format('H:i:s'),$channel,$message);

        $output->writeln('<info>'.$message.'</info>');
    }
}