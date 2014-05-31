<?php

namespace spec\PhpGuard\Application\Event;

use PhpGuard\Application\Plugin\PluginInterface;
use PhpGuard\Application\Spec\ObjectBehavior;

class ProcessEventSpec extends ObjectBehavior
{
    public function let(PluginInterface $plugin)
    {
        $this->beConstructedWith($plugin);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Event\ProcessEvent');
    }

    public function it_returns_related_plugin_for_event(
        PluginInterface $plugin
    )
    {
        $this->getPlugin()->shouldReturn($plugin);
    }

    public function it_returns_results(
        PluginInterface $plugin
    )
    {
        $this->beConstructedWith($plugin,array('foo'));
        $this->getResults()->shouldContain('foo');
    }
}
