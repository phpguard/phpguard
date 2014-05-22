<?php

namespace spec\PhpGuard\Plugins\PhpSpec;

use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class InspectorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PhpSpec\Inspector');
    }

    function it_should_extends_the_ContainerAware()
    {
        $this->shouldHaveType('PhpGuard\\Application\\Container\\ContainerAware');
    }

    function it_should_implement_the_PSR_LoggerAwareInterface()
    {
        $this->shouldImplement('Psr\\Log\\LoggerAwareInterface');
    }

    function its_result_should_be_mutable()
    {
        $this->setResult(array('success'),array('failed'));
    }
}