<?php

namespace spec\PhpGuard\Application\Log;

use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleHandlerSpec extends ObjectBehavior
{
    function let(ConsoleOutputInterface $output)
    {
        $this->beConstructedWith($output);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Log\ConsoleHandler');
    }

    function its_getFormatter_should_return_ConsoleFormatter_by_default()
    {
        $this->getFormatter()->shouldHaveType('PhpGuard\\Application\\Log\\ConsoleFormatter');
    }

    function its_bubble_parameter_should_not_gets_propagated()
    {
        $this->beConstructedWith(null,false);
        $this->getBubble()->shouldReturn(false);
    }

    function its_isHandling_returns_false_when_no_output_is_set()
    {
        $this->beConstructedWith(null);
        $this->shouldNotBeHandling(array());
    }

    function it_should_not_handle_log_when_verbosity_set_to_quiet(OutputInterface $output)
    {
        $output->getVerbosity()
            ->shouldBeCalled()
            ->willReturn(OutputInterface::VERBOSITY_QUIET)
        ;
        $this->isHandling(array('level'=>Logger::NOTICE))->shouldReturn(false);

        $output->getVerbosity()
            ->shouldBeCalled()
            ->willReturn(OutputInterface::VERBOSITY_DEBUG);
        $this->isHandling(array('level'=>Logger::NOTICE))->shouldReturn(true);
    }

    function it_should_handling_the_log_as_bubble(
        ConsoleOutputInterface $output,
        ConsoleOutputInterface $errorOutput
    )
    {
        $output->writeln(Argument::any())
            ->willReturn(null);
        $output->getVerbosity()
            ->shouldBeCalled()
            ->willReturn(OutputInterface::VERBOSITY_DEBUG)
        ;
        $output
            ->write('<info>[16:21:54] app.INFO:</info> My info message'."\n")
            ->shouldBeCalled()
        ;

        $this->beConstructedWith(null,false);
        $this->setOutput($output);
        $infoRecord = array(
            'message' => 'My info message',
            'context' => array(),
            'level' => Logger::INFO,
            'level_name' => Logger::getLevelName(Logger::INFO),
            'channel' => 'app',
            'datetime' => new \DateTime('2013-05-29 16:21:54'),
            'extra' => array(),
        );
        $this->handle($infoRecord)->shouldReturn(true);


        $output
            ->getErrorOutput()
            ->willReturn($errorOutput)
            ->shouldBeCalled()
        ;
        $errorOutput
            ->write('<error>[16:21:54] app.ERROR:</error> My error message'."\n")
            ->shouldBeCalled()
        ;
        $errorRecord = array(
            'message' => 'My error message',
            'context' => array(),
            'level' => Logger::ERROR,
            'level_name' => Logger::getLevelName(Logger::ERROR),
            'channel' => 'app',
            'datetime' => new \DateTime('2013-05-29 16:21:54'),
            'extra' => array(),
        );
        $this->handle($errorRecord)->shouldReturn(true);
    }

    function its_write_behavior_should_be_detected()
    {
        $infoRecord = array(
            'message' => 'My info message',
            'context' => array(),
            'level' => Logger::INFO,
            'level_name' => Logger::getLevelName(Logger::INFO),
            'channel' => 'app',
            'datetime' => new \DateTime('2013-05-29 16:21:54'),
            'extra' => array(),
        );
        $this->shouldNotBeLogged();
        $this->handle($infoRecord);
        $this->shouldBeLogged();
        $this->reset();
        $this->shouldNotBeLogged();
        $this->handle($infoRecord);
        $this->shouldBeLogged();
    }
}