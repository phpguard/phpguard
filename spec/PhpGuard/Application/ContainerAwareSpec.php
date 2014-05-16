<?php

namespace spec\PhpGuard\Application;

use PhpGuard\Application\ContainerAware;
use PhpGuard\Application\Interfaces\ContainerAwareInterface;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ContainerAwareSpec extends ObjectBehavior
{
    function let(ContainerInterface $container)
    {
        require_once __DIR__ . '/ContainerAwareMock.php';
        $this->beAnInstanceOf('\ContainerAwareMock');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\ContainerAware');
    }

    function it_should_implement_the_ContainerInterface()
    {
        $this->shouldImplement('PhpGuard\Application\Interfaces\ContainerAwareInterface');
    }
}