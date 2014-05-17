<?php

namespace spec\PhpGuard\Plugins\PhpSpec\Command;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DescribeCommandSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PhpSpec\Command\DescribeCommand');
    }
}