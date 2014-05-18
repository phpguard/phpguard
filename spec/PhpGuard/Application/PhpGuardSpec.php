<?php

namespace spec\PhpGuard\Application;

use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Listen\Listener;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MockPhpGuard extends PhpGuard
{
    public function start()
    {
        parent::start();
        $listener = $this->getContainer()->get('phpguard.listen.listener');
        $listener->alwaysNotify(true);
        $listener->stop();
    }
}

class PhpGuardSpec extends ObjectBehavior
{
    function let(ContainerInterface $container,LoggerInterface $logger)
    {
        $this->setContainer($container);
        $this->beAnInstanceOf(__NAMESPACE__.'\\MockPhpGuard');
        $container->get('phpguard.logger')
            ->willReturn($logger);
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

    function it_should_start_listen_properly(Listener $listener,ContainerInterface $container)
    {
        $container->get('phpguard.listen.listener')
            ->willReturn($listener);

        $this->setOptions(array(
            'ignores' => 'some_dir'
        ));

        $listener->start()
            ->shouldBeCalled();
        $this->start();
    }
}