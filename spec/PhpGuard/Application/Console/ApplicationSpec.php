<?php

namespace spec\PhpGuard\Application\Console;

use PhpGuard\Application\Container;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ApplicationSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Console\Application');
    }

    function it_should_setup_container(Container $container,EventDispatcherInterface $dispatcher)
    {
        $container->setShared(Argument::any(),Argument::any())
            ->shouldBeCalled();
        $container->set('phpguard',Argument::any())
            ->shouldBeCalled();
        ;
        $container->get('dispatcher')
            ->shouldBeCalled()
            ->willReturn($dispatcher)
        ;

        $container->set('ui.application',$this)
            ->shouldBeCalled();
        $container->setShared('ui.shell',Argument::any())
            ->shouldBeCalled();

        $container->setShared(Argument::containingString('listeners'),Argument::any())
            ->shouldBeCalled();

        $this->setupContainer($container);
    }
}
