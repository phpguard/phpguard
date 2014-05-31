<?php

namespace spec\PhpGuard\Application\Console;

use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpSpec\Console\Application;
use Prophecy\Argument;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShellSpec extends ObjectBehavior
{
    public function let(
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
        $output->write(Argument::any())->willReturn();

        $this->beAnInstanceOf('PhpGuard\\Application\\Test\\TestShell',array($container));
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Console\Shell');
    }

    public function it_should_quit_application(
        EventDispatcherInterface $dispatcher
    )
    {
        $dispatcher->dispatch(ApplicationEvents::terminated,Argument::any())
            ->shouldBeCalled();
        $this->runCommand('quit');
    }

    public function it_should_run_command(
        Application $application,
        OutputInterface $output
    )
    {
        $application->getName()->willReturn('some');
        $application
            ->run(Argument::any(function (StringInput $value) {
                return $value->getFirstArgument()==='some';
            }),$output)->shouldBeCalled()
            ->willReturn('retval')
        ;
        $this->runCommand('some')->shouldReturn('retval');
    }

    public function it_should_run_all_when_command_is_false(
        Application $application,
        OutputInterface $output
    )
    {
        $application->getName()->willReturn('some');
        $application
            ->run(Argument::that(function (StringInput $value) {
                return $value->getFirstArgument() == 'all';
            }),$output)->shouldBeCalled()
            ->willReturn('retval')
        ;
        $this->runCommand(false)->shouldReturn('retval');
    }

    public function it_should_show_prompt(
        Application $application,
        OutputInterface $output,
        OutputFormatterInterface $outputFormatter
    )
    {
        $application
            ->getName()
            ->willReturn('some')
            ->shouldBeCalled()
        ;
        $outputFormatter->format(Argument::containingString('some'))
            ->shouldBeCalled()
            ->willReturn('prompt')
        ;
        $output->write('prompt')
            ->shouldBeCalled();
        $this->showPrompt();
    }

    public function it_should_read_user_input(
        OutputInterface $output,
        Application $application
    )
    {
        $application
            ->run(Argument::that(function (StringInput $value) {
                return $value->getFirstArgument()==='all';
            }),$output)
            ->shouldBeCalled()
            ->willReturn('retval')
        ;
        $application->getName()->shouldNotBeCalled();
        $this->readline(false);

        // show prompt when true
        $application->getName()->shouldBeCalled();
        $this->readline(true);
    }
}
