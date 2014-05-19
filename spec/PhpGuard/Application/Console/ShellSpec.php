<?php

namespace spec\PhpGuard\Application\Console;

require_once __DIR__.'/../MockFileSystem.php';
use PhpGuard\Application\Console\Application;
use PhpGuard\Application\Console\Shell;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\PhpGuardEvents;
use PhpGuard\Listen\Listener;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use spec\PhpGuard\Application\MockFileSystem as mfs;
class MockShell extends Shell
{
    private $exit;

    public function isExit()
    {
        return $this->exit;
    }

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->historyFile = mfs::$tmpDir.'/history';
    }

    public function run()
    {

    }

    public function exitShell()
    {
        $this->exit = true;
    }
}

class ShellSpec extends ObjectBehavior
{
    function let(
        ContainerInterface $container,
        Application $application,
        OutputInterface $output,
        OutputFormatterInterface $formatter,
        EventDispatcherInterface $dispatcher,
        PhpGuard $phpGuard
    )
    {
        mfs::mkdir(mfs::$tmpDir);
        $container->get('phpguard.ui.application')
            ->willReturn($application)
        ;
        $container->get('phpguard.ui.output')
            ->willReturn($output)
        ;
        $container->get('phpguard.dispatcher')
            ->willReturn($dispatcher)
        ;
        $container->get('phpguard')
            ->willReturn($phpGuard)
        ;

        $output->getFormatter()
            ->willReturn($formatter)
        ;
        $output->writeln(Argument::cetera())
            ->willReturn(null);

        $this->beAnInstanceOf(__NAMESPACE__.'\\MockShell',array($container));
    }

    function letgo()
    {
        mfs::cleanDir(mfs::$tmpDir);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Console\Shell');
    }

    function it_should_start_to_evaluate_file_system_change(ContainerInterface $container,Listener $listener)
    {
        $container->get('phpguard.listen.listener')
            ->willReturn($listener)
        ;
        $listener->evaluate()
            ->shouldBeCalled()
        ;

        $this->evaluate();
    }

    function it_can_be_started_or_stopped()
    {
        $this->start();
        $this->stop();
        $this->shouldNotBeRunning();

        $this->start();
        $this->shouldBeRunning();
    }

    function its_runCommand_should_run_all_when_argument_is_false(
        EventDispatcherInterface $dispatcher
    )
    {
        $dispatcher->dispatch(PhpGuardEvents::runAllCommands,Argument::any())
            ->shouldBeCalled();
        $this->runCommand(false);
    }

    function its_runCommand_should_run_all_when_argument_is_all(
        EventDispatcherInterface $dispatcher
    )
    {
        $dispatcher->dispatch(PhpGuardEvents::runAllCommands,Argument::any())
            ->shouldBeCalled(2);
        $this->runCommand('all phpspec');
    }

    function its_runCommand_should_execute_console_command(
        Application $application
    )
    {
        $application->setAutoExit(false)
            ->shouldBeCalled();
        $application->setCatchExceptions(true)
            ->shouldBeCalled();
        $application->getName()
            ->shouldBeCalled();

        $application->run(Argument::cetera())
            ->shouldBeCalled();

        $this->runCommand('help');
    }

    function its_runCommand_should_exit_application_when_argument_is_quit()
    {
        $this->runCommand('quit');

        $this->shouldBeExit();
    }
}