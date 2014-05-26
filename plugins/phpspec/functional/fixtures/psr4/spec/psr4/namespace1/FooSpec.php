<?php

namespace spec\psr4\namespace1;

use PhpSpec\ObjectBehavior;

class FooSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('psr4\\namespace1\\Foo');
    }
}