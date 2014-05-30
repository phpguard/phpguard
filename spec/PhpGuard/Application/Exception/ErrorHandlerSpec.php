<?php

namespace spec\PhpGuard\Application\Exception;

use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class ErrorHandlerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Exception\ErrorHandler');

    }
}