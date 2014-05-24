<?php

namespace spec\PhpGuard\Application\Listener;

use PhpGuard\Application\Log\ConsoleHandler;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Plugin\PluginInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpGuard\Listen\Adapter\AdapterInterface;
use PhpGuard\Listen\Listener;
use PhpGuard\Application\Event\GenericEvent;

use Prophecy\Argument;

class ConfigurationListenerSpec extends ObjectBehavior
{
    function let(
        ContainerInterface $container,
        PluginInterface $plugin,
        AdapterInterface $adapter,
        Listener $listener,
        ConsoleHandler $handler,
        Logger $logger,
        GenericEvent $event
    )
    {

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
        $container->get('logger.handler')
            ->willReturn($handler);

        //$logger = new Logger('PhpGuard');
        $container->get('logger')
            ->willReturn($logger);
        $event->getContainer()->willReturn($container);

        $this->setContainer($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Listener\ConfigurationListener');
    }

    function it_should_subscribe_config_preLoad_event()
    {
        $this->getSubscribedEvents()->shouldHaveKey(ApplicationEvents::preLoadConfig);
    }

    function it_should_subscribe_config_postLoad_event()
    {
        $this->getSubscribedEvents()->shouldHaveKey(ApplicationEvents::postLoadConfig);
    }

    function it_should_pre_load_configuration_properly(
        GenericEvent $event,
        ContainerInterface $container,
        PhpGuard $guard
    )
    {
        $container->get('phpguard')->willReturn($guard);

        $guard->setOptions(array())
            ->shouldBeCalled();
        $this->preLoad($event);
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
        $active->getTitle()->shouldBeCalled()
            ->willReturn('Some');
        $active->setLogger(Argument::any())
            ->shouldBeCalled();

        $active->isActive()->willReturn(true);
        $active->configure()->shouldBeCalled();

        $inactive->isActive()->willReturn(false);
        $inactive->configure()->shouldNotBeCalled();

        $this->postLoad($event);
    }
}