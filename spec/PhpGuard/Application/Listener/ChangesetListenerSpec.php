<?php

namespace spec\PhpGuard\Application\Listener;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ChangesetListenerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Listener\ChangesetListener');
    }
}