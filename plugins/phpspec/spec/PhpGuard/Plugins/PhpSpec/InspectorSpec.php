<?php

namespace spec\PhpGuard\Plugins\PhpSpec;

use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Event\ResultEvent;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Plugin\PluginInterface;
use PhpGuard\Application\Util\Filesystem;
use PhpGuard\Application\Util\Runner;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpGuard\Plugins\PhpSpec\Inspector;
use Prophecy\Argument;
use Symfony\Component\Process\Process;

class InspectorSpec extends ObjectBehavior
{
    protected $cacheFile;

    function let(
        ContainerInterface $container,
        Runner $runner,
        Process $process,
        Logger $logger,
        PluginInterface $plugin
    )
    {
        $this->cacheFile = Inspector::getCacheFileName();
        @unlink($this->cacheFile);
        $container->get('runner')
            ->willReturn($runner)
        ;
        $container->get('logger')
            ->willReturn($logger)
        ;
        $container->get('plugins.phpspec')
            ->willReturn($plugin)
        ;
        $runner->run(Argument::any())
            ->willReturn($process);
        $runner->setContainer($container);

        $plugin->getOptions()->willReturn(array(
            'cli' => '--some-options',
            'all_after_pass' => false,
            'keep_failed' => true,
            'run_all' => array(
                'cli' => 'run-all'
            ),
        ));
        $this->setContainer($container);
        $this->setLogger($logger);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PhpSpec\Inspector');
    }

    function it_should_extends_the_ContainerAware()
    {
        $this->shouldHaveType('PhpGuard\\Application\\Container\\ContainerAware');
    }

    function it_should_implement_the_PSR_LoggerAwareInterface()
    {
        $this->shouldImplement('Psr\\Log\\LoggerAwareInterface');
    }

    function it_should_run_for_paths(
        Runner $runner,
        Process $process
    )
    {


        $results = array(
            'succeed' => ResultEvent::createSucceed('Succeed'),
            'failed' => ResultEvent::createFailed('Failed'),
            'broken' => ResultEvent::createBroken('Broken'),
            //'error' => ResultEvent::createError('error',new \Exception('some exception')),
        );

        Filesystem::serialize($this->cacheFile,$results);
        $runner->run(Argument::any())
            ->shouldBeCalled()
        ;
        $process->getExitCode()
            ->shouldBeCalled()
            ->willReturn(1)
        ;
        $results = $this->run(array('some_path'));
        $results->getResults()->shouldHaveCount(3);
        $results->getResults()->shouldHaveKey('succeed');
        $results->getResults()->shouldHaveKey('failed');
        $results->getResults()->shouldHaveKey('broken');
    }

    function it_should_runAll_after_pass(
        Runner $runner,
        Process $process,
        ContainerInterface $container,
        PluginInterface $plugin
    )
    {
        $plugin->getOptions()->willReturn(array(
            'cli' => '--some-options',
            'all_after_pass' => true,
            'keep_failed' => true,
            'run_all' => array(
                'cli' => 'run-all'
            ),
        ));
        $this->setContainer($container);

        $results = array(
            'succeed' => ResultEvent::createSucceed('Succeed'),
        );

        Filesystem::serialize($this->cacheFile,$results);
        $runner->run(Argument::any())
            ->shouldBeCalled()
        ;
        $process->getExitCode()
            ->shouldBeCalled()
            ->willReturn(0)
        ;
        $results = $this->run(array('some_path'));
        $results->getResults()->shouldHaveCount(2);
        $results->getResults()->shouldHaveKey('succeed');
        $results->getResults()->shouldHaveKey('all_after_pass');
    }

    function its_runAll_create_success_event_if_results_only_contain_success_events(
        Runner $runner
    )
    {
        $results = array(
            'succeed1' => ResultEvent::createSucceed('Succeed1'),
            'succeed2' => ResultEvent::createSucceed('Succeed2'),
            'succeed3' => ResultEvent::createSucceed('Succeed3'),
        );
        Filesystem::serialize($this->cacheFile,$results);

        $runner->run(Argument::any())
            ->shouldBeCalled()
        ;

        $results = $this->runAll()->getResults();
        $results->shouldHaveCount(1);
        $results->shouldHaveKey('all_after_pass');
    }

    function it_throws_when_results_file_not_exists()
    {
        $this->shouldThrow('RuntimeException')
             ->duringRun(array('some_path'));
    }
}