<?php

namespace spec\PhpGuard\Application\Listener;

use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Configuration;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Event\GenericEvent;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpGuard\Listen\Listener;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Adapter\AdapterInterface;

class ApplicationListenerSpec extends ObjectBehavior
{
    function let(GenericEvent $event,ContainerInterface $container)
    {
        $event->getContainer()->willReturn($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Listener\ApplicationListener');
    }

    function it_should_listen_to_application_events()
    {
        $this->getSubscribedEvents()->shouldHaveKey(ApplicationEvents::initialize);
    }

    function it_should_not_initialize_application_if_application_have_initialized(
        GenericEvent $event,
        ContainerInterface $container,
        EventDispatcherInterface $dispatcher
    )
    {
        $container->getParameter('app.initialized',false)
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $container->get('config')
            ->shouldNotBeCalled()
        ;
        $this->initialize($event,'initialize',$dispatcher);
    }

    function it_should_initialize_application(
        GenericEvent $event,
        ContainerInterface $container,
        Configuration $config,
        EventDispatcherInterface $dispatcher
    )
    {
        $configFile = getcwd().'/phpguard.yml.dist';

        $container->getParameter('app.initialized',false)
            ->shouldBeCalled()
            ->willReturn(false);

        $container->get('config')
            ->shouldBeCalled()
            ->willReturn($config)
        ;

        $container->getParameter('config.file')
            ->shouldBeCalled()
            ->willReturn($configFile)
        ;

        $container->setParameter('app.initialized',true)
            ->shouldBeCalled();

        $dispatcher->dispatch(ApplicationEvents::preLoadConfig,$event)
            ->shouldBeCalled();
        $config->compileFile($configFile)
            ->shouldBeCalled()
        ;
        $dispatcher->dispatch(ApplicationEvents::postLoadConfig,$event)
            ->shouldBeCalled();

        $this->initialize($event,'initialize',$dispatcher);
    }

    function it_should_configure_listen_when_application_started(
        GenericEvent $event,
        ContainerInterface $container,
        AdapterInterface $adapter,
        Listener $listener,
        Logger $logger
    )
    {
        $container->get('logger')
            ->willReturn($logger);

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

        $this->started($event);
    }
}