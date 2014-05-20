<?php

namespace spec\PhpGuard\Application\Listener;

use PhpGuard\Application\Interfaces\PluginInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\PhpGuardEvents;
use PhpGuard\Application\Spec\ObjectBehavior;
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

    function it_postLoad_should_configure_only_active_plugin(
        GenericEvent $event,
        PhpGuard $guard,
        ContainerInterface $container,
        PluginInterface $active,
        PluginInterface $inactive
    )
    {

        $guard->setupListen()->shouldBeCalled();
        $guard->getContainer()->shouldBeCalled()
            ->willReturn($container)
        ;

        $container->getByPrefix('plugins')
            ->shouldBeCalled()
            ->willReturn(array($active,$inactive))
        ;

        $active->isActive()->willReturn(true);
        $active->configure()->shouldBeCalled();

        $inactive->isActive()->willReturn(false);
        $inactive->configure()->shouldNotBeCalled();

        $this->postLoad($event);
    }
}