<?php

namespace spec\PhpGuard\Plugins\PHPUnit;

use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Runner;
use PhpGuard\Listen\Util\PathUtil;
use PhpGuard\Plugins\PHPUnit\PHPUnitPlugin;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

class MockPhpUnitPlugin extends PHPUnitPlugin
{
    /**
     * @var Runner
     */
    protected $runner;

    public function setRunner(Runner $runner)
    {
        $this->runner = $runner;
    }

    public function createRunner($command,array $arguments=array())
    {
        $this->runner->setCommand($command);
        $this->runner->setArguments($arguments);
        return $this->runner;
    }
}

class PHPUnitPluginSpec extends ObjectBehavior
{
    function let(
        Runner $runner,
        ContainerInterface $container,
        OutputInterface $output,
        PhpGuard $phpGuard
    )
    {
        $this->beAnInstanceOf(__NAMESPACE__.'\\MockPhpUnitPlugin');
        $this->setRunner($runner);
        $container->get('ui.output')
            ->willReturn($output);
        $container->get('phpguard')
            ->willReturn($phpGuard);
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

    function it_should_run_properly(Runner $runner,PhpGuard $phpGuard)
    {
        $runner->setCommand('phpunit')
            ->shouldBeCalled();

        $runner->run()
            ->shouldBeCalled()
            ->willReturn(true);

        $spl = PathUtil::createSplFileInfo(getcwd(),'PhpGuard\\Application\\PhpGuard');
        $runner->setArguments(Argument::containing($spl))
            ->shouldBeCalled();

        $phpGuard->log(Argument::containingString('success'),Argument::cetera())
            ->shouldBeCalled();
        $this->run(array($spl));
    }

    function it_should_log_error_when_run_command_failed(Runner $runner,PhpGuard $phpGuard)
    {
        $runner->setCommand('phpunit')
            ->shouldBeCalled();

        $runner->run()
            ->shouldBeCalled()
            ->willReturn(false);

        $spl = PathUtil::createSplFileInfo(getcwd(),'PhpGuard\\Application\\PhpGuard');
        $runner->setArguments(Argument::containing($spl))
            ->shouldBeCalled();

        $phpGuard->log(Argument::containingString('failed'),Argument::cetera())
            ->shouldBeCalled();
        $this->run(array($spl));
    }

    function it_should_run_all_after_pass_if_defined_in_options(
        Runner $runner,
        PhpGuard $phpGuard
    )
    {
        $this->setOptions(array(
            'all_after_pass' => true,
            'cli' => '--exclude-group phpspec'
        ));
        $runner->setCommand('phpunit')
            ->shouldBeCalled();

        $runner->run()
            ->shouldBeCalled()
            ->willReturn(true);

        $spl = PathUtil::createSplFileInfo(getcwd(),'PhpGuard\\Application\\PhpGuard');
        $runner->setArguments(Argument::cetera())
            ->shouldBeCalled();

        $phpGuard->log(Argument::containingString('success'),Argument::cetera())
            ->shouldBeCalled();
        $phpGuard->log(Argument::containingString('after pass'),Argument::cetera())
            ->shouldBeCalled();

        $this->run(array($spl));
    }

    function it_should_run_all_properly(Runner $runner,PhpGuard $phpGuard)
    {
        $runner->setCommand('phpunit')
            ->shouldBeCalled();

        $runner->setArguments(array())
            ->shouldBeCalled();

        $runner->run()
            ->shouldBeCalled()
            ->willReturn(true);

        $phpGuard->log(Argument::containingString('success'),Argument::cetera())
            ->shouldBeCalled();

        $this->runAll();
    }

    function it_should_log_error_when_run_all_failed(Runner $runner,PhpGuard $phpGuard)
    {
        $runner->setCommand('phpunit')
            ->shouldBeCalled();

        $runner->setArguments(array())
            ->shouldBeCalled();

        $runner->run()
            ->shouldBeCalled()
            ->willReturn(false);

        $phpGuard->log(Argument::containingString('failed'),Argument::cetera())
            ->shouldBeCalled();

        $this->runAll();
    }
}