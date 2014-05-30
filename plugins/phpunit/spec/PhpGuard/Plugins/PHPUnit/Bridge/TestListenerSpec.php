<?php

namespace spec\PhpGuard\Plugins\PHPUnit\Bridge;

use PhpGuard\Application\Container;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class TestListenerSpec extends ObjectBehavior
{
    function let(Container $container)
    {

    }
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PHPUnit\Bridge\TestListener');
    }
}