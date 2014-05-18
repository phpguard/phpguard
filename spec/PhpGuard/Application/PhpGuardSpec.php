<?php

namespace spec\PhpGuard\Application;

use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Listen\Listener;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\ConsoleOutput;

class MockPhpGuard extends PhpGuard
{
    public function start()
    {
        parent::start();
        $listener = $this->getContainer()->get('phpguard.listen.listener');
        $listener->alwaysNotify(true);
        $listener->stop();
    }
}

class PhpGuardSpec extends ObjectBehavior
{
    function let(ContainerInterface $container,ConsoleOutput $output, OutputFormatter $formatter)
    {
        $container->get('phpguard.ui.output')
            ->willReturn($output);
        $output->getVerbosity()
            ->willReturn(ConsoleOutput::VERBOSITY_NORMAL);

        $this->setContainer($container);

        $this->beAnInstanceOf(__NAMESPACE__.'\\MockPhpGuard');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\PhpGuard');
    }

    function it_should_set_default_options()
    {
        $this->setOptions(array());
        $options = $this->getOptions();

        $options->shouldHaveKey('ignores');
        $options->shouldHaveKey('latency');
    }

    function it_should_start_listen_properly(Listener $listener,ContainerInterface $container,ConsoleOutput $output)
    {
        $container->get('phpguard.listen.listener')
            ->willReturn($listener);

        $this->setOptions(array(
            'ignores' => 'some_dir'
        ));

        $listener->start()
            ->shouldBeCalled();
        $output->writeln(Argument::cetera())
            ->shouldBeCalled();
        $this->start();
    }
}