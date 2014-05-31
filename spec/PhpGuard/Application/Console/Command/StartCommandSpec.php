<?php

namespace spec\PhpGuard\Application\Console\Command;

use PhpGuard\Application\Spec\ObjectBehavior;

class StartCommandSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Console\Command\StartCommand');
    }
}
