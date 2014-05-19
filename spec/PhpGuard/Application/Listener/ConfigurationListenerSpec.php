<?php

namespace spec\PhpGuard\Application\Listener;

use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\PhpGuardEvents;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\GenericEvent;

class ConfigurationListenerSpec extends ObjectBehavior
{
    function let(GenericEvent $event,PhpGuard $guard)
    {
        $event->getSubject()->willReturn($guard);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Listener\ConfigurationListener');
    }

    function it_should_subscribe_config_preLoad_event()
    {
        $this->getSubscribedEvents()->shouldHaveKey(PhpGuardEvents::preLoadConfig);
    }

    function it_should_subscribe_config_postLoad_event()
    {
        $this->getSubscribedEvents()->shouldHaveKey(PhpGuardEvents::postLoadConfig);
    }

    function it_should_pre_load_configuration_properly(
        GenericEvent $event,
        PhpGuard $guard
    )
    {
        $event->getSubject()->willReturn($guard);

        $guard->loadPlugins()
            ->shouldBeCalled();
        $guard->setOptions(array())
            ->shouldBeCalled();
        $this->preLoad($event);
    }

    function it_should_post_load_configuration_properly(
        GenericEvent $event,
        PhpGuard $guard,
        ContainerInterface $container
    )
    {
        $guard->setupListen()->shouldBeCalled();
        $guard->getContainer()->shouldBeCalled()
            ->willReturn($container)
        ;
        $container->getByPrefix('plugins')
            ->shouldBeCalled()
            ->willReturn(array())
        ;


        $this->postLoad($event);
    }
}