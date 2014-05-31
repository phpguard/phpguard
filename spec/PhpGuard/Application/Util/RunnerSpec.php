<?php

namespace spec\PhpGuard\Application\Util;

use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Log\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class RunnerSpec extends ObjectBehavior
{
    public function let(
        ContainerInterface $container,
        OutputInterface $output,
        Logger $logger,
        Process $process,
        ProcessBuilder $builder
    )
    {
        $builder->getProcess()->willReturn($process);
        $process->getCommandLine()
            ->willReturn('some_command');
        $process->getExitCode()
            ->willReturn(0);
        $process->getExitCodeText()
            ->willReturn('exit_code_text');

        $container->get('runner.logger')->willReturn($logger);
        $container->get('ui.output')->willReturn($output);
        $container->getParameter('runner.tty',false)->willReturn(false);
        $container->getParameter('runner.default_dirs')->willReturn(array());
        $this->setContainer($container);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Util\Runner');
    }

    public function it_should_run_process_builder(
        ProcessBuilder $builder,
        Process $process
    )
    {
        $process->run(Argument::any())
            ->shouldBeCalled();
        $process->setTty(false)
            ->shouldBeCalled();
        $this->run($builder)->shouldHaveType('Symfony\\Component\\Process\\Process');
    }

    public function it_should_find_executable(
        ContainerInterface $container
    )
    {
        $container->hasParameter($id = 'runner.default_dirs')
            ->willReturn(false);
        $container->setParameter($id,Argument::any())->shouldBeCalled();
        $this->findExecutable('foo')->shouldReturn(false);
        $this->findExecutable('php')->shouldNotReturn(false);
        $this->findExecutable('phpspec')->shouldNotReturn(false);
    }

}
