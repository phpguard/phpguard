<?php

namespace spec\PhpGuard\Application;

use \PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Linter\LinterInterface;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Util\Filesystem;
use PhpGuard\Listen\Resource\FileResource;
use PhpGuard\Application\Spec\ObjectBehavior;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WatcherSpec extends ObjectBehavior
{
    function let(ContainerInterface $container,OutputInterface $output,Logger $logger)
    {
        Filesystem::create()->mkdir(self::$tmpDir);
        $this->beConstructedWith($container);
        $container->get('logger')->willReturn($logger);
        $container->get('ui.output')->willReturn($output);
    }

    function letgo()
    {
        Filesystem::create()->cleanDir(self::$tmpDir);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Application\Watcher');
    }

    function it_throws_when_pattern_is_not_set()
    {
        $this->shouldThrow('InvalidArgumentException')
            ->duringSetOptions(array());
    }

    function it_should_generate_default_options(OptionsResolverInterface $resolver)
    {
        $this->setOptions(array('pattern'=>'some_pattern'));

        $options = $this->getOptions();
        $options->shouldHaveKey('pattern');
        $options->shouldHaveKey('tags');
        $options->shouldHaveKey('transform');
        $options->shouldHaveKey('groups');
        $options->shouldHaveKey('lint');
    }

    function it_should_throw_when_linter_not_exists(
        ContainerInterface $container
    )
    {
        $container->has('linters.foo')
            ->shouldBeCalled()
            ->willReturn(false);

        $options =array(
            'pattern' => 'some_pattern',
            'lint' => 'foo',
        );

        $this->shouldThrow('InvalidArgumentException')
            ->duringSetOptions($options);
    }

    function its_matchFile_returns_SplFileInfo_is_matched()
    {
        $this->setOptions(array(
            'pattern' => '#.*\.php$#',
        ));
        $this->matchFile(__FILE__)->shouldHaveType('SplFileInfo');

        $resource = new FileResource(__FILE__);
        $this->matchFile($resource)->shouldHaveType('SplFileInfo');
    }

    function its_matchFile_should_check_with_relative_path_name()
    {
        $this->setOptions(array(
            'pattern' => '#^spec\/.*\.php$#',
        ));
        $this->matchFile(__FILE__)->shouldHaveType('SplFileInfo');

        touch($file = self::$tmpDir.'/foobar.php');

        $this->matchFile($file)->shouldReturn(false);
    }

    function its_matchFile_returns_false_if_file_not_exists()
    {
        $this->matchFile('/tmp/foobar.php')->shouldReturn(false);
    }

    function its_matchFile_should_transform_file_if_defined()
    {
        $this->setOptions(array(
            'pattern' => '#^src\/(.+)\.php$#',
            'transform' => 'spec/PhpGuard/Application/${1}Spec.php'
        ));
        $spl = $this->matchFile(getcwd().'/src/Watcher.php');
        $spl->getRelativePathName()->shouldReturn('spec/PhpGuard/Application/WatcherSpec.php');
    }

    function its_matchFile_returns_false_if_transformed_file_not_exists()
    {
        $this->setOptions(array(
            'pattern' => '#^src\/(.+)\.php$#',
            'transform' => 'spec/${1}SpecFooBar.php'
        ));

        $this->matchFile(getcwd().'/src/Watcher.php')
            ->shouldReturn(false)
        ;
    }

    function its_hasGroup_should_check_if_group_exists()
    {
        $this->setOptions(array(
            'pattern' => 'some',
            'groups' => 'foo'
        ));
        $this->shouldHaveGroup('foo');
        $this->shouldNotHaveGroup('bar');
    }

    function it_should_check_file_with_linter_if_defined(
        ContainerInterface $container,
        LinterInterface $linter
    )
    {
        $container->has('linters.some')
            ->shouldBeCalled()
            ->willReturn(true);
        $container->get('linters.some')
            ->willReturn($linter);
        $linter->getName()
            ->shouldBeCalled()
            ->willReturn('some');
        $linter->getTitle()->willReturn('SomeTitle');

        $this->setOptions(array(
            'lint'=>'some',
            'pattern'=>'some'
        ));
        $linter->check(__FILE__)
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $this->lint(__FILE__)->shouldReturn(true);
    }

    function its_hasTag_returns_true_if_tag_exists()
    {
        $this->setOptions(array(
            'pattern' => 'some',
            'tags' => array('tag','foo','bar','hello','world')
        ));
        $this->shouldHaveTags('tag');
        $this->shouldHaveTags(array('tag'));
        $this->shouldHaveTags(array());
        $this->shouldHaveTags(null);
        $this->shouldNotHaveTags('untag');
        $this->shouldHaveTags(array('untag','foo','bar'));
        $this->shouldNotHaveTags(array('untag','ungag1','untag2'));
    }

    function its_tags_should_be_empty_by_default()
    {
        $this->getTags()->shouldReturn(array());
    }

    function its_tags_should_be_mutable()
    {
        $this->addTags('foo');
        $this->shouldHaveTags('foo');
        $this->addTags(array('bar','hello','world','foo','bar','hello'));

        $this->getTags()->shouldHaveCount(4);
        $this->shouldHaveTags('bar');
        $this->shouldHaveTags('hello');
        $this->shouldHaveTags('world');
    }
}
