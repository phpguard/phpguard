<?php

namespace spec\PhpGuard\Application\Listener;

use PhpGuard\Application\Plugin\PluginInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\PhpGuardEvents;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpGuard\Listen\Adapter\AdapterInterface;
use PhpGuard\Listen\Listener;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\GenericEvent;

class ConfigurationListenerSpec extends ObjectBehavior
{
    function let(
        GenericEvent $event,
        PhpGuard $phpGuard,
        ContainerInterface $container,
        PluginInterface $plugin,
        AdapterInterface $adapter,
        Listener $listener
    )
    {
        $event->getSubject()->willReturn($phpGuard);
        $container->get('phpguard')
            ->willReturn($phpGuard);
        $phpGuard->log(Argument::cetera())
            ->willReturn(null);

        $container->getByPrefix('plugins')
            ->willReturn(array($plugin))
        ;

        $container->getParameter(Argument::any(),Argument::any())
            ->willReturn(null);
        $container->setParameter(Argument::any(),Argument::any())
            ->willReturn(null);

        $container->get('listen.listener')
            ->willReturn($listener);
        $container->get('listen.adapter')
            ->willReturn($adapter);

        $this->setContainer($container);
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

    function its_postLoad_should_configure_listen(
        ContainerInterface $container,
        AdapterInterface $adapter,
        Listener $listener
    )
    {
        // listen specs
        $container->get('listen.listener')
            ->shouldBeCalled()
            ->willReturn($listener)
        ;
        $container->get('listen.adapter')
            ->shouldBeCalled()
            ->willReturn($adapter)
        ;

        $listener->setAdapter($adapter)
            ->shouldBeCalled();

        $this->postLoad();
    }

    function it_postLoad_should_configure_only_active_plugin(
        GenericEvent $event,
        ContainerInterface $container,
        PluginInterface $active,
        PluginInterface $inactive
    )
    {
        $container->getByPrefix('plugins')
            ->shouldBeCalled()
            ->willReturn(array($active,$inactive))
        ;
        $active->getName()->shouldBeCalled()
            ->willReturn('some');
        $active->isActive()->willReturn(true);
        $active->configure()->shouldBeCalled();

        $inactive->isActive()->willReturn(false);
        $inactive->configure()->shouldNotBeCalled();

        $this->postLoad($event);
    }
}