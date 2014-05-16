<?php

namespace spec\PhpGuard\Plugins\PHPUnit;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PHPUnitPluginSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PHPUnit\PHPUnitPlugin');
    }
}