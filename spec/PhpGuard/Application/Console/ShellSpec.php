<?php

namespace spec\PhpGuard\Application\Console;

use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ShellSpec extends ObjectBehavior
{
    function let(ContainerInterface $container)
    {
        $this->beConstructedWith($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Console\Shell');
    }
}