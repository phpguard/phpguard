<?php

namespace spec\PhpGuard\Application\Plugin;

use PhpGuard\Application\Plugin\Plugin;
use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MockPlugin extends Plugin
{
    public function getName()
    {
        // TODO: Implement getName() method.
    }

    public function runAll()
    {
        // TODO: Implement runAll() method.
    }

    public function run(ChangeSetEvent $event)
    {
        // TODO: Implement run() method.
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {

    }
}

class PluginSpec extends ObjectBehavior
{
    function let()
    {
        $this->beAnInstanceOf(__NAMESPACE__.'\\MockPlugin');
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
}