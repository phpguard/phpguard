<?php

namespace spec\PhpGuard\Application;

use PhpGuard\Application\Configuration\ConfigEvents;
use PhpGuard\Application\Configuration\Processor;
use PhpGuard\Application\Console\Application;
use PhpGuard\Application\Console\ShellInterface;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Log\ConsoleHandler;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpGuard\Listen\Listener;
use PhpGuard\Application\Util\Filesystem;
use Prophecy\Argument;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PhpGuardSpec extends ObjectBehavior
{
    static $cwd;

    public function let(
        ContainerInterface $container,
        ConsoleOutput $output,
        EventDispatcherInterface $dispatcher,
        ShellInterface $shell,
        Processor $configuration,
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

        if (!is_dir(self::$cwd)) {
            self::$cwd = getcwd();
        }
    }

    public function letgo()
    {
        chdir(self::$cwd);
        Filesystem::cleanDir(self::$tmpDir.'/test-config');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\PhpGuard');
    }

    public function it_should_set_default_options()
    {
        $this->setOptions(array());
        $options = $this->getOptions();

        $options->shouldHaveKey('ignores');
        $options->shouldHaveKey('latency');
    }

    public function it_should_evaluate_when_the_file_system_change(
        EventDispatcherInterface $dispatcher,
        ChangeSetEvent $event,
        ContainerInterface $container
    )
    {
        $container->getParameter('config.file',Argument::any())
            ->willReturn('config_file');

        $event->getFiles()
            ->willReturn(array('some_file'));
        $dispatcher->dispatch(ApplicationEvents::postEvaluate,Argument::cetera())
            ->shouldBeCalled();
        $this->listen($event);
    }

    public function it_should_reload_when_configuration_changed(
        ContainerInterface $container,
        ChangeSetEvent $event,
        EventDispatcherInterface $dispatcher,
        ConsoleHandler $handler,
        Logger $logger
    )
    {
        $container->getParameter('config.file',Argument::any())
            ->shouldBeCalled()
            ->willReturn('phpguard.yml.dist')
        ;

        $container->get('logger.handler')->willReturn($handler);
        $container->get('logger')->willReturn($logger);

        $event->getFiles()
            ->willReturn(array('phpguard.yml.dist'));

        $dispatcher->dispatch(ConfigEvents::RELOAD,Argument::any())
            ->shouldBeCalled();

        $dispatcher->dispatch(ApplicationEvents::postEvaluate,Argument::any())
            ->shouldBeCalled();

        $this->listen($event);
    }

    public function it_should_run_shell_when_started(ShellInterface $shell)
    {
        $shell->run()
            ->shouldBeCalled()
            ->willReturn(false)
        ;
        $shell->showPrompt()
            ->shouldBeCalled();

        $this->start();
    }

    public function it_should_stop_application(EventDispatcherInterface $dispatcher)
    {
        $this->stop();
        $this->shouldNotBeRunning();
    }
}
