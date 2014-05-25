<?php

namespace spec\PhpSpecTest2;

use PhpSpec\ObjectBehavior;

class TestClassSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpSpecTest2\\TestClass');
    }
}