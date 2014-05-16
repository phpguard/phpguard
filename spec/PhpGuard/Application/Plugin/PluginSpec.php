<?php

namespace spec\PhpGuard\Application\Plugin;

use PhpGuard\Application\Plugin\Plugin;
use PhpGuard\Application\Watcher;
use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpGuard\Listen\Util\PathUtil;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MockPlugin extends Plugin
{
    protected $isRunning = false;

    public function getName()
    {
        // TODO: Implement getName() method.
    }

    public function runAll()
    {
        // TODO: Implement runAll() method.
    }

    public function run(array $paths = array())
    {
        // TODO: Implement run() method.
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
    function let(Watcher $watcher)
    {
        $this->beAnInstanceOf(__NAMESPACE__.'\\MockPlugin');
        $this->addWatcher($watcher);
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
        ChangeSetEvent $event,
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
        ChangeSetEvent $event,
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
        ChangeSetEvent $event,
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
}