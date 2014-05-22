<?php

namespace spec\PhpGuard\Application\Log;

use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class ConsoleHandlerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Log\ConsoleHandler');
    }
}