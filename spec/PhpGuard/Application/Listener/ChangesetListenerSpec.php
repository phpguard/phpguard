<?php

namespace spec\PhpGuard\Application\Listener;

use PhpGuard\Application\Console\Shell;
use PhpGuard\Application\Event\EvaluateEvent;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\Interfaces\PluginInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\PhpGuardEvents;
use PhpGuard\Listen\Util\PathUtil;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class ChangesetListenerSpec extends ObjectBehavior
{
    function let(
        ContainerInterface $container,
        PluginInterface $plugin,
        EvaluateEvent $evaluateEvent,
        EventDispatcherInterface $dispatcher,
        Shell $shell,
        PhpGuard $phpGuard,
        OutputInterface $output
    )
    {
        $container->getByPrefix('plugins')
            ->willReturn(array($plugin));

        $container->get('dispatcher')
            ->willReturn($dispatcher);

        $container->get('ui.shell')
            ->willReturn($shell);

        $container->get('ui.output')
            ->willReturn($output);

        $container->get('phpguard')
            ->willReturn($phpGuard);

        $this->setContainer($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Listener\ChangesetListener');
    }

    function it_should_subscribe_postEvaluate_event()
    {
        $this->getSubscribedEvents()->shouldHaveKey(PhpGuardEvents::postEvaluate);
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

    function it_should_handle_preRunCommand_events(
        GenericEvent $event,
        PluginInterface $plugin,
        Shell $shell,
        PhpGuard $phpGuard
    )
    {
        $phpGuard->log(Argument::containingString('Begin'),Argument::cetera())
            ->shouldBeCalled();

        $phpGuard->log(Argument::containingString('Match file: '),Argument::cetera())
            ->shouldBeCalled();
        $event->getSubject()->willReturn($plugin);
        $event->getArgument('paths')
            ->willReturn(array(PathUtil::createSplFileInfo(getcwd(),__FILE__)));

        $shell->unsetStreamBlocking()
            ->shouldBeCalled();

        $this->preRunCommand($event);
    }

    function it_should_handle_postRunCommand_events(
        GenericEvent $event,
        PluginInterface $plugin,
        Shell $shell,
        PhpGuard $phpGuard
    )
    {
        $event->getSubject()
            ->willReturn($plugin);

        $phpGuard->log(Argument::containingString('End'),Argument::cetera())
            ->shouldBeCalled();

        $shell->setStreamBlocking()
            ->shouldBeCalled();
        $shell->installReadlineCallback()
            ->shouldBeCalled();

        $this->postRunCommand($event);
    }

    function it_should_handle_runAllCommand_events(
        GenericEvent $event,
        PluginInterface $plugin,
        Shell $shell,
        PhpGuard $phpGuard
    )
    {
        $phpGuard->log(Argument::cetera())
            ->shouldBeCalled();
        $shell->setStreamBlocking()
            ->shouldBeCalled();
        $shell->unsetStreamBlocking()
            ->shouldBeCalled();
        $shell->installReadlineCallback()
            ->shouldBeCalled();

        $phpGuard->log(Argument::containingString('Start'),Argument::cetera())
            ->shouldBeCalled()
        ;
        $phpGuard->log(Argument::containingString('End'),Argument::cetera())
            ->shouldBeCalled()
        ;
        $event->getArgument('plugin')
            ->willReturn(null);

        $plugin->getName()
            ->willReturn('some_plugin');
        $plugin->runAll()
            ->shouldBeCalled();

        $this->runAllCommand($event);
    }

    function it_should_runAllCommand_for_spesific_plugin(
        GenericEvent $event,
        PluginInterface $plugin,
        Shell $shell,
        PhpGuard $phpGuard,
        ContainerInterface $container
    )
    {

        $container->getByPrefix('plugins')
            ->shouldNotBeCalled()
        ;
        $container->has('plugins.some_plugin')
            ->shouldBeCalled()
            ->willReturn(true);
        $container->get('plugins.some_plugin')
            ->willReturn($plugin);

        $container->has('plugins.foo')
            ->shouldBeCalled()
            ->willReturn(false);

        $plugin->getName()
            ->willReturn('some_plugin')
            ->shouldBeCalled()
        ;

        $plugin->runAll()
            ->shouldBeCalled()
        ;

        $event->getArgument('plugin')
            ->willReturn('some_plugin');

        $this->runAllCommand($event);

        $event->getArgument('plugin')
            ->willReturn('foo');

        $this->shouldThrow('RuntimeException')
            ->duringRunAllCommand($event);

    }
}