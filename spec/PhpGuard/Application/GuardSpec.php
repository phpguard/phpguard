<?php

namespace spec\PhpGuard\Application;

use PhpGuard\Application\Container;
use PhpGuard\Application\Interfaces\PluginInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class GuardSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Guard');
    }
}