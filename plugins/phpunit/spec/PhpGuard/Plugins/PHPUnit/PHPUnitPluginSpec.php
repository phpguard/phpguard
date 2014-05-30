<?php

namespace spec\PhpGuard\Plugins\PHPUnit;

use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Util\Runner;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpGuard\Listen\Util\PathUtil;
use PhpGuard\Plugins\PHPUnit\Inspector;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;

class PHPUnitPluginSpec extends ObjectBehavior
{
    function let(
        ContainerInterface $container,
        OutputInterface $output,
        PhpGuard $phpGuard,
        Inspector $inspector
    )
    {
        $container->get('ui.output')
            ->willReturn($output);
        $container->get('phpguard')
            ->willReturn($phpGuard);

        $container->get('phpunit.inspector')
            ->willReturn($inspector);

        $logger = new Logger('PhpUnit');
        $this->setLogger($logger);
        $this->setContainer($container);

    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PHPUnit\PHPUnitPlugin');
    }

    function it_should_set_default_options()
    {
        $this->setOptions(array());
        $options = $this->getOptions();
        $options->shouldHaveKey('cli');
    }

    function its_run_should_delegate_run_to_inspector(
        Inspector $inspector
    )
    {
        $inspector->run(array('some_path'))
            ->shouldBeCalled()
            ->willReturn('result');
        $this->run(array('some_path'))->shouldReturn('result');
    }

    function it_should_delegate_run_all_to_inspector(
        Inspector $inspector
    )
    {
        $inspector->runAll()
            ->shouldBeCalled()
            ->willReturn('result')
        ;

        $this->runAll();
    }
}