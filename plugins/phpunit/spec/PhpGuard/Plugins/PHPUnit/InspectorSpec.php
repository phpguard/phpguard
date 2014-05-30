<?php

namespace spec\PhpGuard\Plugins\PHPUnit;

use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Event\ResultEvent;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Plugin\PluginInterface;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpGuard\Application\Util\Filesystem;
use PhpGuard\Application\Util\Runner;
use PhpGuard\Plugins\PHPUnit\Inspector;
use PhpGuard\Plugins\PHPUnit\PHPUnitPlugin;
use Prophecy\Argument;
use Symfony\Component\Finder\Shell\Command;
use Symfony\Component\Process\Process;

class InspectorSpec extends ObjectBehavior
{
    protected $cacheFile;

    function let(
        ContainerInterface $container,
        Runner $runner,
        Process $process,
        PHPUnitPlugin $plugin,
        Logger $logger
    )
    {
        $this->cacheFile = Inspector::getResultFileName();
        @unlink($this->cacheFile);
        $runner->setContainer($container);
        $runner->run(Argument::any())
            ->willReturn($process);
        $runner->findExecutable('phpunit')
            ->willReturn('phpunit');
        $container->get('runner')->willReturn($runner);
        $container->get('plugins.phpunit')->willReturn($plugin);
        $container->get('logger')->willReturn($logger);

        $plugin->getOptions()
            ->willReturn(array(
                'cli'=>'--some-options',
                'all_after_pass' => false,
            ))
        ;
        $plugin->getTitle()
            ->willReturn('phpunit')
        ;
        $this->setContainer($container);

    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PHPUnit\Inspector');
    }

    function it_should_run_with_paths(
        Runner $runner,
        Process $process
    )
    {
        $event = $this->createCommandEvent(ResultEvent::SUCCEED,' Success Message');
        Filesystem::serialize($this->cacheFile,array(
            'key' => $event,
        ));
        $runner->run(Argument::any())
            ->willReturn($process)
            ->shouldBeCalled();
        $results = $this->run(array('some_path'));
        $results->getResults()->shouldHaveCount(1);
    }

    function it_should_run_all_after_pass(
        Runner $runner,
        Process $process,
        PluginInterface $plugin,
        ContainerInterface $container
    )
    {
        $plugin->getOptions()
            ->willReturn(array(
                'cli'   =>  '--some-options',
                'all_after_pass' => true,
            ))
        ;
        $this->beConstructedWith();
        $this->setContainer($container);

        $event = $this->createCommandEvent(ResultEvent::SUCCEED,' Success Message');
        Filesystem::serialize($this->cacheFile,array(
            'key' => $event,
        ));
        $process->getExitCode()->willReturn(0);
        $runner->run(Argument::any())
            ->willReturn($process)
            ->shouldBeCalled()
        ;

        $results = $this->run(array('some_path'))->getResults();
        $results->shouldHaveCount(2);
        $results->shouldHaveKey('all_after_pass');
    }

    function its_runAll_should_returns_only_failed_or_broken_tests(
        Runner $runner,
        Process $process
    )
    {
        Filesystem::serialize($this->cacheFile,array(
            'succeed' => $this->createCommandEvent(ResultEvent::SUCCEED,' Success Message'),
            'failed' => $this->createCommandEvent(ResultEvent::FAILED,' Failed Message'),
            'broken' => $this->createCommandEvent(ResultEvent::BROKEN,' Failed Message'),
        ));
        $runner->run(Argument::any())
            ->willReturn($process)
            ->shouldBeCalled();
        $results = $this->runAll();
        $results->getResults()->shouldHaveKey('failed');
        $results->getResults()->shouldHaveKey('broken');
        $results->getResults()->shouldNotHaveKey('succeed');
    }

    /**
     * @param PluginInterface   $plugin
     * @param int               $result
     * @param string            $message
     *
     * @return ResultEvent
     */
    public function createCommandEvent($result,$message)
    {
        $event = new ResultEvent($result,$message);
        return $event;
    }
}