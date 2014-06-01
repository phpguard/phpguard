<?php

namespace spec\PhpGuard\Application\Util;

use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Event\GenericEvent;
use PhpGuard\Application\Log\ConsoleHandler;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Plugin\PluginInterface;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LocatorSpec extends ObjectBehavior
{
    function let(ContainerInterface $container,Logger $logger)
    {
        $container->get('logger')->willReturn($logger);
        $container->has(Argument::any())
            ->willReturn(true);
        $this->setContainer($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Util\Locator');
    }

    function it_should_locate_class_file()
    {
        $this->findClassFile('PhpGuard\Application\Container')->shouldHaveType('SplFileInfo');
        $this->findClassFile('PhpGuard\Application\Foo')->shouldReturn(false);
    }

    function it_should_find_class_from_file()
    {
        $this->findClass(getcwd().'/src/Container.php')
            ->shouldReturn('PhpGuard\\Application\\Container');
    }

    function it_should_delegate_add()
    {
        $specDir = getcwd().'/spec/PhpGuard/Application';
        $this->findClass($file = $specDir.'/ContainerSpec.php',false)
            ->shouldReturn(false)
        ;

        $this->add("spec",getcwd());
        $class = 'spec\\PhpGuard\\Application\\ContainerSpec';
        $this->findClass($file,false)
            ->shouldReturn($class);
    }

    function it_should_delegate_addPsr4()
    {
        $this->addPsr4(__NAMESPACE__."\\",__DIR__)->shouldReturn($this);
        $this->findClass(__FILE__,false)->shouldReturn(__CLASS__);

        $specDir = getcwd().'/spec/PhpGuard/Application';
        $this->findClass($file = $specDir.'/ContainerSpec.php',false)
            ->shouldReturn(false)
        ;
        $this->addPsr4('spec\\PhpGuard\\Application\\',$specDir);
        $this->findClass($file,false)->shouldReturn('spec\\PhpGuard\\Application\\ContainerSpec');

    }

    function it_should_load_plugin(
        ContainerInterface $container,
        GenericEvent $event,
        EventDispatcherInterface $dispatcher,
        ConsoleHandler $logHandler
    )
    {
        $event->getContainer()->willReturn($container);
        $container->get('dispatcher')
            ->willReturn($dispatcher);

        $container->getParameter('application.initialized',false)
            ->willReturn(false);
        $this->addPsr4(
            'PhpGuard\\Plugins\\Some\\',
            getcwd().'/tests/fixtures/locator/plugin'
        );

        $container->has('plugins.some')
            ->shouldBeCalled()
            ->willReturn(false);
        $container->set('plugins.some',Argument::any())
            ->shouldBeCalled()
        ;
        $container->setShared('linters.php',Argument::any())
            ->shouldBeCalled()
        ;
        ;
        $container->get('logger.handler')
            ->willReturn($logHandler);

        $dispatcher->addSubscriber(Argument::any())
            ->shouldBeCalled();

        $this->onApplicationInitialize($event);
    }

    function it_should_not_load_plugin_when_application_initialized(
        ContainerInterface $container,
        GenericEvent $event
    )
    {
        $event->getContainer()->willReturn($container);
        $container->getParameter('application.initialized',false)
            ->willReturn(true);
        $container->setShared(Argument::any(),Argument::any())
            ->shouldNotBeCalled()
        ;
        $this->onApplicationInitialize($event);
    }
}
