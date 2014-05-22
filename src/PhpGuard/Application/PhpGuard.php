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

use PhpGuard\Application\Exception\ConfigurationException;
use PhpGuard\Application\Log\ConsoleFormatter;
use PhpGuard\Application\Log\ConsoleHandler;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpGuard\Listen\Listen;
use PhpGuard\Application\Event\EvaluateEvent;
use PhpGuard\Application\Container\ContainerInterface;
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

    const ERROR = 'error';

    /**
     * @var Container
     */
    private $container;

    private $options = array();

    private $hasLogged = false;

    public function __construct()
    {
        // force to setup default values
        $this->setOptions(array());
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function setupServices()
    {
        $container = $this->container;

        $container->setShared('config',function(){
            return new Configuration();
        });

        $container->setShared('dispatcher.listeners.config',function(){
            return new ConfigurationListener();
        });

        $container->setShared('dispatcher.listeners.changeset',function(){
            return new ChangesetListener();
        });

        $container->setShared('dispatcher', function ($c) {
            $dispatcher = new EventDispatcher;

            array_map(
                array($dispatcher, 'addSubscriber'),
                $c->getByPrefix('dispatcher.listeners')
            );

            return $dispatcher;
        });

        $container->setShared('logger.handler', function(){
            $format = "%start_tag%[%datetime%][%channel%.%level_name%] %message% %context% %extra% %end_tag%\n";
            $formatter = new ConsoleFormatter();

            $handler = new ConsoleHandler();


            return $handler;
        });

        $container->setShared('logger', function($c){
            $logger = new Logger('Main');
            $logger->pushHandler($c->get('logger.handler'));
            return $logger;
        });

        $container->setShared('listen.listener',function($c){
            $listener = Listen::to(getcwd());
            $options = $c->get('phpguard')->getOptions();
            foreach($options['ignores'] as $ignored){
                $listener->ignores($ignored);
            }

            $phpguard = $c->get('phpguard');
            $listener->latency($options['latency']);
            $listener->callback(array($phpguard,'listen'));

            //$logger = new Logger('Listen');
            //$logger->pushHandler($c->get('logger.handler'));
            //$listener->setLogger($logger);
            return $listener;
        });

        $container->setShared('listen.adapter',function(){
            $adapter = Listen::getDefaultAdapter();
            return $adapter;
        });

        $this->container = $container;
    }

    public function loadPlugins()
    {
        $this->container->setShared('plugins.phpspec',function(){
            $plugin = new PhpSpecPlugin();
            return $plugin;
        });
        $this->container->setShared('plugins.phpunit',function(){
            return new PHPUnitPlugin();
        });

        $this->container->setShared('linters.php',function($c){
            $linter = new Linter\PhpLinter();
            $linter->setContainer($c);
            return $linter;
        });
    }

    public function loadConfiguration()
    {
        $configFile = null;
        if(is_file($file=getcwd().'/phpguard.yml')){
            $configFile = $file;
        }elseif(is_file($file = getcwd().'/phpguard.yml.dist')){
            $configFile = $file;
        }
        if(is_null($configFile)){
            throw new ConfigurationException('Can not find configuration file "phpguard.yml" or "phpguard.yml.dist" in the current directory');
        }

        $event = new GenericEvent($this);
        $dispatcher = $this->container->get('dispatcher');
        $dispatcher->dispatch(PhpGuardEvents::preLoadConfig,$event);

        $this->container->get('config')
            ->compileFile($configFile)
        ;
        $dispatcher->dispatch(PhpGuardEvents::postLoadConfig,$event);
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
     * @param string    $message
     * @param int       $level
     * @param string    $channel
     */
    public function log($message=null,$level=OutputInterface::VERBOSITY_NORMAL,$channel='PhpGuard')
    {
        if(is_null($level)){
            $level = OutputInterface::VERBOSITY_NORMAL;
        }
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
        }
        elseif($level==self::ERROR){
            $format = '<error>'.$format.'</error>';
        }
        else{
            $format = '<info>'.$format.'</info>';
        }

        $message = sprintf($format,$time->format('H:i:s'),$channel,$message);
        if(!$this->hasLogged){
            $output->writeln("");
        }
        $output->writeln($message);
        $this->hasLogged = true;
    }

    public function hasLogged()
    {
        return $this->hasLogged;
    }

    public function resetLog()
    {
        $this->hasLogged = false;
    }

}