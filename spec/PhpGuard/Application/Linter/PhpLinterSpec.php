<?php

namespace spec\PhpGuard\Application\Linter;

use PhpGuard\Application\Interfaces\ContainerInterface;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

class PhpLinterSpec extends ObjectBehavior
{
    function let(ContainerInterface $container,OutputInterface $output)
    {
        self::mkdir(self::$tmpDir);
        $this->setContainer($container);
        $container->get('ui.output')
            ->willReturn($output)
        ;
        $container->getParameter('phpguard.use_tty',Argument::any())
            ->willReturn(false);

    }

    function letgo()
    {
        self::cleanDir(self::$tmpDir);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Linter\PhpLinter');
    }

    function its_check_returns_true_if_no_syntax_error()
    {
        $this->check(__FILE__)->shouldReturn(true);
    }

    function its_check_throws_if_file_have_syntax_error()
    {
        file_put_contents($file=self::$tmpDir.'/error.php','<?php adakaa');
        $this
            ->shouldThrow('RuntimeException')
            ->duringCheck($file);
    }
}