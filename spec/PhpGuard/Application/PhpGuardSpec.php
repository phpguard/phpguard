<?php

namespace spec\PhpGuard\Application;

require_once __DIR__.'/MockFileSystem.php';
use PhpGuard\Application\Configuration;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\PhpGuardEvents;
use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpGuard\Listen\Listener;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use spec\PhpGuard\Application\MockFileSystem as mfs;

class MockPhpGuard extends PhpGuard
{
    public function start()
    {
        parent::start();
        $listener = $this->getContainer()->get('listen.listener');
        $listener->alwaysNotify(true);
        $listener->stop();
    }
}

class PhpGuardSpec extends ObjectBehavior
{
    protected $cwd;

    function let(
        ContainerInterface $container,
        ConsoleOutput $output,
        OutputFormatter $formatter,
        EventDispatcherInterface $dispatcher
    )
    {
        $container->get('ui.output')
            ->willReturn($output);
        $container->get('dispatcher')
            ->willReturn($dispatcher)
        ;
        $output->getVerbosity()
            ->willReturn(ConsoleOutput::VERBOSITY_NORMAL);

        $this->setContainer($container);

        $this->beAnInstanceOf(__NAMESPACE__.'\\MockPhpGuard');
        $this->cwd = getcwd();
    }

    function letgo()
    {
        chdir($this->cwd);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\PhpGuard');
    }

    function it_should_set_default_options()
    {
        $this->setOptions(array());
        $options = $this->getOptions();

        $options->shouldHaveKey('ignores');
        $options->shouldHaveKey('latency');
    }

    function it_setup_services(ContainerInterface $container)
    {
        $container->set('phpguard',$this)
            ->shouldBeCalled();
        $container->setShared('config',Argument::cetera())
            ->shouldBeCalled();
        $container->setShared('dispatcher',Argument::cetera())
            ->shouldBeCalled();
        $container->setShared('dispatcher.listeners.config',Argument::cetera())
            ->shouldBeCalled();
        $container->setShared('dispatcher.listeners.changeset',Argument::cetera())
            ->shouldBeCalled();
        $container->setShared('ui.shell',Argument::cetera())
            ->shouldBeCalled();

        $this->setupServices();
    }

    function it_should_listen_properly(
        ContainerInterface $container,
        EventDispatcherInterface $dispatcher,
        ChangeSetEvent $event,
        ConsoleOutput $output
    )
    {
        $event->getFiles()
            ->willReturn(array('some_file'));
        $dispatcher->dispatch(PhpGuardEvents::postEvaluate,Argument::cetera())
            ->shouldBeCalled();
        $this->listen($event);
    }

    function it_should_start_properly(Listener $listener,ContainerInterface $container,ConsoleOutput $output)
    {
        $container->get('listen.listener')
            ->willReturn($listener);

        $this->setOptions(array(
            'ignores' => 'some_dir'
        ));

        $listener->start()
            ->shouldBeCalled();
        $output->writeln(Argument::cetera())
            ->shouldBeCalled();
        $this->start();
    }

    function it_should_log_null_message(ConsoleOutput $output)
    {
        $output->writeln("")
            ->shouldBeCalled();
        $this->log();
    }

    function it_should_not_log_if_verbosity_does_not_match(ConsoleOutput $output)
    {
        $output->setVerbosity(ConsoleOutput::VERBOSITY_NORMAL);
        $output->writeln(Argument::any())
            ->shouldNotBeCalled();
        $this->log('not_visibled',ConsoleOutput::VERBOSITY_VERY_VERBOSE);
    }

    function it_should_format_log_message(ConsoleOutput $output)
    {
        $output->getVerbosity()
            ->willReturn(ConsoleOutput::VERBOSITY_DEBUG);

        $output->writeln(Argument::containingString('normal'))
            ->shouldBeCalled()
        ;

        $this->log('normal');

        $output->writeln(Argument::containingString('debug'))
            ->shouldBeCalled()
        ;
        $this->log('debug',ConsoleOutput::VERBOSITY_DEBUG);
    }

    function it_should_load_configuration(
        ContainerInterface $container,
        Configuration $config,
        EventDispatcherInterface $dispatcher
    )
    {

        $container->get('config')
            ->willReturn($config);

        $dispatcher->dispatch(PhpGuardEvents::preLoadConfig,Argument::any())
            ->shouldBeCalled();

        $dispatcher->dispatch(PhpGuardEvents::postLoadConfig,Argument::any())
            ->shouldBeCalled();

        $config->compileFile(Argument::containingString('yml'))
            ->shouldBeCalled();

        $this->loadConfiguration();

        mfs::mkdir($dir = mfs::$tmpDir);
        touch($dir.'/phpunit.yml.dist');
        chdir($dir);
        $config->compileFile(Argument::containingString('yml.dist'))
            ->shouldBeCalled();
        $this->loadConfiguration();
    }
}