<?php

namespace spec\PhpGuard\Plugins\PhpSpec\Command;

require_once __DIR__.'/../MockPhpSpecPlugin.php';

use PhpGuard\Application\Console\Application;
use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\Runner;
use PhpGuard\Plugins\PhpSpec\PhpSpecPlugin;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DescribeCommandSpec extends ObjectBehavior
{
    function let(ContainerInterface $container,PhpSpecPlugin $phpspec)
    {
        $container->get('plugins.phpspec')
            ->willReturn($phpspec);
        $this->setContainer($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PhpSpec\Command\DescribeCommand');
    }

    function it_should_execute_properly(PhpSpecPlugin $phpspec,InputInterface $input,OutputInterface $output,Runner $runner)
    {
        $phpspec->createRunner('phpspec',Argument::containing(__CLASS__))
            ->shouldBeCalled()
            ->willReturn($runner)
        ;

        $input->getArgument('class')
            ->willReturn(__CLASS__);
        $input->bind(Argument::cetera())
            ->shouldBeCalled();
        $input->isInteractive()
            ->willReturn(true);
        $input->validate()
            ->shouldBeCalled();

        $runner->run()
            ->shouldBeCalled()
            ->willReturn(true);
        $this->run($input,$output);
    }
}