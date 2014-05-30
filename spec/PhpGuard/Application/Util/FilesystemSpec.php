<?php

namespace spec\PhpGuard\Application\Util;

use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class FilesystemSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Util\Filesystem');
    }
}