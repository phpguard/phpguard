<?php

namespace spec\PhpGuard\Application\Console;

use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpSpec\Console\Application;
use Prophecy\Argument;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShellSpec extends ObjectBehavior
{
    function let(
        ContainerInterface $container,
        PhpGuard $phpGuard,
        Application $application,
        OutputInterface $output,
        OutputFormatterInterface $outputFormatter,
        EventDispatcherInterface $dispatcher
    )
    {
        $container->get('phpguard')->willReturn($phpGuard);
        $container->get('ui.application')->willReturn($application);
        $container->get('ui.output')->willReturn($output);
        $container->get('dispatcher')->willReturn($dispatcher);
        $output->getFormatter()->willreturn($outputFormatter);
        $this->beConstructedWith($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Console\Shell');
    }

    function it_should_delegate_run_command(Application $application)
    {
        $application->run(Argument::any(),Argument::any())
            ->shouldBeCalled();
        $application->getName()->willReturn('phpguard');
        $this->runCommand('help');
    }

    function it_should_quit_application(
        EventDispatcherInterface $dispatcher
    )
    {
        $dispatcher->dispatch(ApplicationEvents::terminated,Argument::any())
            ->shouldBeCalled();
        $this->runCommand('quit');
    }
}