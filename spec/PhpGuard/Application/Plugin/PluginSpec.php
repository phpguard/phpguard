<?php

namespace spec\PhpGuard\Application\Plugin;

use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\Plugin\Plugin;
use PhpGuard\Application\Watcher;
use PhpGuard\Application\Event\EvaluateEvent;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MockPlugin extends Plugin
{
    protected $isRunning = false;

    public function getName()
    {
    }

    public function runAll()
    {
    }

    public function run(array $paths = array())
    {
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'some' => null,
        ));
    }
}

class PluginSpec extends ObjectBehavior
{
    function let(Watcher $watcher,ContainerInterface $container)
    {
        $this->beAnInstanceOf(__NAMESPACE__.'\\MockPlugin');
        $this->addWatcher($watcher);
        $this->setContainer($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Plugin\Plugin');
    }

    function it_should_implement_the_PSR_LoggerAwareInterface()
    {
        $this->shouldImplement('Psr\\Log\\LoggerAwareInterface');
    }

    function it_should_log_message(LoggerInterface $logger)
    {
        $logger->log(LogLevel::INFO,'some',array())
            ->shouldBeCalled()
        ;

        $this->setLogger($logger);
        $this->log('some');

        $logger->log(LogLevel::DEBUG,'some',array())
            ->shouldBeCalled()
        ;

        $this->log('some',array(),LogLevel::DEBUG);
    }

    function it_should_add_watcher($watcher)
    {
        $this->getWatchers()->shouldContain($watcher);
    }

    function its_getMatchedFiles_returns_an_array_of_matched_file(
        EvaluateEvent $event,
        Watcher $watcher
    )
    {
        $event->getFiles()
            ->willReturn(array(__FILE__))
        ;
        $watcher->matchFile(__FILE__)
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $this->getMatchedFiles($event)->shouldHaveCount(1);
    }

    function its_getMatchedFiles_returns_an_empty_array_if_there_are_not_matched_files(
        EvaluateEvent $event,
        Watcher $watcher
    )
    {
        $event->getFiles()
            ->willReturn(array(__FILE__))
            ->shouldBeCalled()
        ;
        $watcher->matchFile(__FILE__)
            ->shouldBeCalled()
            ->willReturn(false)
        ;
        $this->getMatchedFiles($event)->shouldNotHaveCount(1);
    }

    function its_getMatchedFiles_convert_paths_into_SplFileInfo(
        EvaluateEvent $event,
        Watcher $watcher
    )
    {
        $event->getFiles()
            ->willReturn(array(__FILE__))
            ->shouldBeCalled()
        ;
        $watcher->matchFile(__FILE__)
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $paths = $this->getMatchedFiles($event);
        $paths[0]->shouldHaveType('SplFileInfo');
    }

    function its_options_should_be_mutable()
    {
        $this->setOptions(array('some' => 'value'))->shouldReturn($this);
        $this->getOptions()->shouldContain('value');
    }

    function it_should_create_runner(ContainerInterface $container,OutputInterface $output)
    {
        $container->get('phpguard.ui.output')
            ->willReturn($output);

        $runner = $this->createRunner('some',array('foobar'));
        $runner->shouldHaveType('PhpGuard\\Application\\Runner');
        $runner->getArguments()->shouldContain('foobar');
    }
}