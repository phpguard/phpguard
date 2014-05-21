<?php

namespace spec\PhpGuard\Application;

use PhpGuard\Application\Configuration;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\PhpGuardEvents;
use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PhpGuardSpec extends ObjectBehavior
{
    static $cwd;

    function let(
        ContainerInterface $container,
        ConsoleOutput $output,
        OutputFormatter $formatter,
        EventDispatcherInterface $dispatcher
    )
    {
        $container->get('ui.output')
            ->willReturn($output);
        $container->get('dispatcher')
            ->willReturn($dispatcher)
        ;
        $output->getVerbosity()
            ->willReturn(ConsoleOutput::VERBOSITY_NORMAL);

        $this->setContainer($container);
        $output->writeln('')
            ->willReturn(null);

        if(!is_dir(self::$cwd)){
            self::$cwd = getcwd();
        }
    }

    function letgo()
    {
        chdir(self::$cwd);
        self::cleanDir(self::$tmpDir.'/test-config');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\PhpGuard');
    }

    function it_should_set_default_options()
    {
        $this->setOptions(array());
        $options = $this->getOptions();

        $options->shouldHaveKey('ignores');
        $options->shouldHaveKey('latency');
    }

    function it_setup_services(ContainerInterface $container)
    {
        $container->set('phpguard',$this)
            ->shouldBeCalled();
        $container->setShared('config',Argument::cetera())
            ->shouldBeCalled();
        $container->setShared('dispatcher',Argument::cetera())
            ->shouldBeCalled();
        $container->setShared('dispatcher.listeners.config',Argument::cetera())
            ->shouldBeCalled();
        $container->setShared('dispatcher.listeners.changeset',Argument::cetera())
            ->shouldBeCalled();

        $container->setShared('listen.listener',Argument::cetera())
            ->shouldBeCalled();
        $container->setShared('listen.adapter',Argument::cetera())
            ->shouldBeCalled();

        $this->setupServices();
    }

    function it_should_listen_properly(
        ContainerInterface $container,
        EventDispatcherInterface $dispatcher,
        ChangeSetEvent $event,
        ConsoleOutput $output
    )
    {
        $event->getFiles()
            ->willReturn(array('some_file'));
        $dispatcher->dispatch(PhpGuardEvents::postEvaluate,Argument::cetera())
            ->shouldBeCalled();
        $this->listen($event);
    }

    function it_should_log_null_message(ConsoleOutput $output)
    {
        $output->writeln("")
            ->shouldBeCalled();
        $this->log();
    }

    function it_should_not_log_if_verbosity_does_not_match(ConsoleOutput $output)
    {
        $output->setVerbosity(ConsoleOutput::VERBOSITY_NORMAL);
        $output->writeln(Argument::any())
            ->shouldNotBeCalled();
        $this->log('not_visibled',ConsoleOutput::VERBOSITY_VERY_VERBOSE);
    }

    function it_should_format_log_message(ConsoleOutput $output)
    {
        $output->getVerbosity()
            ->willReturn(ConsoleOutput::VERBOSITY_DEBUG);

        $output->writeln(Argument::containingString('normal'))
            ->shouldBeCalled()
        ;

        $this->log('normal');

        $output->writeln(Argument::containingString('debug'))
            ->shouldBeCalled()
        ;
        $this->log('debug',ConsoleOutput::VERBOSITY_DEBUG);
    }

    function it_should_load_configuration(
        ContainerInterface $container,
        Configuration $config,
        EventDispatcherInterface $dispatcher
    )
    {
        self::mkdir($dir = self::$tmpDir.'/test-config');
        chdir($dir);

        $container->get('config')
            ->willReturn($config);

        $dispatcher->dispatch(PhpGuardEvents::preLoadConfig,Argument::any())
            ->shouldBeCalled();

        $dispatcher->dispatch(PhpGuardEvents::postLoadConfig,Argument::any())
            ->shouldBeCalled();

        $config->compileFile(Argument::containingString('phpguard.yml.dist'))
            ->shouldBeCalled();
        touch($dir.'/phpguard.yml.dist');
        $this->loadConfiguration();

        touch($dir.'/phpguard.yml');
        $config->compileFile(Argument::containingString('phpguard.yml'))
            ->shouldBeCalled();

        $this->loadConfiguration();
    }

    function it_throws_when_configuration_file_not_exists()
    {
        chdir(self::$tmpDir);
        $this->shouldThrow('InvalidArgumentException')
            ->duringLoadConfiguration()
        ;
    }
}