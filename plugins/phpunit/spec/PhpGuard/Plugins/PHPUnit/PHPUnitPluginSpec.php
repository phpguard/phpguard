<?php

namespace spec\PhpGuard\Plugins\PHPUnit;

use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Runner;
use PhpGuard\Listen\Util\PathUtil;
use PhpGuard\Plugins\PHPUnit\PHPUnitPlugin;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

class MockPhpUnitPlugin extends PHPUnitPlugin
{
    /**
     * @var Runner
     */
    protected $runner;

    public function setRunner(Runner $runner)
    {
        $this->runner = $runner;
    }

    public function createRunner($command,array $arguments=array())
    {
        $this->runner->setCommand($command);
        $this->runner->setArguments($arguments);
        return $this->runner;
    }
}

class PHPUnitPluginSpec extends ObjectBehavior
{
    function let(
        Runner $runner,
        ContainerInterface $container,
        OutputInterface $output,
        PhpGuard $phpGuard,
        $logger
    )
    {
        $this->beAnInstanceOf(__NAMESPACE__.'\\MockPhpUnitPlugin');
        $this->setRunner($runner);
        $container->get('ui.output')
            ->willReturn($output);
        $container->get('phpguard')
            ->willReturn($phpGuard);

        $logger = new Logger('PhpUnit');
        $this->setLogger($logger);
        $this->setContainer($container);

    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PHPUnit\PHPUnitPlugin');
    }

    function it_should_set_default_options()
    {
        $this->setOptions(array());
        $options = $this->getOptions();
        $options->shouldHaveKey('cli');
    }
}