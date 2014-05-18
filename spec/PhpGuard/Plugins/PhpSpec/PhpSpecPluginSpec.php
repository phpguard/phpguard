<?php

namespace spec\PhpGuard\Plugins\PhpSpec;

use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\Runner;
use PhpGuard\Listen\Util\PathUtil;
use PhpGuard\Plugins\PhpSpec\PhpSpecPlugin;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MockPhpSpecPlugin extends PhpSpecPlugin
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

class PhpSpecPluginSpec extends ObjectBehavior
{
    function let(ContainerInterface $container, Runner $runner,LoggerInterface $logger,OutputInterface $output)
    {
        $this->beAnInstanceOf(__NAMESPACE__.'\\MockPhpSpecPlugin');
        $this->setRunner($runner);
        $this->setLogger($logger);

        // initialize default options
        $this->setOptions(array());
        $runner->setCommand('phpspec')
            ->willReturn(true);
        $runner->setArguments(Argument::any())
            ->willReturn(true);

        $container->get('phpguard.ui.output')
            ->willReturn($output);
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

    function it_should_set_default_options_properly(OptionsResolverInterface $resolver)
    {
        $this->setOptions(array());

        $options = $this->getOptions();

        $options->shouldHaveKey('run_all');
        $options->shouldHaveKey('format');
        $options->shouldHaveKey('all_after_pass');
    }

    function it_should_run_properly(Runner $runner,LoggerInterface $logger)
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

    function it_should_run_all_after_pass(Runner $runner,LoggerInterface $logger)
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

        $logger->log(LogLevel::INFO,Argument::cetera())
            ->shouldBeCalled();
        $this->run(array($spl));
    }

    function it_should_log_error_when_failed_to_run(Runner $runner,LoggerInterface $logger)
    {
        $runner->run()
            ->willReturn(false);
        $logger->log(LogLevel::INFO,Argument::cetera())
            ->shouldBeCalled();
        $logger->log(LogLevel::ERROR,Argument::any(),Argument::any())
            ->shouldBeCalled();

        $spl = PathUtil::createSplFileInfo(getcwd(),__FILE__);
        $this->run(array($spl));
    }

    function it_should_run_all_properly(Runner $runner,LoggerInterface $logger)
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

    function it_should_log_error_when_failed_to_run_all(Runner $runner,LoggerInterface $logger)
    {
        $runner->run()
            ->willReturn(false);
        $logger->log(LogLevel::INFO,Argument::cetera())
            ->shouldBeCalled();
        $logger->log(LogLevel::ERROR,Argument::any(),Argument::any())
            ->shouldBeCalled();

        $spl = PathUtil::createSplFileInfo(getcwd(),__FILE__);
        $this->runAll(array($spl));
    }
}