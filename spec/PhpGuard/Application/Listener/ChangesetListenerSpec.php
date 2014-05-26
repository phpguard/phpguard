<?php

namespace spec\PhpGuard\Application\Listener;

use PhpGuard\Application\Console\Shell;
use PhpGuard\Application\Console\ShellInterface;
use PhpGuard\Application\Event\CommandEvent;
use PhpGuard\Application\Event\EvaluateEvent;
use \PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Log\ConsoleHandler;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Plugin\PluginInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpGuard\Listen\Util\PathUtil;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class ChangesetListenerSpec extends ObjectBehavior
{
    function let(
        ContainerInterface $container,
        PluginInterface $plugin,
        EventDispatcherInterface $dispatcher,
        Shell $shell,
        PhpGuard $phpGuard,
        OutputInterface $output,
        Logger $logger,
        ConsoleHandler $handler
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

        $container->get('logger')
            ->willReturn($logger);

        $container->get('logger.handler')
            ->willReturn($handler);

        $container->getParameter('config.file')
            ->willReturn('config_file');

        $plugin->isActive()->willReturn(true);
        $plugin->getName()->willReturn('some');

        $this->setContainer($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Listener\ChangesetListener');
    }

    function it_should_subscribe_postEvaluate_event()
    {
        $this->getSubscribedEvents()->shouldHaveKey(ApplicationEvents::postEvaluate);
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

    function it_should_not_run_plugins_when_the_paths_is_no_match($plugin,EvaluateEvent $evaluateEvent)
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

    function it_should_render_result_after_running_plugin(
        PluginInterface $plugin,
        CommandEvent $succeed,
        CommandEvent $failed,
        CommandEvent $broken,
        EvaluateEvent $event,
        ContainerInterface $container,
        Logger $logger
    )
    {
        $container->get('logger')
            ->willReturn($logger);
        $plugin->run(Argument::any())
            ->willReturn(array($succeed,$failed,$broken))
        ;
        $plugin->getMatchedFiles(Argument::any())
            ->willReturn(array('some_path'))
        ;
        $succeed->getMessage()
            ->shouldBeCalled();
        $succeed->getResult()
            ->shouldBeCalled()
            ->willReturn(CommandEvent::SUCCEED)
        ;

        $failed->getResult()
            ->shouldBeCalled()
            ->willReturn(CommandEvent::FAILED)
        ;
        $failed->getMessage()
            ->shouldBeCalled();

        $broken->getResult()
            ->shouldBeCalled()
            ->willReturn(CommandEvent::BROKEN)
        ;
        $broken->getMessage()
            ->shouldBeCalled();

        $this->postEvaluate($event);
    }

    function it_plugin_should_not_run_if_not_active(
        ContainerInterface $container,
        PluginInterface $active,
        PluginInterface $inactive,
        EvaluateEvent $event
    )
    {
        $container->getByPrefix('plugins')
            ->willReturn(array($active,$inactive))
        ;

        $active->isActive()
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $active->getMatchedFiles($event)
            ->willReturn(array('some_file'));
        $active->run(Argument::any())
            ->shouldBeCalled()
        ;

        $inactive->isActive()
            ->shouldBeCalled()
            ->willReturn(false)
        ;
        $inactive->run(Argument::any())
            ->shouldNotBeCalled()
        ;

        $this->postEvaluate($event);
    }

    function it_should_handle_preRunCommand_events(
        GenericEvent $event,
        PluginInterface $plugin,
        Logger $logger
    )
    {
        $logger->addDebug(Argument::containingString('Begin'))
            ->shouldBeCalled();

        $logger->addDebug(Argument::containingString('Match file: '))
            ->shouldBeCalled();
        $event->getSubject()->willReturn($plugin);
        $event->getArgument('paths')
            ->willReturn(array(PathUtil::createSplFileInfo(getcwd(),__FILE__)));

        $this->preRunCommand($event);
    }

    function it_should_handle_postRunCommand_events(
        GenericEvent $event,
        PluginInterface $plugin,
        Logger $logger
    )
    {
        $event->getSubject()
            ->willReturn($plugin);

        $logger->addDebug(Argument::containingString('End'))
            ->shouldBeCalled();

        $this->postRunCommand($event);
    }

    function it_should_handle_runAllCommand_events(
        GenericEvent $event,
        PluginInterface $plugin,
        Logger $logger
    )
    {
        $logger->addDebug(Argument::cetera())
            ->shouldBeCalled();
        $logger->addDebug(Argument::containingString('Start'))
            ->shouldBeCalled()
        ;
        $logger->addDebug(Argument::containingString('End'),Argument::cetera())
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

    function it_plugin_should_not_runAll_if_not_active(
        ContainerInterface $container,
        PluginInterface $active,
        PluginInterface $inactive,
        GenericEvent $event
    )
    {

        $container->getByPrefix('plugins')
            ->willReturn(array($active,$inactive))
        ;

        $container->has('active')
            ->willReturn(true)
        ;
        $active->isActive()
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $active->getName()
            ->willReturn('active');
        $active->runAll(Argument::any())
            ->shouldBeCalled()
        ;

        $container->has('inactive')
            ->willReturn(true)
        ;
        $inactive->getName()->willReturn('inactive');
        $inactive->getTitle()->willReturn('Inactive');
        $inactive->isActive()
            ->shouldBeCalled()
            ->willReturn(false)
        ;
        $inactive->runAll(Argument::any())
            ->shouldNotBeCalled()
        ;

        $this->runAllCommand($event);
    }
}