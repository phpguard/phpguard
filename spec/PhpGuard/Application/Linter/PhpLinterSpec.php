<?php

namespace spec\PhpGuard\Application\Linter;

use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Log\ConsoleHandler;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpGuard\Application\Util\Filesystem;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

class PhpLinterSpec extends ObjectBehavior
{
    public function let(ContainerInterface $container,OutputInterface $output,ConsoleHandler $handler)
    {
        Filesystem::mkdir(self::$tmpDir);
        $container->get('logger.handler')
            ->willReturn($handler);
        $this->setContainer($container);
        $container->get('ui.output')
            ->willReturn($output)
        ;
        $container->getParameter('phpguard.use_tty',Argument::any())
            ->willReturn(false);

    }

    public function letgo()
    {
        Filesystem::cleanDir(self::$tmpDir);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Linter\PhpLinter');
    }

    public function its_check_returns_true_if_no_syntax_error()
    {
        $this->check(__FILE__)->shouldReturn(true);
    }

    public function its_check_throws_if_file_have_syntax_error()
    {
        file_put_contents($file=self::$tmpDir.'/error.php','<?php adakaa');
        $this->shouldThrow('PhpGuard\\Application\\Linter\\LinterException')
            ->duringCheck($file);
    }
}
