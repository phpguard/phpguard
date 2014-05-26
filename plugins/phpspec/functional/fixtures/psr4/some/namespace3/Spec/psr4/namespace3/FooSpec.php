<?php

namespace Spec\psr4\namespace3;

use PhpSpec\ObjectBehavior;

class FooSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('psr4\\namespace3\\Foo');
    }
}