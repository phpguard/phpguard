<?php

namespace spec\PhpGuard\Application\Event;

use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class GenericEventSpec extends ObjectBehavior
{
    function let(ContainerInterface $container,PhpGuard $phpGuard)
    {
        $this->beConstructedWith($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Event\GenericEvent');
    }

    function it_should_extends_the_Symfony_Event()
    {
        $this->shouldHaveType('\Symfony\Component\EventDispatcher\Event');
    }

    function its_getContainer_returns_the_container_object($container)
    {
        $this->getContainer()->shouldReturn($container);
    }
}