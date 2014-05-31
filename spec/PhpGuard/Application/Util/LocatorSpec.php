<?php

namespace spec\PhpGuard\Application\Util;

use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Event\GenericEvent;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class LocatorSpec extends ObjectBehavior
{
    public function let(ContainerInterface $container,Logger $logger)
    {
        $container->get('logger')->willReturn($logger);
        $container->has(Argument::any())
            ->willReturn(true);
        $this->setContainer($container);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Util\Locator');
    }

    public function it_should_locate_class_file()
    {
        $this->findClassFile('PhpGuard\Application\Container')->shouldHaveType('SplFileInfo');
    }

    public function it_should_find_class_from_file()
    {
        $this->findClass(getcwd().'/src/Container.php')
            ->shouldReturn('PhpGuard\\Application\\Container');
    }

    public function it_should_delegate_add()
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

    public function it_should_delegate_addPsr4()
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

    public function it_should_load_plugin(
        ContainerInterface $container,
        GenericEvent $event
    )
    {
        $event->getContainer()->willReturn($container);
        $container->getParameter('application.initialized',false)
            ->willReturn(false);
        $this->addPsr4(
            'PhpGuard\\Plugins\\Some\\',
            getcwd().'/tests/fixtures/locator/plugin'
        );

        $container->has('plugins.some')
            ->shouldBeCalled()
            ->willReturn(false);
        $container->setShared('plugins.some',Argument::any())
            ->shouldBeCalled()
        ;
        $container->setShared('linters.php',Argument::any())
            ->shouldBeCalled()
        ;

        $this->onApplicationInitialize($event);
    }
}
