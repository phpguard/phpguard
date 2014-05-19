<?php

namespace spec\PhpGuard\Application\Listener;

use PhpGuard\Application\Event\EvaluateEvent;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\Interfaces\PluginInterface;
use PhpGuard\Application\PhpGuardEvents;
use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ChangesetListenerSpec extends ObjectBehavior
{
    function let(
        ContainerInterface $container,
        PluginInterface $plugin,
        EvaluateEvent $evaluateEvent,
        EventDispatcherInterface $dispatcher
    )
    {
        $container->getByPrefix('phpguard.plugins')
            ->willReturn(array($plugin));

        $container->get('phpguard.dispatcher')
            ->willReturn($dispatcher);

        $this->setContainer($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Listener\ChangesetListener');
    }

    function it_should_subscribe_postEvaluate_event()
    {
        $this->getSubscribedEvents()->shouldHaveKey(PhpGuardEvents::POST_EVALUATE);
    }

    function it_should_run_plugins_when_the_paths_is_matched(
        EvaluateEvent $evaluateEvent,
        PluginInterface $plugin,
        EventDispatcherInterface $dispatcher
    )
    {
        $plugin->getMatchedFiles($evaluateEvent)
            ->shouldBeCalled()
            ->willReturn(array('some_path'))
        ;

        $plugin->run(array('some_path'))
            ->shouldBeCalled();
        $this->postEvaluate($evaluateEvent);
    }

    function it_should_not_run_plugins_when_the_paths_is_no_match($plugin,$evaluateEvent)
    {
        $plugin->getMatchedFiles($evaluateEvent)
            ->shouldBeCalled()
            ->willReturn(array())
        ;
        $plugin->run(Argument::any())
            ->shouldNotBeCalled()
        ;
        $this->postEvaluate($evaluateEvent);
    }
}