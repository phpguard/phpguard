<?php

namespace spec\PhpGuard\Application\Console\Command;

use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class RunAllCommandSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Console\Command\RunAllCommand');
    }
}