<?php

namespace spec\PhpGuard\Application\Bridge;

use Monolog\Handler\HandlerInterface;
use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Configuration\ConfigEvents;
use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Log\ConsoleHandler;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpGuard\Application\Util\Filesystem;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use PHP_CodeCoverage;
use PHP_CodeCoverage_Filter;

class CodeCoverageRunnerSpec extends ObjectBehavior
{
    protected $options;

    static $cwd;

    function let(
        ContainerInterface $container,
        PHP_CodeCoverage_Filter $filter,
        PHP_CodeCoverage $coverage,
        OutputInterface $output,
        PhpGuard $phpGuard,
        HandlerInterface $handler
    )
    {
        if(is_null(static::$cwd)){
            static::$cwd = getcwd();
        }
        Filesystem::mkdir(static::$tmpDir);
        chdir(static::$tmpDir);
        $container->get('coverage.filter')
            ->willReturn($filter)
        ;
        $container->get('coverage')
            ->willReturn($coverage)
        ;
        $coverage->beADoubleOf('PHP_CodeCoverage',array(
            $filter
        ));
        $this->options = array(
            'enabled'   => true,
            'output.html'      => null,
            'output.clover'    => null,
            'output.text'      => null,
            'whitelist' => array(),
            'blacklist' => array(),
            'whitelist_files' => array(),
            'blacklist_files' => array(),
        );
        $container->get('ui.output')->willReturn($output);
        $container->get('phpguard')->willReturn($phpGuard);
        $container->get('logger.handler')->willReturn($handler);
        $container->setShared(Argument::any(),Argument::any())
            ->willReturn();
        $container->getParameter('coverage.enabled',Argument::any())->willReturn(false);
        $container->setParameter(Argument::cetera())
            ->willReturn();

        $phpGuard->getOptions()->willReturn(array('coverage'=>$this->options));
        $this->setContainer($container);
        $this->setOptions($this->options);
    }

    function letgo()
    {
        Filesystem::cleanDir(static::$tmpDir);
        chdir(static::$cwd);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Bridge\CodeCoverageRunner');
    }

    function it_should_be_serializable()
    {
        $this->shouldImplement('Serializable');
    }

    function it_should_set_code_coverage_from_container(
        ContainerInterface $container
    )
    {
        $container->get('coverage')
            ->shouldBeCalled();
        $container->get('coverage.filter')
            ->shouldBeCalled();

        $this->setContainer($container);
    }

    function it_should_subscribe_events()
    {
        $this->shouldImplement('Symfony\\Component\\EventDispatcher\\EventSubscriberInterface');
        $events = $this->getSubscribedEvents();
        $events->shouldHaveKey(ConfigEvents::POSTLOAD);
        $events->shouldHaveKey(ApplicationEvents::postEvaluate);
        $events->shouldHaveKey(ApplicationEvents::preRunAll);
        $events->shouldHaveKey(ApplicationEvents::postRunAll);
    }

    function it_should_enabled_by_container_parameter(
        ContainerInterface $container
    )
    {
        $options = $this->options;
        $options['enabled'] = false;
        $this->setOptions($options);
        $this->shouldNotBeEnabled();

        $container->getParameter('coverage.enabled',false)
            ->willReturn(true);
        $this->onConfigPostLoad();
        $this->shouldBeEnabled();
    }

    function it_should_throws_if_output_html_dir_not_exists()
    {
        $options = $this->options;
        $options['output.html'] = getcwd().'/foobar/coverage';
        $this->shouldThrow('InvalidArgumentException')
            ->duringSetOptions($options);
    }

    function it_should_create_html_output_child_dir_if_not_exists()
    {
        $options = $this->options;
        $options['output.html'] = getcwd().'/foobar';

        $this->setOptions($options);
        $this->getOptions()->shouldContain(realpath(getcwd().'/foobar'));
    }

    function its_getCached_returns_false_if_coverage_not_started()
    {
        $this->getCached()->shouldReturn(false);
    }

    function it_delegate_start(
        PHP_CodeCoverage $coverage
    )
    {
        $coverage->start('some',false)
            ->shouldBeCalled();

        $this->start('some',false);
    }

    function it_delegate_end(
        PHP_CodeCoverage $coverage
    )
    {
        $coverage->stop(true,array(),array())
            ->shouldBeCalled()
        ;
        $this->stop();
    }

    function it_configure_coverage_filter_when_configuration_loaded(
        PHP_CodeCoverage_Filter $filter
    )
    {
        $options = $this->options;
        $options['whitelist'] = array('some_path');
        $options['blacklist'] = array('some_path');
        $options['whitelist_files'] = array('some_file');
        $options['blacklist_files'] = array('some_file');

        $this->setOptions($options);
        $filter->addDirectoryToWhitelist('some_path',Argument::cetera())
            ->shouldBeCalled();
        $filter->addDirectoryToBlacklist('some_path',Argument::cetera())
            ->shouldBeCalled();
        $filter->addFileToWhitelist('some_file',Argument::cetera())
            ->shouldBeCalled();
        $filter->addFileToBlacklist('some_file',Argument::cetera())
            ->shouldBeCalled();

        $this->onConfigPostLoad();
    }

    function it_should_not_print_report_if_session_has_empty_results(
        ContainerInterface $container
    )
    {
        $container->getParameter('session.results',Argument::any())
            ->willReturn(array());
        $this->process();
    }
}