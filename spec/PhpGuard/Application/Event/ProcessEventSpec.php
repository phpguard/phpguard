<?php

namespace spec\PhpGuard\Application\Event;

use PhpGuard\Application\Plugin\PluginInterface;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class ProcessEventSpec extends ObjectBehavior
{
    function let(PluginInterface $plugin)
    {
        $this->beConstructedWith($plugin);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Event\ProcessEvent');
    }
}