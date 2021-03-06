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

use PhpGuard\Application\Bridge\CodeCoverage\CodeCoverageSession;
use PhpGuard\Application\Configuration\ConfigEvents;
use PhpGuard\Application\Configuration\Processor;
use PhpGuard\Application\Console\Command\RunAllCommand;
use PhpGuard\Application\Console\Command\StartCommand;
use PhpGuard\Application\Listener\ApplicationListener;
use PhpGuard\Application\Log\ConsoleFormatter;
use PhpGuard\Application\Log\ConsoleHandler;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Util\Filesystem;
use PhpGuard\Application\Util\Locator;
use PhpGuard\Application\Event\GenericEvent;
use PhpGuard\Application\Util\Report;
use PhpGuard\Application\Util\Runner;
use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpGuard\Listen\Listen;
use PhpGuard\Application\Event\EvaluateEvent;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Listener\ConfigurationListener;
use PhpGuard\Application\Listener\ChangesetListener;
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

    static public function getCacheDir()
    {
        $path = getcwd();
        if(false!==strpos($path,'phpguard-test')){
            return $path;
        }else{
            $hash = crc32($path);
            $dir = sys_get_temp_dir().'/phpguard/cache/'.$hash;
            @mkdir($dir,0755,true);
            return $dir;
        }
    }

    static public function getPluginCache($plugin)
    {
        $cache = static::getCacheDir();
        $dir = $cache.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.$plugin;
        if (!is_dir($dir)) {
            mkdir($dir,0755,true);
        }

        return $dir;
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function setupListeners(ContainerInterface $container)
    {
        $container->setShared('dispatcher.listeners.application',function () {
            return new ApplicationListener();
        });

        $container->setShared('dispatcher.listeners.config',function () {
            return new ConfigurationListener();
        });

        $container->setShared('dispatcher.listeners.changeset',function () {
            return new ChangesetListener();
        });

        $container->setShared('dispatcher.listeners.report',function(){
            return new Report();
        });
    }

    public function setupServices(ContainerInterface $container)
    {
        $container->setShared('config',function () {
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

        $container->setShared('logger.handler', function ($c) {
            $format = "%start_tag%[%datetime%][%channel%][%level_name%] %message% %context% %extra% %end_tag%\n";
            $formatter = new ConsoleFormatter($format);
            $handler = new ConsoleHandler(null,true);
            $handler->setFormatter($formatter);

            return $handler;
        });

        $container->setShared('logger', function ($c) {
            $logger = new Logger('Main');
            $logger->pushHandler($c->get('logger.handler'));

            return $logger;
        });

        $container->setShared('listen.listener',function ($c) {
            $listener = Listen::to(getcwd());
            $options = $c->get('phpguard')->getOptions();
            foreach ($options['ignores'] as $ignored) {
                $listener->ignores($ignored);
            }

            $phpguard = $c->get('phpguard');
            $listener->latency($options['latency']);
            $listener->callback(array($phpguard,'listen'));

            return $listener;
        });

        $container->setShared('listen.adapter',function () {
            $adapter = Listen::getDefaultAdapter();

            return $adapter;
        });

        $container->setShared('locator',function () {
            $locator = new Locator();

            return $locator;
        });
        $container->setShared('dispatcher.listeners.locator',function ($c) {
            return $c->get('locator');
        });

        $container->setShared('runner.logger',function ($c) {
            $logger = new Logger('Runner');
            $logger->pushHandler($c->get('logger.handler'));

            return $logger;
        });

        $container->setShared('runner',function () {
            return new Runner();
        });

        $container->setShared('filesystem',function(){
            return new Filesystem();
        });

        CodeCoverageSession::setupContainer($container);
    }

    public function setupCommands($container)
    {
        $container->setShared('commands.start',function () {
            $command = new StartCommand();

            return $command;
        });

        $container->setShared('commands.run_all',function () {
            return new RunAllCommand();
        });
    }

    public function listen(ChangeSetEvent $event)
    {
        $files = $event->getFiles();
        if (empty($files)) {
            return;
        }

        $container = $this->container;
        $dispatcher = $container->get('dispatcher');
        $configFile = $container->getParameter('config.file');

        if (in_array($configFile,$files)) {
            $container->get('logger.handler')->reset();
            $container->get('logger')->addCommon("Reloading Configuration");
            $reloadEvent = new GenericEvent($container);
            $dispatcher->dispatch(ConfigEvents::RELOAD,$reloadEvent);
            $container->get('logger')->addCommon('Configuration Reloaded');
            $container->get('ui.shell')->showPrompt();
        }

        $evaluateEvent = new EvaluateEvent($event);
        $dispatcher->dispatch(
            ApplicationEvents::preEvaluate,
            $evaluateEvent
        );
        $dispatcher->dispatch(
            ApplicationEvents::evaluate,
            $evaluateEvent
        );
        $dispatcher->dispatch(
            ApplicationEvents::postEvaluate,
            $evaluateEvent
        );
    }

    public function setOptions(array $options=array())
    {
        if (isset($options['coverage'])) {
            $this->container->get('coverage.session')->setOptions($options['coverage']);
            unset($options['coverage']);
        }

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

        $application = $container->get('ui.application');
        $application->setAutoExit(false);
        $application->setCatchExceptions(true);

        $dispatcher = $container->get('dispatcher');
        $event = new GenericEvent($container);
        $dispatcher->dispatch(ApplicationEvents::started,$event);

        $shell = $container->get('ui.shell');
        $this->showHeader();
        $shell->showPrompt();
        while ($this->running) {
            $return = $shell->run();
            if (!$return) {
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
        $this->container->get('listen.listener')->evaluate();
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
            'coverage' => array(),
        ));

        $resolver->setNormalizers(array(
            'ignores' => function (Options $options,$value) {
                if (!is_array($value)) {
                    $value = array($value);
                }

                return $value;
            }
        ));
    }
}
