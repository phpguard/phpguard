<?php

namespace spec\PhpGuard\Application;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class WatcherSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Watcher');
    }
}