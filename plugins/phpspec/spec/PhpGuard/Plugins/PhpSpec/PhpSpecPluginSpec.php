<?php

namespace spec\PhpGuard\Plugins\PhpSpec;

use PhpGuard\Application\Console\Application;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Util\Locator;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpGuard\Plugins\PhpSpec\Inspector;
use Prophecy\Argument;

class PhpSpecPluginSpec extends ObjectBehavior
{
    function let(
        ContainerInterface $container,
        Logger $logger,
        Locator $locator,
        Inspector $inspector,
        Application $application
    )
    {
        $container->get('locator')
            ->willReturn($locator)
        ;
        $container->get('phpspec.inspector')
            ->willReturn($inspector)
        ;
        $container->get('ui.application')
            ->willReturn($application)
        ;

        $container->getParameter('filter.tags',Argument::any())
            ->willReturn(array())
        ;
        $container->getParameter('phpspec.suites',Argument::any())
            ->willReturn(array())
        ;

        // initialize default options
        $this->setOptions(array());
        $container->get('logger')
            ->willReturn($logger);

        $this->setContainer($container);
        $this->setLogger($logger);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PhpSpec\PhpSpecPlugin');
    }

    function it_should_be_the_PhpSpec_plugin()
    {
        $this->getName()->shouldReturn('phpspec');
        $this->shouldHaveType('PhpGuard\\Application\\Plugin\\Plugin');
    }

    function it_should_set_default_options_properly()
    {
        $this->setOptions(array());

        $options = $this->getOptions();

        $options->shouldHaveKey('run_all');
        $options->shouldHaveKey('cli');
        $options->shouldHaveKey('all_after_pass');
        $options->shouldHaveKey('keep_failed');
        $options->shouldHaveKey('all_on_start');
    }

    function it_should_configure_inspector_when_application_initialized(
        ContainerInterface $container
    )
    {
        $container->setShared('phpspec.inspector',Argument::any())
            ->shouldBeCalled()
        ;
        $this->configure();
    }
}