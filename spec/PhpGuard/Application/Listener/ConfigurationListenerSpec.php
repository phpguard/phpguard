<?php

namespace spec\PhpGuard\Application\Listener;

use PhpGuard\Application\Guard;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\PhpGuardEvents;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\GenericEvent;

class ConfigurationListenerSpec extends ObjectBehavior
{
    function let(GenericEvent $event,Guard $guard)
    {
        $event->getSubject()->willReturn($guard);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Listener\ConfigurationListener');
    }

    function it_should_subscribe_config_preLoad_event()
    {
        $this->getSubscribedEvents()->shouldHaveKey(PhpGuardEvents::CONFIG_PRE_LOAD);
    }

    function it_should_subscribe_config_postLoad_event()
    {
        $this->getSubscribedEvents()->shouldHaveKey(PhpGuardEvents::CONFIG_POST_LOAD);
    }

    function it_should_pre_load_configuration_properly(
        GenericEvent $event,
        Guard $guard
    )
    {
        $event->getSubject()->willReturn($guard);

        $guard->loadPlugins()
            ->shouldBeCalled();
        $this->preLoad($event);
    }

    function it_should_post_load_configuration_properly(
        GenericEvent $event,
        Guard $guard
    )
    {
        $guard->setupListen()->shouldBeCalled();
        $this->postLoad($event);
    }
}