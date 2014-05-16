<?php

namespace spec\PhpGuard\Application\Event;

use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EvaluateEventSpec extends ObjectBehavior
{
    function let(ChangeSetEvent $event)
    {
        $this->beConstructedWith($event);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Event\EvaluateEvent');
    }
}