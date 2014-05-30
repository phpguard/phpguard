<?php

namespace spec\PhpGuard\Application\Plugin;

use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Plugin\Plugin;
use PhpGuard\Application\Watcher;
use PhpGuard\Application\Event\EvaluateEvent;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MockPlugin extends Plugin
{
    protected $isRunning = false;

    public function getTitle()
    {
        return 'mock';
    }

    public function getName()
    {
        return 'Mock';
    }

    public function runAll()
    {
    }

    public function run(array $paths = array())
    {
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'some' => null,
        ));
    }
}

class PluginSpec extends ObjectBehavior
{
    function let(Watcher $watcher,ContainerInterface $container,Logger $logger)
    {
        $this->beAnInstanceOf(__NAMESPACE__.'\\MockPlugin');
        $this->addWatcher($watcher);
        $this->setContainer($container);

        $container->getParameter('config.file',Argument::any())
            ->willReturn('config_file')
        ;
        $container->getParameter('filter.tags',Argument::any())
            ->willReturn(array())
        ;

        $watcher->hasTags(Argument::any())
            ->willReturn(true);
        $watcher->lint(Argument::any())
            ->willReturn(true);

        $this->setLogger($logger);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Plugin\Plugin');
    }

    function it_should_add_watcher($watcher)
    {
        $this->getWatchers()->shouldContain($watcher);
    }

    function its_getMatchedFiles_returns_an_array_of_matched_file(
        EvaluateEvent $event,
        Watcher $watcher
    )
    {
        $event->getFiles()
            ->willReturn(array(__FILE__))
        ;
        $watcher->matchFile(__FILE__)
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $this->getMatchedFiles($event)->shouldHaveCount(1);
    }

    function its_getMatchedFiles_returns_an_empty_array_if_there_are_not_matched_files(
        EvaluateEvent $event,
        Watcher $watcher
    )
    {
        $event->getFiles()
            ->willReturn(array(__FILE__))
            ->shouldBeCalled()
        ;
        $watcher->matchFile(__FILE__)
            ->shouldBeCalled()
            ->willReturn(false)
        ;
        $this->getMatchedFiles($event)->shouldNotHaveCount(1);
    }

    function its_getMatchedFiles_convert_paths_into_SplFileInfo(
        EvaluateEvent $event,
        Watcher $watcher
    )
    {
        $event->getFiles()
            ->willReturn(array(__FILE__))
            ->shouldBeCalled()
        ;
        $watcher->matchFile(__FILE__)
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $paths = $this->getMatchedFiles($event);
        $paths[0]->shouldHaveType('SplFileInfo');
    }

    function its_getMatchedFiles_should_only_match_againts_tag_if_defined(
        EvaluateEvent $event,
        Watcher $watcher,
        ContainerInterface $container
    )
    {

        $tags = array('foo');
        $container->getParameter('filter.tags',array())
            ->shouldBeCalled()
            ->willReturn($tags)
        ;

        $watcher->hasTags($tags)
            ->shouldBeCalled()
            ->willReturn(true);
        $watcher->matchFile(__FILE__)
            ->shouldBeCalled()
            ->willReturn(true)
        ;

        $event->getFiles()
            ->willReturn(array(__FILE__));


        $this->getMatchedFiles($event);
    }

    function its_getMatchedFiles_returns_false_if_tag_is_not_matched(
        EvaluateEvent $event,
        Watcher $watcher,
        ContainerInterface $container
    )
    {

        $tags = array('foo');
        $container->getParameter('filter.tags',array())
            ->shouldBeCalled()
            ->willReturn($tags)
        ;
        $watcher->hasTags($tags)
            ->shouldBeCalled()
            ->willReturn(false);
        $watcher->matchFile(__FILE__)
            ->shouldNotBeCalled()
        ;
        $watcher->getOptions()
            ->willReturn(array('tags'=>$tags));

        $event->getFiles()
            ->willReturn(array(__FILE__));

        $this->getMatchedFiles($event);
    }

    function its_getMatchedFiles_should_not_process_configuration_file(
        EvaluateEvent $event,
        ContainerInterface $container,
        Watcher $watcher
    )
    {
        $watcher->matchFile('config_file')
            ->shouldNotBeCalled();
        $event->getFiles()
            ->willReturn(array('config_file'))
        ;

        $this->getMatchedFiles($event);
    }

    function its_options_should_be_mutable()
    {
        $this->setOptions(array('some' => 'value'))->shouldReturn($this);
        $this->getOptions()->shouldContain('value');
    }

    /*function it_should_create_runner(ContainerInterface $container,OutputInterface $output)
    {
        $container->get('ui.output')
            ->willReturn($output);

        $runner = $this->setupRunner('phpspec',array('foobar'));
        $runner->shouldHaveType('PhpGuard\\Application\\Runner');
        $runner->getArguments()->shouldContain('foobar');
    }*/

    function its_isActive_returns_false_by_default()
    {
        $this->shouldNotBeActive();
    }

    function its_active_should_be_mutable()
    {
        $this->setActive(true)->shouldReturn($this);
        $this->getActive()->shouldReturn(true);
        $this->shouldBeActive();
    }
}