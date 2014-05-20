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
        $this->setCommand('phpspec')->shouldReturn($this);
        $this->getCommand()->shouldReturn('./vendor/bin/phpspec');

        $this->setCommand('phpguard')->shouldReturn($this);
        $this->getCommand()->shouldReturn('./bin/phpguard');
    }

    function its_setCommand_throws_when_command_not_executable()
    {
        $this->shouldThrow('InvalidArgumentException')
            ->duringSetCommand('some');
    }

    function its_arguments_should_be_mutable()
    {
        $this->setArguments(array('name'=>'value'))->shouldReturn($this);

        $this->getArguments()->shouldHaveKey('name');
        $this->getArguments()->shouldContain('value');
    }

    function its_output_should_be_mutable(OutputInterface $output)
    {
        $this->setOutput($output)->shouldReturn($this);
        $this->getOutput()->shouldReturn($output);
    }
}