<?php

namespace spec\PhpGuard\Plugins\PhpSpec;

require_once __DIR__.'/MockPhpSpecPlugin.php';

use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Runner;
use PhpGuard\Listen\Util\PathUtil;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;


class PhpSpecPluginSpec extends ObjectBehavior
{
    function let(ContainerInterface $container, Runner $runner,PhpGuard $phpGuard)
    {
        $this->beAnInstanceOf(__NAMESPACE__.'\\MockPhpSpecPlugin');
        $this->setRunner($runner);

        // initialize default options
        $this->setOptions(array());
        $runner->setCommand('phpspec')
            ->willReturn(true);
        $runner->setArguments(Argument::any())
            ->willReturn(true);

        $container->get('phpguard')
            ->willReturn($phpGuard);

        $this->setContainer($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PhpSpec\PhpSpecPlugin');
    }

    function it_should_be_the_PhpSpec_plugin()
    {
        $this->getName()->shouldReturn('phpspec');
        $this->shouldHaveType('PhpGuard\\Application\\Plugin\\Plugin');
    }

    function it_should_set_default_options_properly()
    {
        $this->setOptions(array());

        $options = $this->getOptions();

        $options->shouldHaveKey('run_all');
        $options->shouldHaveKey('format');
        $options->shouldHaveKey('all_after_pass');
    }

    function it_should_run_properly(Runner $runner,PhpGuard $phpGuard)
    {
        $this->setOptions(array(
            'format' => 'dot'
        ));

        $runner->setArguments(Argument::containing('--format=dot'))
            ->shouldBeCalled()
        ;
        $runner->run()
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $spl = PathUtil::createSplFileInfo(getcwd(),__FILE__);
        $this->run(array($spl));
    }

    function it_should_run_all_after_pass(Runner $runner,PhpGuard $phpGuard)
    {
        $this->setOptions(array(
            'all_after_pass' => true,
            'format' => 'dot',
            'run_all' => array(
                'format' => 'progress'
            )
        ));
        $runner->run()
            ->willReturn(true);
        $runner->setCommand('phpspec')
            ->shouldBeCalled()
        ;
        $runner->setArguments(Argument::containing('--format=dot'))
            ->shouldBeCalled()
        ;
        $runner->setArguments(Argument::containing('--format=progress'))
            ->shouldBeCalled()
        ;

        $spl = PathUtil::createSplFileInfo(getcwd(),__FILE__);


        $this->run(array($spl));
    }

    function it_should_log_error_when_failed_to_run(Runner $runner,PhpGuard $phpGuard)
    {
        $runner->run()
            ->willReturn(false);

        $spl = PathUtil::createSplFileInfo(getcwd(),__FILE__);
        $this->run(array($spl));
    }

    function it_should_run_all_properly(Runner $runner,PhpGuard $phpGuard)
    {
        $runner->run()
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $this->setOptions(array(
            'run_all' => array(
                'format' => 'dot'
            )
        ));
        $runner->setArguments(Argument::containing('--format=dot'))
            ->shouldBeCalled();


        $spl = PathUtil::createSplFileInfo(getcwd(),__FILE__);
        $this->runAll(array($spl));
    }

    function it_should_log_error_when_failed_to_run_all(Runner $runner,PhpGuard $phpGuard)
    {
        $runner->run()
            ->willReturn(false);

        $spl = PathUtil::createSplFileInfo(getcwd(),__FILE__);
        $this->runAll(array($spl));
    }
}