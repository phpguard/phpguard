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

    private $options = array();

    public function __construct()
    {
        // force to setup default values
        $this->setOptions(array());
    }

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

        $container->setShared('config',function(){
            return new Configuration();
        });

        $container->setShared('dispatcher', function ($c) {
            $dispatcher = new EventDispatcher;

            array_map(
                array($dispatcher, 'addSubscriber'),
                $c->getByPrefix('dispatcher.listeners')
            );

            return $dispatcher;
        });

        $container->setShared('dispatcher.listeners.config',function(){
            return new ConfigurationListener();
        });

        $container->setShared('dispatcher.listeners.changeset',function(){
            return new ChangesetListener();
        });

        $container->setShared('ui.shell',function($c){
            $shell = new Shell($c);
            return $shell;
        });
        $this->container = $container;
    }

    public function setupListen()
    {
        $container = $this->container;

        $container->setShared('listen.listener',function($c){
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
        $this->container->setShared('plugins.phpspec',function(){
            return new PhpSpecPlugin();
        });
        $this->container->setShared('plugins.phpunit',function(){
            return new PHPUnitPlugin();
        });
    }

    public function loadConfiguration()
    {
        $event = new GenericEvent($this);
        $dispatcher = $this->container->get('dispatcher');
        $dispatcher->dispatch(PhpGuardEvents::preLoadConfig,$event);

        if(!is_file($configFile=getcwd().'/phpguard.yml')){
            $configFile = getcwd().'/phpguard.yml.dist';
        }
        $this->container->get('config')
            ->compileFile($configFile)
        ;
        $dispatcher->dispatch(PhpGuardEvents::postLoadConfig,$event);
    }

    public function start()
    {
        /* @var \PhpGuard\Listen\Listener $listener */
        $listener = $this->container->get('listen.listener');

        $this->log('Starting to watch at <comment>'.getcwd().'</comment>');
        $listener->start();
    }

    public function listen(ChangeSetEvent $event)
    {
        $files = $event->getFiles();
        if(!empty($files)){
            $dispatcher = $this->container->get('dispatcher');
            $evaluateEvent = new EvaluateEvent($event);
            $dispatcher->dispatch(
                PhpGuardEvents::postEvaluate,
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
     * @param null   $message
     * @param int    $level
     * @param string $channel
     */
    public function log($message=null,$level=OutputInterface::VERBOSITY_NORMAL,$channel='PhpGuard')
    {
        /* @var \Symfony\Component\Console\Output\OutputInterface $output */
        $output = $this->container->get('ui.output');
        if(is_null($message)){
            $output->writeln("");
            return;
        }

        if($level > $output->getVerbosity()){
            return;
        }

        $time = new \DateTime();
        $format = "[%s][%s] %s";
        if($level==OutputInterface::VERBOSITY_DEBUG){
            $format = '<comment>'.$format.'</comment>';
            $channel = $channel.'.DEBUG';
        }else{
            $format = '<info>'.$format.'</info>';
        }

        $message = sprintf($format,$time->format('H:i:s'),$channel,$message);
        $output->writeln($message);
    }
}