<?php

namespace spec\PhpGuard\Application\Util;

use PhpGuard\Application\Spec\ObjectBehavior;

class FilesystemSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Util\Filesystem');
    }
}
