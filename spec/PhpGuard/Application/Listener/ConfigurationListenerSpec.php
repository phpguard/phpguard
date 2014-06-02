<?php

namespace spec\PhpGuard\Application\Listener;

use PhpGuard\Application\Configuration\ConfigEvents;
use PhpGuard\Application\Configuration\Processor;
use PhpGuard\Application\Log\ConsoleHandler;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Plugin\PluginInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpGuard\Application\Util\Filesystem;
use PhpGuard\Listen\Adapter\AdapterInterface;
use PhpGuard\Listen\Listener;
use PhpGuard\Application\Event\GenericEvent;

use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConfigurationListenerSpec extends ObjectBehavior
{
    static $cwd;
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
        if(is_null(static::$cwd)){
            static::$cwd = getcwd();
        }
        Filesystem::create()->mkdir(static::$tmpDir);
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
        $container->get('logger')
            ->willReturn($logger);
        $event->getContainer()->willReturn($container);

        $this->setContainer($container);
    }

    function letgo()
    {
        chdir(static::$cwd);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Listener\ConfigurationListener');
    }

    function it_should_subscribe_config_preLoad_event()
    {
        $this->getSubscribedEvents()->shouldHaveKey(ConfigEvents::PRELOAD);
    }

    function it_should_subscribe_config_postLoad_event()
    {
        $this->getSubscribedEvents()->shouldHaveKey(ConfigEvents::POSTLOAD);
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

    function its_preLoad_throws_when_configuration_file_not_exists(
        GenericEvent $event,
        ContainerInterface $container,
        PhpGuard $guard
    )
    {
        chdir(static::$tmpDir);
        $container->get('phpguard')->willReturn($guard);

        $guard->setOptions(array())
            ->shouldBeCalled();
        $this->shouldThrow('InvalidArgumentException')
            ->duringPreLoad($event);
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

        $active->isActive()->willReturn(true);
        $active->configure()->shouldBeCalled();

        $inactive->isActive()->willReturn(false);
        $inactive->configure()->shouldNotBeCalled();

        $this->postLoad($event);
    }

    function it_should_load_configuration(
        GenericEvent $event,
        ContainerInterface $container,
        EventDispatcherInterface $dispatcher,
        Processor $processor
    )
    {
        $configFile = getcwd().'/phpguard.yml.dist';

        $container->get('config')
            ->shouldBeCalled()
            ->willReturn($processor)
        ;

        $container->getParameter('config.file')
            ->shouldBeCalled()
            ->willReturn($configFile)
        ;

        $dispatcher->dispatch(ConfigEvents::PRELOAD,$event)
            ->shouldBeCalled();

        $processor->compileFile($configFile)
            ->shouldBeCalled()
        ;

        $dispatcher->dispatch(ConfigEvents::POSTLOAD,$event)
            ->shouldBeCalled();
        $this->load($event,ConfigEvents::LOAD,$dispatcher);
    }

    function it_should_reload_configuration(
        GenericEvent $event,
        ContainerInterface $container,
        EventDispatcherInterface $dispatcher,
        Processor $processor
    )
    {
        $configFile = getcwd().'/phpguard.yml.dist';

        $container->get('config')
            ->shouldBeCalled()
            ->willReturn($processor)
        ;

        $container->getParameter('config.file')
            ->shouldBeCalled()
            ->willReturn($configFile)
        ;

        $dispatcher->dispatch(ConfigEvents::PRELOAD,$event)
            ->shouldBeCalled();

        $processor->compileFile($configFile)
            ->shouldBeCalled()
        ;

        $dispatcher->dispatch(ConfigEvents::POSTLOAD,$event)
            ->shouldBeCalled();
        $this->reload($event,ConfigEvents::LOAD,$dispatcher);
    }
}
