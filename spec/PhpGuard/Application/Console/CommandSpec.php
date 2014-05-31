<?php

namespace spec\PhpGuard\Application\Console;

use PhpGuard\Application\Console\Command;
use PhpGuard\Application\Spec\ObjectBehavior;

class MockCommand extends Command
{

}

class CommandSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beAnInstanceOf(__NAMESPACE__.'\\MockCommand');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Console\Command');
    }

    public function it_should_implement_the_ContainerAwareInterface()
    {
        $this->shouldImplement('PhpGuard\Application\Container\ContainerAwareInterface');
    }
}
