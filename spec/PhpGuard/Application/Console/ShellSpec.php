<?php

namespace spec\PhpGuard\Application\Console;

use PhpGuard\Application\Console\Application;
use PhpGuard\Application\Console\Shell;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Listen\Listener;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MockShell extends Shell
{
    public function run()
    {

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
        $this->stop();
        $this->shouldNotBeRunning();

        $this->start();
        $this->shouldBeRunning();
    }
}