<?php

namespace spec\PhpGuard\Application\Console\Command;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class StartCommandSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Console\Command\StartCommand');
    }
}