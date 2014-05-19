<?php

namespace spec\PhpGuard\Application\Console;

use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

class LogHandlerSpec extends ObjectBehavior
{
    function let(ContainerInterface $container,OutputInterface $output)
    {
        $container->get('ui.output')
            ->willReturn($output);
        $this->setContainer($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Console\LogHandler');
    }

    function it_should_handle_log_info_properly(OutputInterface $output)
    {
        $time = new \DateTime();
        $output->writeln(Argument::containingString(sprintf('<info>[%s][INFO] some value</info>',$time->format('H:i:s'))))
            ->shouldBeCalled()
        ;

        $this->setLevel(LogLevel::INFO);

        $this->handle(array(
            'message' => 'some {key}',
            'level' => LogLevel::INFO,
            'level_name' => 'INFO',
            'datetime' => $time,
            'context' => array(
                'key' => 'value',
                'array' => array(),
                'object' => $this,
            ),
            'extra' => array(),
        ));
    }

    function it_should_handle_log_error_properly(OutputInterface $output)
    {
        $time = new \DateTime();
        $this->setLevel(LogLevel::DEBUG);
        $output->writeln(Argument::containingString('[ERROR]'))
            ->shouldBeCalled()
        ;
        $this->handle(array(
            'message' => 'some',
            'level' => LogLevel::ERROR,
            'level_name' => 'ERROR',
            'datetime' => $time,
            'context' => array(),
            'extra' => array(),
        ));
    }
}