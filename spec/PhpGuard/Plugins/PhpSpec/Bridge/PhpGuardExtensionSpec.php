<?php

namespace spec\PhpGuard\Plugins\PhpSpec\Bridge;

use PhpGuard\Application\Spec\ObjectBehavior;
use PhpSpec\Formatter\Presenter\PresenterInterface;
use PhpSpec\IO\IOInterface;
use PhpSpec\Listener\StatisticsCollector;
use Prophecy\Argument;

class PhpGuardExtensionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PhpSpec\Bridge\PhpGuardExtension');
    }

    function it_should_be_the_PhpSpec_Extension()
    {
        $this->shouldImplement('PhpSpec\\Extension\\ExtensionInterface');
    }
}