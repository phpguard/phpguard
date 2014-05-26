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

use PhpGuard\Application\Configuration\ConfigEvents;
use PhpGuard\Application\Configuration\Processor;
use PhpGuard\Application\Console\Command\RunAllCommand;
use PhpGuard\Application\Console\Command\StartCommand;
use PhpGuard\Application\Listener\ApplicationListener;
use PhpGuard\Application\Log\ConsoleFormatter;
use PhpGuard\Application\Log\ConsoleHandler;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Util\Locator;
use PhpGuard\Application\Event\GenericEvent;
use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpGuard\Listen\Listen;
use PhpGuard\Application\Event\EvaluateEvent;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Listener\ConfigurationListener;
use PhpGuard\Application\Listener\ChangesetListener;
use PhpGuard\Plugins\PhpSpec\PhpSpecPlugin;
use PhpGuard\Plugins\PHPUnit\PHPUnitPlugin;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PhpGuard
 */
class PhpGuard
{
    const VERSION = '1.0.0-dev';
    const EXIT_MESSAGE = 'Exit PhpGuard. <comment>Bye... Bye...</comment>';

    /**
     * @var Container
     */
    protected $container;

    protected $options = array();

    protected $running = true;

    public function __construct()
    {
        // force to setup default values
        $this->setOptions(array());
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function setupListeners(ContainerInterface $container)
    {
        $container->setShared('dispatcher.listeners.application',function(){
            return new ApplicationListener();
        });

        $container->setShared('dispatcher.listeners.config',function(){
            return new ConfigurationListener();
        });

        $container->setShared('dispatcher.listeners.changeset',function(){
            return new ChangesetListener();
        });
    }

    public function setupServices(ContainerInterface $container)
    {
        $container->setShared('config',function(){
            return new Processor();
        });

        $container->setShared('dispatcher', function ($c) {
            $dispatcher = new EventDispatcher;

            array_map(
                array($dispatcher, 'addSubscriber'),
                $c->getByPrefix('dispatcher.listeners')
            );

            return $dispatcher;
        });

        $container->setShared('logger.handler', function($c){
            $format = "%start_tag%[%datetime%][%channel%][%level_name%] %message% %context% %extra% %end_tag%\n";
            $formatter = new ConsoleFormatter($format);
            $handler = new ConsoleHandler(null,true);
            $handler->setFormatter($formatter);
            $c->get('dispatcher')->addSubscriber($handler);
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
            return $listener;
        });

        $container->setShared('listen.adapter',function(){
            $adapter = Listen::getDefaultAdapter();
            return $adapter;
        });

        $container->setShared('locator',function(){
            $locator = new Locator();
            $cwd = getcwd();
            // TODO: load this automatically
            $locator->addPsr4('spec\\',array(
                $cwd.'/spec',
                $cwd.'/plugins/phpspec/spec',
                $cwd.'/plugins/phpunit/spec'
            ));


            return $locator;
        });
    }

    public function setupCommands($container)
    {
        $container->setShared('commands.start',function(){
            $command = new StartCommand();
            return $command;
        });

        $container->setShared('commands.run_all',function(){
            return new RunAllCommand();
        });
    }

    public function loadPlugins(ContainerInterface $container)
    {
        $container->setShared('plugins.phpspec',function(){
            $plugin = new PhpSpecPlugin();
            return $plugin;
        });
        $container->setShared('plugins.phpunit',function(){
            return new PHPUnitPlugin();
        });
        $container->setShared('linters.php',function($c){
            $linter = new Linter\PhpLinter();
            $linter->setContainer($c);
            return $linter;
        });
    }

    public function listen(ChangeSetEvent $event)
    {
        $files = $event->getFiles();
        if(empty($files)){
            return;
        }

        $container = $this->container;
        $dispatcher = $container->get('dispatcher');
        $configFile = $container->getParameter('config.file');

        if(in_array($configFile,$files)){
            $container->get('logger.handler')->reset();
            $container->get('logger')->addCommon("Reloading Configuration");
            $reloadEvent = new GenericEvent($container);
            $dispatcher->dispatch(ConfigEvents::RELOAD,$reloadEvent);
            $container->get('logger')->addCommon('Configuration Reloaded');
            $container->get('ui.shell')->showPrompt();
        }


        $evaluateEvent = new EvaluateEvent($event);
        $dispatcher->dispatch(
            ApplicationEvents::postEvaluate,
            $evaluateEvent
        );
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

    public function start()
    {
        $container = $this->container;
        $dispatcher = $container->get('dispatcher');

        $event = new GenericEvent($container);
        $dispatcher->dispatch(ApplicationEvents::started,$event);

        $application = $container->get('ui.application');
        $application->setAutoExit(false);
        $application->setCatchExceptions(true);

        $shell = $container->get('ui.shell');
        $this->showHeader();
        $shell->showPrompt();

        while($this->running){
            $return = $shell->run();
            if(!$return){
                $this->stop();
            }
            $this->evaluate();
        }
    }

    /**
     * Returns the shell header.
     *
     * @return string The header string
     */
    public function showHeader()
    {
        $version = static::VERSION;
        $header = <<<EOF

Welcome to the <info>PhpGuard</info> (<comment>{$version}</comment>).

At the prompt, type <comment>help</comment> for some help,
or <comment>list</comment> to get a list of available commands.

To exit the shell, type <comment>quit</comment>.
To run all commands, type <comment>Control+D</comment> or <comment>run-all</comment>

EOF;

        $this->container->get('ui.output')
            ->writeln($header);
    }

    public function evaluate()
    {
        try{
            $this->container->get('listen.listener')->evaluate();
        }catch(\Exception $e){
            $this->container->get('ui.application')->renderException($e,$this->container->get('ui.output'));
        }
    }

    public function stop()
    {
        $this->running = false;
    }

    public function isRunning()
    {
        return $this->running;
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
}
