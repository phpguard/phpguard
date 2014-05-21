<?php

namespace spec\PhpSpecTest3;

use PhpSpec\ObjectBehavior;

class TestClassSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpSpecTest3\\TestClass');
    }
}