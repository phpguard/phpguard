<?php

namespace spec\PhpGuard\Application\Console\Command;

use PhpGuard\Application\Spec\ObjectBehavior;

class RunAllCommandSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Console\Command\RunAllCommand');
    }
}
