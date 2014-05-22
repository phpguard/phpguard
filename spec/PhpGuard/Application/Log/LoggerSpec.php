<?php

namespace spec\PhpGuard\Application\Log;

use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class LoggerSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('spec');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Log\Logger');
    }
}