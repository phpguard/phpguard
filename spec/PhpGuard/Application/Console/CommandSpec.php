<?php

namespace spec\PhpGuard\Application\Console;

use PhpGuard\Application\Console\Command;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MockCommand extends Command
{

}

class CommandSpec extends ObjectBehavior
{
    function let()
    {
        $this->beAnInstanceOf(__NAMESPACE__.'\\MockCommand');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Console\Command');
    }

    function it_should_implement_the_ContainerAwareInterface()
    {
        $this->shouldImplement('PhpGuard\Application\Interfaces\ContainerAwareInterface');
    }
}