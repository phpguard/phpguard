<?php

namespace spec\PhpGuard\Application\Log;

use PhpGuard\Application\Spec\ObjectBehavior;

class LoggerSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith('spec');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Log\Logger');
    }
}
