<?php

namespace spec\PhpSpecTest1;

use PhpSpec\ObjectBehavior;

class TestClassSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpSpecTest1\\TestClass');
    }
}