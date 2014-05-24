<?php

namespace spec\PhpGuard\Application;

use PhpGuard\Application\Configuration;
use PhpGuard\Application\Console\Application;
use PhpGuard\Application\Console\ShellInterface;
use \PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpGuard\Listen\Listener;
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
        EventDispatcherInterface $dispatcher,
        ShellInterface $shell,
        Configuration $configuration,
        Application $application,
        Listener $listener
    )
    {
        $container->get('ui.application')
            ->willReturn($application)
        ;
        $container->get('ui.output')
            ->willReturn($output);
        $container->get('ui.shell')
            ->willReturn($shell)
        ;
        $container->get('listen.listener')
            ->willReturn($listener);

        $container->get('dispatcher')
            ->willReturn($dispatcher)
        ;

        $container->get('config')
            ->willReturn($configuration)
        ;

        $output->getVerbosity()
            ->willReturn(ConsoleOutput::VERBOSITY_NORMAL);

        $this->setContainer($container);
        $output->writeln(Argument::any())
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

    function it_should_listen_properly(
        EventDispatcherInterface $dispatcher,
        ChangeSetEvent $event
    )
    {
        $event->getFiles()
            ->willReturn(array('some_file'));
        $dispatcher->dispatch(ApplicationEvents::postEvaluate,Argument::cetera())
            ->shouldBeCalled();
        $this->listen($event);
    }

    function it_should_run_shell_when_started(ShellInterface $shell)
    {
        $shell->run()
            ->shouldBeCalled()
            ->willReturn(false)
        ;
        $shell->showPrompt()
            ->shouldBeCalled();

        $this->start();
    }
}