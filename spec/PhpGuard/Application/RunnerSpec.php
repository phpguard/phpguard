<?php

namespace spec\PhpGuard\Application;

use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class RunnerSpec extends ObjectBehavior
{
    function let(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

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

    function it_should_build_command_line()
    {
        $this->setCommand('phpspec');
        $this->setArguments(array('foo=bar'));

        $this->getCommandLine()->shouldReturn('./vendor/bin/phpspec foo=bar');
    }
}