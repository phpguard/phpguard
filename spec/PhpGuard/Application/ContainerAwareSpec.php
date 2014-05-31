<?php

namespace spec\PhpGuard\Application;

use PhpGuard\Application\Container\ContainerAware;
use PhpGuard\Application\Container\ContainerAwareInterface;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Spec\ObjectBehavior;

class ContainerAwareSpec extends ObjectBehavior
{
    function let(ContainerInterface $container)
    {
        require_once __DIR__ . '/ContainerAwareMock.php';
        $this->beAnInstanceOf('\ContainerAwareMock');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Container\ContainerAware');
    }

    function it_should_implement_the_ContainerInterface()
    {
        $this->shouldImplement('PhpGuard\Application\Container\ContainerAwareInterface');
    }
}
