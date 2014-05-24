<?php

namespace spec\PhpGuard\Application\Event;

use PhpGuard\Application\Event\CommandEvent;
use PhpGuard\Application\Plugin\PluginInterface;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class CommandEventSpec extends ObjectBehavior
{
    function let(PluginInterface $plugin)
    {
        $this->beConstructedWith($plugin,CommandEvent::SUCCEED);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Event\CommandEvent');
    }

    function it_isSucceed_returns_true_if_command_succeed($plugin)
    {
        $this->beConstructedWith($plugin,CommandEvent::SUCCEED);
        $this->shouldBeSucceed();
    }

    function it_isFailed_returns_true_if_command_did_not_succeed($plugin)
    {
        $this->beConstructedWith($plugin,CommandEvent::FAILED);
        $this->shouldBeFailed();
    }

    function it_isBroken_returns_true_if_command_did_not_succeed_and_has_error($plugin)
    {
        $this->beConstructedWith($plugin,CommandEvent::BROKEN,new \Exception('some'));
        $this->shouldBeBroken();
    }
}