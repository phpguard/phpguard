<?php

namespace spec\PhpGuard\Plugins\PhpSpec\Bridge;

use PhpGuard\Application\Spec\ObjectBehavior;
use PhpSpec\Event\ExampleEvent;
use PhpSpec\Formatter\Presenter\PresenterInterface;
use PhpSpec\IO\IOInterface;
use PhpSpec\Listener\StatisticsCollector;
use PhpSpec\Loader\Node\SpecificationNode;
use Prophecy\Argument;

class PhpGuardExtensionSpec extends ObjectBehavior
{
    protected $cwd;
    function let(
        SpecificationNode $specificationNode,
        ExampleEvent $exampleEvent
    )
    {
        $r = new \ReflectionClass(__CLASS__);
        $specificationNode->getClassReflection()->willReturn($r);
        $specificationNode->getTitle()->willReturn('Specification');

        $exampleEvent->getSpecification()
            ->willReturn($specificationNode);
        $exampleEvent->getTitle()
            ->willReturn('it should do something')
        ;
        $this->cwd = getcwd();
        chdir(sys_get_temp_dir());
    }

    function letgo()
    {
        chdir($this->cwd);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PhpSpec\Bridge\PhpGuardExtension');
    }

    function it_should_be_the_PhpSpec_Extension()
    {
        $this->shouldImplement('PhpSpec\\Extension\\ExtensionInterface');
    }

    function it_should_creates_result_event(
        ExampleEvent $exampleEvent,
        SpecificationNode $specificationNode
    )
    {
        $exampleEvent->getResult()
            ->shouldBeCalled()
            ->willReturn(ExampleEvent::PASSED)
        ;
        $specificationNode
            ->getTitle()
            ->shouldBeCalled()
            ->willReturn('SomeSpesification')
        ;

        $this->afterExample($exampleEvent);
        $this->getResults()->shouldHaveCount(1);
        //$this->getResults()->shouldHaveCount(1000);
    }
}