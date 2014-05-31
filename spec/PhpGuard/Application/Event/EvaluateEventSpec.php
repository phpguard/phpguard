<?php

namespace spec\PhpGuard\Application\Event;

use PhpGuard\Listen\Event\ChangeSetEvent;
use PhpGuard\Application\Spec\ObjectBehavior;

class EvaluateEventSpec extends ObjectBehavior
{
    public function let(ChangeSetEvent $event)
    {
        $this->beConstructedWith($event);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Event\EvaluateEvent');
    }

    public function its_delegate_changeset_event(ChangeSetEvent $event)
    {
        $this->getChangeset()->shouldReturn($event);

        $event->getEvents()
            ->shouldBeCalled();
        $this->getEvents();

        $event->getFiles()
            ->shouldBeCalled();
        $this->getFiles();

        $event->getListener()
            ->shouldBeCalled();
        $this->getListener();
    }
}
