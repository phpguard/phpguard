<?php

namespace spec\PhpGuard\Plugins\PHPUnit\Bridge\TextUI;

use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;

class CommandSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PHPUnit\Bridge\TextUI\Command');
    }

    function it_should_extends_the_PHPUnit_test_runner_cli()
    {
        $this->shouldHaveType('PHPUnit_TextUI_Command');
    }
}