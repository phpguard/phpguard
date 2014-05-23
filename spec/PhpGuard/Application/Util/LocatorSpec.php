<?php

namespace spec\PhpGuard\Application\Util;

use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class LocatorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Util\Locator');
    }

    function it_should_locate_class_file()
    {
        $this->findClassFile('PhpGuard\Application\Container')->shouldHaveType('SplFileInfo');
        $spl = $this->findClassFile('PhpGuard\Application\Container');
        $spl->getRelativePathname()->shouldReturn('src/Container.php');

        $this->findClassFile('Foo\\Bar')->shouldReturn(false);
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
}