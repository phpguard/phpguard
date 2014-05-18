<?php

namespace spec\PhpGuard\Application;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

class RunnerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Runner');
    }

    function its_command_should_be_mutable()
    {
        $this->setCommand('any')->shouldReturn($this);
        $this->getCommand()->shouldReturn('any');
    }

    function its_arguments_should_be_mutable()
    {
        $this->setArguments(array('name'=>'value'))->shouldReturn($this);

        $this->getArguments()->shouldHaveKey('name');
        $this->getArguments()->shouldContain('value');
        $this->getArguments()->shouldHaveKey('foobar');
    }

    function its_output_should_be_mutable(OutputInterface $output)
    {
        $this->setOutput($output)->shouldReturn($this);
        $this->getOutput()->shouldReturn($output);
    }
}